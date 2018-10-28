<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complex extends Model
{
    protected $fillable = [
        'user_id', 'history_read_count', 'history_amount'
    ];

    public function user() {

        return $this->belongsTo(User::class);
    }
}
