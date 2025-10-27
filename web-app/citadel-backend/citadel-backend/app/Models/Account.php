<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPasswordNotification;
use App\Models\AcademicManagement\College;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'fullname',
        'college_id',
        'dob',
        'role',
        'gender',
        'address',
        'contact',
        'email',
        'username',
        'password',
    ];

     protected $hidden = [
        'password',
        'remember_token',
    ];

    public $timestamps = true;

    /**
     * Relationship: Account belongs to a College (as professor)
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class, 'college_id');
    }

    /**
     * Relationship: Account is dean of a College
     */
    public function collegeAsDean(): HasOne
    {
        return $this->hasOne(College::class, 'dean_id');
    }

    /**
     * Relationship: Account is program head of a Program
     */
    public function programAsHead(): HasOne
    {
        return $this->hasOne(Program::class, 'program_head_id');
    }

    /**
     * Check if account is a dean
     */
    public function isDean(): bool
    {
        return $this->role === 'dean' || $this->collegeAsDean()->exists();
    }

    /**
     * Check if account is a program head
     */
    public function isProgramHead(): bool
    {
        return $this->role === 'program_head' || $this->programAsHead()->exists();
    }

    /**
     * Check if account is a professor
     */
    public function isProfessor(): bool
    {
        return $this->role === 'prof';
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
