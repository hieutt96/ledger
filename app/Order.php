<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    const PAY_PENDING = 1;
    const PAY_SUCCESS = 2;
}
