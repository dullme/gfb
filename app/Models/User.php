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
        'original_price',
        'retail_price',
        'mobile',
        'alipay_account',
        'alipay_name',
        'status',
        'initial_password',
        'activation_at',
        'validity_period',
        'expiration_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
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

}
