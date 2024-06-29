<?php

namespace App\Models;

use App\Http\Controllers\Admin\Traits\CurrencyFormat;
use App\Models\Model;
use App\Models\Account;
use App\Models\BillingType;

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

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

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
    public function getParticularDetailsAttribute()
    {
        $details = [];
        foreach ($this->particulars as $particular) {

            $amount = $particular['amount'];

            if ($amount) {
                $amount = $this->currencyFormatAccessor($amount);
            }

            $details[] = "<strong>{$particular['description']}</strong> : {$amount}";
        }
        return implode('<br>', $details);
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
    public function setParticularsAttribute($value)
    {
        if ($this->attributes['billing_type_id'] == 1) { // Installation Fee

            $data = [];

            $otcs = $this->account->otcs;

            if ($otcs) {
                foreach ($otcs as $otc) {
                    $data[] = [
                        'description' => $otc->name,
                        'amount' => $otc->amount,
                    ];
                }
                
                // Modify particulars attribute as needed
                $this->attributes['particulars'] = json_encode($data);
                
                $this->attributes['date_start'] = null;
                $this->attributes['date_end'] = null;
                $this->attributes['date_cut_off'] = null;
            }

        }else {
            $this->attributes['particulars'] = json_encode($value);
        }
        
    }

    
}
