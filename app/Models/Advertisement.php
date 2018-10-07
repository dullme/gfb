<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    public static $statusColors = [
        '0'    => 'grey',
        '1'   => 'green',
    ];
}
