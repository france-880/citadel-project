<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPasswordNotification;


class Account extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'fullname',
        'department',
        'dob',
        'role',
        'gender' ,
        'address' ,
        'contact' ,
        'email',
        'username',
        'password',

    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public $timestamps = true; // this is default, but just to be sure

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}


