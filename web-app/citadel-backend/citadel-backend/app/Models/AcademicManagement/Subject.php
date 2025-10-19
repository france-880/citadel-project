<?php

namespace App\Models\AcademicManagement;

use Illuminate\Database\Eloquent\Model;
use App\Models\AcademicManagement\Program;

class Subject extends Model
{
    protected $fillable = [
        'subject_name',
        'subject_code',
        'subject_type',
        'days',
        'lab_hours',
        'lec_hours',
        'units',
        'time',
    ];

    protected $casts = [
        'days' => 'array',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
