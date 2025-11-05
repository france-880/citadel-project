<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\AcademicManagement\Subject;

class FacultyLoad extends Model
{
    protected $fillable = [
        'faculty_id',
        'section_offering_id',
        'subject_id',
        'subject_code',
        'subject_description',
        'lec_hours',
        'lab_hours',
        'units',
        'section',
        'schedule',
        'room',
        'type',
        'academic_year',
        'semester'
    ];

    protected $casts = [
        'lec_hours' => 'integer',
        'lab_hours' => 'integer',
        'units' => 'integer',
    ];

    // Relationships
    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function sectionOffering()
    {
        return $this->belongsTo(SectionOffering::class, 'section_offering_id');
    }
}
