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
        NOTE:: Before proceeding and read the methods below and attributes,
        if you notice many methods are almost similar in accounts or other relationships,
        the reason for that is although it has FK account_id relationship, when the bill
        is created i save a snapshot of accounts and other records as json datatype in billings
        table column name accounts_snapshot and upgrade_account_snapshot. So that it could capture
        the data the moment the bill was created and no matter if they change the account records
        and related data it will not be change because this is an invoice or receipt that value not need
        to change and to be preserved.

        Here is the behavior if the upgrade_account_snapshot is empty, then some attributes will pull from the
        relationship which is the account, but if the json column named account_snapshot is not empty, then it will
        pull that instead of the relationship account, but if the upgrade_account_snapshot is not empty then it will
        get that instead.

        This is the precedents:
            upgrade_account_snapshots,
            account_snapshots,
            account model relationship.

        The reason y sometimes i need to pull the account mdoel relationship values is because if you notice i use creating event, and 
        when the model is still creatd, of course the 2 json datatype column are still empty, but once they are already
        have data then the system will prioritize to pick up that data as you can see in the above precedents.

        The convention i use is whatever the real relationship in account is i used it but camelCase, example for account->lacation->name,
        i use locationName/getLocationNameAttribute, to make it similar.
    */

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

    public function isUnpaid() : bool
    {
        if ($this->billing_status_id == 2) {
            return true;
        }        

        return false;
    }
    
    /* 
    NOTE::
        we can use account model relatipnship to get service interruptions because we will check if the service date_start and date_end is 
        in between the billing start and end.    

        return service interruptions list of dates that is covered or in between billing date start and end
    */
    public function accountServiceInterruptions()
    {
        return $this->account
            ->accountServiceInterruptions()
            ->whereBetween('date_start', [$this->date_start, $this->date_end])
            ->whereBetween('date_end', [$this->date_start, $this->date_end])
            ->get();
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

    public function getMonthAttribute()
    {
        return getMonthFromDate($this->date_start);
    }

    /* 
        NOTE::
            use $this->realAccount if you want to get account datas
            if upgrade_account_snapshot is not empty then use it.
            else if use account_snapshot

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

    public function getAccountInstalledDateAttribute()
    {
        if ($this->realAccount) {
            return $this->realAccount['account']['installed_date'];
        }

        return $this->account->installed_date;
    }   
    
    public function getAccountNameAttribute()
    {
        return $this->account->customer->full_name;
    }

    public function getSubscriptionNameAttribute()
    {
        if ($this->realAccount) {
            return $this->realAccount['subscription']['name'];
        }

        return $this->account->subscription->name;
    }

    public function getLocationNameAttribute()
    {
        if ($this->realAccount) {
            return $this->realAccount['location']['name'];
        }

        return $this->account->plannedApplication->location->name;
    }

    public function getPlannedApplicationTypeNameAttribute()
    {
        if ($this->realAccount) {
            return $this->realAccount['plannedApplicationType']['name'];
        }

        return $this->account->plannedApplication->plannedApplicationType->name;
    }

    public function getPlannedApplicationTypeNameShortenAttribute()
    {
        $type = $this->plannedApplicationTypeName;

        $type = explode("/", $type);

        if (is_array($type)) {
            $type = $type[0];
        }

        return $type;
    }

    public function getAccountPlannedApplicationMbpsAttribute()
    {
        if ($this->realAccount) {
            return $this->realAccount['plannedApplication']['mbps'];
        }

        return $this->account->plannedApplication->mbps;
    }


    public function getAccountPlannedApplicationDetailsAttribute()
    {
        if ($this->realAccount) {

            return $this->locationname . ' - '. 
                    $this->plannedAplicationTypeNameShorten . ' :'. 
                    $this->accountPlannedApplicationMbps . 'Mbps ----- '.
                    $this->currencyFormatAccessor($this->realAccount['plannedApplication']['price']);
        }

        return $this->account->plannedApplication->details;
    }
    

    // Data Taken from snapshot
    public function getAccountDetailsAttribute()
    {
        $from = 'account_relationship';

        if ($this->upgrade_account_snapshot) {
            $from = 'upgrade_account_snapshot';
        }else if ($this->account_snapshot) {
            $from = 'account_snapshot';
        }

        return $this->accountDetails(
            from: $from,
            id: $this->id,
            name: $this->accountName,
            location: $this->locationName,
            type: $this->plannedApplicationTypeNameShorten,
            subscription: $this->subscriptionName, 
            mbps: $this->accountPlannedApplicationMbps,
            installedDate: $this->accountInstalledDate
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

    public function getMonthlyRateAttribute()
    {   
        if ($this->realAccount) {
            return $this->realAccount['plannedApplication']['price'];
        }

        return $this->account->plannedApplication->price;
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

    public function getIsProRatedMonthlyAttribute()
    {
        
        if ($this->accountInstalledDate > $this->date_start) {
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

    public function getProRatedDaysAndHoursServiceAttribute()
    {
        if ($this->isProRatedMonthly) {
            $installedDate = $this->accountInstalledDate;
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

    public function getServiceInterruptDescAttribute()
    {
        $totalDaysInterrupt = $this->totalDaysServiceInterruptions;
        
        if ($totalDaysInterrupt) {
            $days = $totalDaysInterrupt > 1 ? 'days' : 'day';

            return "Service Interruptions ($totalDaysInterrupt $days)";
        }

        return;
    }

    public function getTotalDaysServiceInterruptionsAttribute()
    {
        $interruptions = $this->accountServiceInterruptions();
        
        if ($interruptions) {
            $totalDaysInterrupt = 0;
            foreach ($interruptions as $interrupt) {
                $dateStart = Carbon::parse($interrupt->date_start);
                $dateEnd = Carbon::parse($interrupt->date_end);

                $totalDaysInterrupt += $dateStart->diffInDays($dateEnd);

            }

            return $totalDaysInterrupt;
        }

        return;
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

            // TODO:: capture the account credits exact amount, for documentation and audit trails and review. each bill  
            // TODO:: make sure when we have a button pay using credits, it will add a -amount row in account credits first, before updating the accountCredits here in snapshot
            // TODO:: instead of saving account credits object, TBD how about saving the reaming ammount or credit after the pay.
            $snapshot['accountCredits'] = $this->account->accountCredits->toArray();
            
            // save Service interruptons anyway, for documentation purposes but use Particulars instead
            // $snapshot['accountServiceInterruptions'] = $this->account->accountServiceInterruptions->toArray();
            $snapshot['accountServiceInterruptions'] = $this->accountServiceInterruptions(); // use this function becaues it has where clause

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
            $totalInterruptionDays = $this->totalDaysServiceInterruptions;
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
