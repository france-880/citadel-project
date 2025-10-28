<?php

namespace App\Models\AcademicManagement;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
     protected $fillable = [
        'subject_name', 
        'subject_code', 
        'units',
        'semester',
        'year_level',
    ];
}