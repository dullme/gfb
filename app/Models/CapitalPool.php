<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapitalPool extends Model
{
    protected $fillable = ['price', 'Balance', 'change_amount', 'type'];
}
