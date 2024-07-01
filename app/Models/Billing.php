<?php

namespace App\Models;

use App\Models\Model;
use App\Models\Account;
use App\Models\BillingType;
use App\Http\Controllers\Admin\Traits\CurrencyFormat;
use App\Models\Scopes\ExcludeSoftDeletedAccountsScope;

class Billing extends Model
{
    use CurrencyFormat;

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

        static::addGlobalScope(new ExcludeSoftDeletedAccountsScope);

        static::creating(function ($billing) {
            // Setting date fields to null based on billing_type_id
            if ($billing->billing_type_id == 1) { // installment
                $billing->date_start = null;
                $billing->date_end = null;
                $billing->date_cut_off = null;
                

                // OTCS
                $particulars = [];
                // Accessing the 'account' relationship and iterating over 'otcs'
                if ($billing->account->otcs) {
                    foreach ($billing->account->otcs as $otc) {
                        
                        $particulars[] = [
                            'description' => $otc->name,
                            'amount' => $otc->amount,
                        ];
                    }
                }

                // Contract Periods
                $contractPeriodExists = $billing->account->contractPeriods()->where('contract_periods.id', 1)->exists();

                if ($contractPeriodExists) {
                    // If the ContractPeriod with ID 1 exists for this Billing, 1 = one month advance
                    $contractPeriod = $billing->account->contractPeriods()->where('contract_periods.id', 1)->first();
                    
                    // Perform operations with $contractPeriod
                    $particulars[] = [
                        'description' => $contractPeriod->name,
                        'amount' => $billing->account->plannedApplication->price
                    ];
                } 

                $billing->particulars = array_values($particulars);

            }elseif ($billing->billing_type_id == 2) { // monthly
                // TODO:: dont forget to compute service interruption
                // TODO:: make account.installed_date is not null before proceeding otherwise fire a validation
            }else {
                // do nothing
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */
    public function getTotalAttribute()
    {
        return collect($this->particulars)->sum(function ($item) {
            return (float) $item['amount'];
        });
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
    
                $amount = $particular['amount'];
    
                if ($amount) {
                    $amount = $this->currencyFormatAccessor($amount);
                }
    
                $details[] = "<strong>{$particular['description']}</strong> : {$amount}";
                // $details[] = "{$particular['description']} : <strong>{$amount}</strong>";
                // $details[] = "{$particular['description']} : {$amount}";
            }
            return implode('<br>', $details);
        }

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
    // TODO:: Delete later if no longer use
    public function setTempParticularsAttribute($value)
    {
        if ($this->attributes['billing_type_id'] == 1) { // Installation Fee

            $data = [];

            $otcs = $this->account->otcs;

            // OTC record from account
            if ($otcs) {
                foreach ($otcs as $otc) {
                    $data[] = [
                        'description' => $otc->name,
                        'amount' => $otc->amount,
                    ];
                }
            }

            if ($value) {
                $data = collect($data)->merge($value)->unique('description')->values()->all();
            } 

            // Modify particulars attribute as needed
            $this->attributes['particulars'] = json_encode($data);

            // Reset other attributes as needed
            $this->attributes['date_start'] = null;
            $this->attributes['date_end'] = null;
            $this->attributes['date_cut_off'] = null;

        } else {
            // For other billing types, simply encode $value as JSON
            $this->attributes['particulars'] = json_encode($value);
        }
    }


    
}
