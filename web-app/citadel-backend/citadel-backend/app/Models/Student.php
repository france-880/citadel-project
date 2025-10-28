<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\AcademicManagement\Program; 
use App\Models\YearSection;

class Student extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'fullname',
        'student_no',
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

        'program_id',
        'year_section_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public $timestamps = true; // this is default, but just to be sure

    // Relationship sa Program
    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id'); 
    }

    // Relationship sa YearSection
    public function yearSection()
    {
        return $this->belongsTo(YearSection::class, 'year_section_id');
    }
}

