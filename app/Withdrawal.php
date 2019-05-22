<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    
    const STAT_PENDING = 1;
    const STAT_SUCCESS = 2;
    const STAT_FAIL = 3;
    
    protected $fillable = ['user_id', 'amount', 'type', 'stat'];
}
