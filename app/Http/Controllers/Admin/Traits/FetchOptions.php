<?php

namespace App\Http\Controllers\Admin\Traits;

use App\Models\Customer;
use App\Models\BillingType;
use App\Models\Subscription;
use App\Models\AccountStatus;
use App\Models\BillingStatus;

trait FetchOptions
{
    public function accountStatusLists()
    {
        return AccountStatus::pluck('name', 'id')->toArray();
    }

    public function billingStatusLists()
    {
        return BillingStatus::pluck('name', 'id')->toArray();
    }

    public function billingTypeLists()
    {
        return BillingType::pluck('name', 'id')->toArray();
    }

    public function subscriptionLists()
    {
        return Subscription::pluck('name', 'id')->toArray();
    }

    public function customerLists()
    {
        $customers = Customer::all();

        return $customers->mapWithKeys(function ($customer) {
            return [$customer->id => $customer->full_name];
        })->toArray();
    }

}
