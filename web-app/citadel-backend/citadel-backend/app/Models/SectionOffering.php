<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\AcademicManagement\Program;
use App\Models\AcademicManagement\Subject;

class SectionOffering extends Model
{
    protected $fillable = [
        'program_id',
        'academic_year',
        'semester',
        'year_level',
        'parent_section',
        'subject_id',
        'slots',
        'lec_hours',
        'lab_hours',
    ];

    /**
     * Get the program that owns the section offering.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the subject that owns the section offering.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the schedules for the section offering.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(SectionOfferingSchedule::class);
    }
}
