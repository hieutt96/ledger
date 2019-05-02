<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Txn extends Model
{
    protected $fillable = ['user_id', 'account_id', 'ref_no', 'type', 'stat'];
}
