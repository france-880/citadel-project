<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'fullname',
        'student_no',
        'section',
        'program',
        'year',
        'dob',
        'gender',
        'email',
        'contact',
        'address',
        'guardian_name',
        'guardian_contact',
        'guardian_address',
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public $timestamps = true; // this is default, but just to be sure
}


