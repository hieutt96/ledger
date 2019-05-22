<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Bschmitt\Amqp\Facades\Amqp;
use App\Events\TransferSuccess;

class TransferSuccessNotification
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
    public function handle(TransferSuccess $event)
    {
        Amqp::publish('', json_encode(['name' => 'TransferSuccess', 'data' =>['account_from' => $event->accountFrom, 'account_to' => $event->accountTo, 'amount' => $event->amount]]) , ['queue' => 'wallet-queue']);
    }
}
