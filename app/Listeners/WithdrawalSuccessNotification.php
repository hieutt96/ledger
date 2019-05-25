<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\WithdrawalSuccess;
use Bschmitt\Amqp\Facades\Amqp;

class WithdrawalSuccessNotification
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
    public function handle(WithdrawalSuccess $event)
    {
        Amqp::publish('', json_encode(['name' => 'WithdrawalSuccess', 'data' =>['withdrawal' => $event->withdrawal]]) , ['queue' => 'wallet-queue']);
    }
}
