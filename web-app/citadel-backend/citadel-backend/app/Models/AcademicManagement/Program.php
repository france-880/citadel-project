<?php

namespace App\Models\AcademicManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Account;
use App\Models\AcademicManagement\College;
use App\Models\Student;

class Program extends Model
{
    protected $fillable = [
        'program_name', 
        'program_code', 
        'college_id', 
        'program_head_id'
    ];
    
    /**
     * Get the college that owns the program
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }
    
    /**
     * Get the program head (account) associated with the program
     */
    public function programHead(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'program_head_id');
    }

    public function students() {
        return $this->hasMany(Student::class);
    }

    /**
     * Get all subjects assigned to this program (many-to-many)
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'program_subject')
            ->withPivot('semester', 'year_level')
            ->withTimestamps();
    }
}