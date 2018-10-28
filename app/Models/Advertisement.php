<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    protected $fillable = ['title', 'img', 'img_uri', 'ad_expenses', 'divided_count', 'divided_amount', 'status'];

    public static $statusColors = [
        '0'    => 'grey',
        '1'   => 'green',
    ];
}
