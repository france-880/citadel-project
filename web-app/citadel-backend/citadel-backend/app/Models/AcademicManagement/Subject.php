<?php

namespace App\Models\AcademicManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
     protected $fillable = [
        'subject_name', 
        'subject_code', 
        'units',
    ];

    /**
     * Get all programs that offer this subject (many-to-many)
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'program_subject');
    }
}