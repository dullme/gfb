<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model {

    protected $fillable = [
        'user_id',
        'price',
        'status',
        'payment_at'
    ];

    public static $status = [
        '0' => '未导出',
        '1' => '已导出',
        '2' => '已处理',
    ];

    public static $statusColors = [
        '0' => 'grey',
        '1' => 'red',
        '2' => 'green',
    ];

    public function user() {

        return $this->belongsTo(User::class);
    }

    public function complexes() {

        return $this->hasMany(Complex::class, 'user_id', 'user_id');
    }
}
