<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Orders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function(Blueprint $table) {

            $table->increments('id');
            $table->integer('user_id', false, 11)->nullable();
            $table->string('mrc_order_id')->nullable();
            $table->integer('txn_id', false, 11)->nullable();
            $table->string('ref_no')->nullable();
            $table->integer('amount', false, 25)->nullable();
            $table->string('url_success', 255)->nullable();
            $table->text('webhooks')->nullable();
            $table->tinyInteger('stat', false, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
