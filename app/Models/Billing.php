<?php

namespace App\Models;

use App\Models\Model;
use App\Models\Account;
use App\Models\BillingType;
use App\Models\BillingStatus;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Admin\Traits\AccountCrud;
use App\Http\Controllers\Admin\Traits\CurrencyFormat;
use App\Models\Scopes\ExcludeSoftDeletedAccountsScope;

class Billing extends Model
{
    use CurrencyFormat;
    use AccountCrud;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'billings';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];

    protected $casts = [
        'particulars' => 'array',
        'account_snapshot' => 'array',
        'upgrade_account_snapshot' => 'array',
    ];

    protected $attributes = [
        'billing_status_id' => 2, // Newly created bill default value 2 or Unpaid
    ];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    protected static function boot()
    {
        parent::boot();

        // static::addGlobalScope(new ExcludeSoftDeletedAccountsScope);

        static::creating(function ($billing) {

            $billing->createParticulars();
            
            $billing->saveAccountSnapshot();

        });
    }

    public function isPaid() : bool
    {
        if ($this->billing_status_id == 1) {
            return true;
        }        

        return false;
    }
    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    public function billingStatus()
    {
        return $this->belongsTo(BillingStatus::class);
    }

    public function billingType()
    {
        return $this->belongsTo(BillingType::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */
    public function scopeCutOffAccountLists($query)
    {
        return $this->monthly()
        ->whereBetween('date_cut_off', [
            carbonToday()->subDays(5), 
            carbonToday()->addDays(5)
        ]);
    }

    public function scopeMonthly($query)
    {
        return $query->whereHas('billingType', function ($q) {
            $q->where('id', 2); // monthly 
        });
    }

    public function scopeUnpaid($query)
    {
        return $query->whereHas('billingStatus', function ($q) {
            $q->where('id', 2); // Unpaid
        });
    }

    public function scopePaid($query)
    {
        return $query->whereHas('billingStatus', function ($q) {
            $q->where('id', 1);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /* 
        NOTE::
            use $this->realAccount if you want to get account datas
            if upgrade acc snapshot is not empty it will take from there
            else if from acc snapshot
            otherwise from acc relationship

    */
    public function getRealAccountAttribute() 
    {
        if ($this->upgrade_account_snapshot) {
            
            return $this->upgrade_account_snapshot;
        
        }elseif ($this->account_snapshot) {

            return $this->account_snapshot;

        }

        return;
    }


    // Data Taken from snapshot
    public function getAccountSnapshotDetailsAttribute()
    {
        $name = $this->account->customer->full_name; // use relationship name here instead of snapshot so if customer change name it will reflect
        $subscription = $this->realAccount['subscription']['name'];
        $location = $this->realAccount['location']['name'];
        $type = $this->realAccount['plannedApplicationType']['name'];
        $mbps = $this->realAccount['plannedApplication']['mbps'];

        $type = explode("/", $type);

        if (is_array($type)) {
            $type = $type[0];
        }

        return $this->accountDetails(
            from: ($this->upgrade_account_snapshot ? 'upgrade_account_snapshot' : 'account_snapshot'),
            id: $this->id,
            name: $name,
            location: $location,
            type: $type,
            subscription: $subscription, 
            mbps: $mbps
        );
    }

    public function getDateCutOffBadgeAttribute()
    {
        $dateCutOff = $this->date_cut_off;
        $class = '';
        $daysDifference = '';

        if ($dateCutOff) {
            // Calculate difference in days from now
            $cutOffDate = Carbon::parse($dateCutOff);
            $now = Carbon::now();
            $daysDifference = $now->diffInDays($cutOffDate);

            // Determine badge class based on days difference
            if ($daysDifference <= 0) {
                $class = 'text-danger';
            }elseif ($daysDifference <= 2) {
                $class = 'text-warning'; 
            } elseif ($daysDifference <= 4) {
                $class = 'text-info'; 
            }
        }

        return '<span 
                    diff="'.$daysDifference.'"
                    class="'.$class.'">'.
                    Carbon::parse($this->date_cut_off)->format(dateHumanReadable()).
                '</span>'; // Return empty string if no condition matched
    }

    public function getTotalAttribute()
    {
        $totalAmount = collect($this->particulars)->sum(function ($item) {
            return (float) $item['amount'];
        });

        return $this->currencyRound($totalAmount);
    }

    public function getBillingPeriodDetailsAttribute()
    {

        if ($this->billing_type_id == 1) {
            return "<strong>{$this->billingType->name}</strong>";
        }

        // return $this->date_start . ' - '. $this->date_end;

        return "
            <strong>Date Start</strong> : {$this->date_start} <br>
            <strong>Date End</strong> : {$this->date_end} <br>
            <strong>Cut Off</strong> : {$this->date_cut_off} <br>
        ";
    }

    public function getParticularDetailsAttribute()
    {
        $details = [];

        if ($this->particulars) {
            foreach ($this->particulars as $particular) {
                $textColor = 'text-info ';
                $amount = $particular['amount'];
    
                if ($amount) {
                    
                    if ($amount < 0) {
                        $textColor = 'text-danger';
                    }

                    $amount = $this->currencyFormatAccessor($amount);
                }
    

                $details[] = "<strong>{$particular['description']}</strong> : <span class='{ $textColor }'>{$amount}</span>";
            }
            return implode('<br>', $details);
        }

        return;
    }

    // NOTE:: check realAccount function above
    public function getMonthlyRateAttribute()
    {   
        return $this->realAccount['plannedApplication']['price'];
    }
    
    public function getDailyRateAttribute()
    {
        if ($this->monthlyRate && $this->totalNumberOfDays) {
            return $this->monthlyRate / $this->totalNumberOfDays;
        }

        return;
    }

    public function getHourlyRateAttribute()
    {
        if ($this->dailyRate){
            return $this->dailyRate / 24; // 24 hours
        }

        return;
    }

    // Total number of days for the period
    public function getTotalNumberOfDaysAttribute()
    {
        if ($this->date_start && $this->date_end) {
            $startDate = Carbon::parse($this->date_start);
            $endDate = Carbon::parse($this->date_end);
        
            $totalNumberOfDays = $startDate->diffInDays($endDate);
        
            return $totalNumberOfDays;
        }

        return;
    }

    // NOTE:: check realAccount func
    public function getIsProRatedMonthlyAttribute()
    {
        if ($this->realAccount['account']['installed_date'] > $this->date_start) {
            return true;
        }   

        return false;
    }
    
    // pro rated total
    public function getProRatedServiceTotalAmountAttribute()
    {
        if ($this->isProRatedMonthly) {
            $total = $this->dailyRate * $this->proRatedDaysAndHoursService['days'];
            return $this->currencyRound($total);
        }

        return;
    }

    // check realAccount func and the content of js in db
    public function getProRatedDaysAndHoursServiceAttribute()
    {
        if ($this->isProRatedMonthly) {
            $installedDate = $this->realAccount['account']['installed_date'];
            if ($installedDate && $this->date_end) {
                return $this->proRatedDaysAndHoursService($installedDate, $this->date_end);
            }
        }
        
        return;
    }

    // Method to calculate days and hours difference
    public function proRatedDaysAndHoursService($dateStart = null, $dateEnd = null)
    {
        $dateStart = Carbon::parse($dateStart);
        $dateEnd = Carbon::parse($dateEnd);

        if ($dateStart && $dateEnd) {
            // Calculate the difference and format it
            $difference = $dateEnd->diff($dateStart)->format('%a|%H|%I');
            $diff = $dateEnd->diff($dateStart)->format('%a days, %H:%I');

            // Explode the formatted difference into an array
            list($days, $hours, $minutes) = explode('|', $difference);

            // Create the array with named keys
            return [
                'days' => (int) $days,
                'hours' => (int) $hours,
                'minutes' => (int) $minutes,
                'diff' => $diff,
            ];

        }

        return;
    }

    public function getProRatedDescAttribute()
    {
        if ($this->isProRatedMonthly) {
            $num = $this->totalNumberOfDays - $this->proRatedDaysAndHoursService['days'];
                    $days = $num > 1 ? 'days' : 'day';
    
            return "Pro-rated Service Adjustment ($num $days)";
        }

        return;
    }

    // TODO:: transfer total interrupt from accounts -> account interrupted to billings -> interrupted table
    // HERE: naku
    public function getServiceInterruptDescAttribute()
    {
        return '123';

        // $totalInterruptionDays = $this->account->total_service_interruption_days;

        // if ($totalInterruptionDays) {
        //     $days = $totalInterruptionDays > 1 ? 'days' : 'day';

        //     return "Service Interruptions ($totalInterruptionDays $days)";

        // }

        // return;
    }
    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
    
    public function saveAccountSnapshot($column = 'account_snapshot') 
    {
        $snapshot = [];

        if ($this->account) {
            $snapshot['account'] = $this->account->toArray();
            $snapshot['plannedApplication'] = $this->account->plannedApplication->toArray();
            $snapshot['plannedApplicationType'] = $this->account->plannedApplication->plannedApplicationType->toArray();
            $snapshot['location'] = $this->account->plannedApplication->location->toArray();
            $snapshot['subscription'] = $this->account->subscription->toArray();
            $snapshot['otcs'] = $this->account->otcs->toArray();
            $snapshot['contractPeriods'] = $this->account->contractPeriods->toArray();
            $snapshot['accountStatus'] = $this->account->accountStatus->toArray();
            $snapshot['accountCredits'] = $this->account->accountCredits->toArray();
            // save Service interruptons anyway, for documentation purposes but use Particulars instead
            $snapshot['accountServiceInterruptions'] = $this->account->accountServiceInterruptions->toArray();

        }

        $this->{$column} = $snapshot;
    }

    public function saveUpgradeAccountSnapshot()
    {
        $this->saveAccountSnapshot(column: 'upgrade_account_snapshot');
    }

    public function createParticulars()
    {
        $particulars = [];

        // Setting date fields to null based on billing_type_id
        if ($this->billing_type_id == 1) { // installment
            $this->date_start = null;
            $this->date_end = null;
            $this->date_cut_off = null;

            // OTCS
            if ($this->account->otcs) {
                foreach ($this->account->otcs as $otc) {
                    $particulars[] = [
                        'description' => $otc->name,
                        'amount' => $otc->amount,
                    ];
                }
            }
            
            // Contract Periods
            $contractId = 1; // 1-month advance
            $contractPeriodExists = $this->account->contractPeriods()->where('contract_periods.id', $contractId)->exists();

            if ($contractPeriodExists) {
                $contractPeriod = $this->account->contractPeriods()->where('contract_periods.id', $contractId)->first();
                $particulars[] = [
                    'description' => $contractPeriod->name,
                    'amount' => $this->account->plannedApplication->price,
                ];
            }


        } elseif ($this->billing_type_id == 2) { // monthly
            $particulars[] = [
                'description' => $this->billingType->name,
                'amount' => $this->account->monthlyRate,
            ];

            // Pro-rated Service Adjustment
            if ($this->isProRatedMonthly) {
                $particulars[] = [
                    'description' => $this->proRatedDesc,
                    'amount' => -($this->account->monthlyRate - $this->proRatedServiceTotalAmount),
                ];
            }

            // Service Interrptions
            $totalInterruptionDays = $this->account->total_service_interruption_days;
            if ($totalInterruptionDays) {
                $particulars[] = [
                    'description' => $this->serviceInterruptDesc,
                    'amount' => -($this->currencyRound($totalInterruptionDays * $this->dailyRate)),
                ];
            }            
        }

        $this->particulars = array_values($particulars);
    }
    
}
