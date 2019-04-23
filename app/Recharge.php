<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Recharge extends Model
{
    const STAT_PENDING = 1;
    const STAT_SUCCESS = 2;
}
