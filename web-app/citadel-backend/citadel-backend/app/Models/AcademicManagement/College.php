<?php

namespace App\Models\AcademicManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Account;

class College extends Model
{
    protected $fillable = [
        'college_name', 
        'college_code', 
        'dean_id'
    ];
    
    /**
     * Get the dean (account) associated with the college
     */
    public function dean(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'dean_id');
    }
    
    /**
     * Get all accounts (professors) belonging to this college
     */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }
}