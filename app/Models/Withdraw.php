<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model {

    public static $status = [
        '0' => '待确认',
        '1' => '已提现',
    ];

    public static $statusColors = [
        '0' => 'grey',
        '1' => 'green',
    ];

    public function user() {

        return $this->belongsTo(User::class);
    }

    public function complexes() {

        return $this->hasMany(Complex::class, 'user_id', 'user_id');
    }
}
