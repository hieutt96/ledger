<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\TransferSuccess;
use App\Events\RechargeSuccess;
use App\Events\WithdrawalSuccess;
use App\Listeners\TransferSuccessNotification;
use App\Listeners\RechargeSuccessNotification;
use App\Listeners\WithdrawalSuccessNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        TransferSuccess::class => [
            TransferSuccessNotification::class,
        ],
        RechargeSuccess::class => [
            RechargeSuccessNotification::class,
        ],
        WithdrawalSuccess::class => [
            WithdrawalSuccessNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
