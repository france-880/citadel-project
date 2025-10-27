<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;

class YearSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_level',
        'section'
    ];

    // Relationship sa students
    public function students()
    {
        return $this->hasMany(Student::class, 'year_section_id');
    }
}
