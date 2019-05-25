<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\RechargeSuccess;
use Bschmitt\Amqp\Facades\Amqp;

class RechargeSuccessNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(RechargeSuccess $event)
    {
        Amqp::publish('', json_encode(['name' => 'RechargeSuccess', 'data' =>['recharge' => $event->recharge]]) , ['queue' => 'wallet-queue']);
    }
}
