<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
        'staff_id',
        'original_price',
        'retail_price',
        'mobile',
        'alipay_account',
        'alipay_name',
        'realname',
        'status',
        'initial_password',
        'activation_at',
        'validity_period',
        'expiration_at',
        'avatar',
        'wrong_password',
        'remember_token',
        'amount',
        'history_amount',
        'history_read_count',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public static $statusColors = [
        '0'    => 'grey',
        '1'   => 'yellow',
        '2'   => 'green',
        '3'   => 'red',
    ];

    public function findForPassport($username) {

        return User::where('id', $username)->first();
    }

    public function complexes() {

        return $this->hasMany(Complex::class);
    }

    public function withdraws() {

        return $this->hasMany(Withdraw::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

}
