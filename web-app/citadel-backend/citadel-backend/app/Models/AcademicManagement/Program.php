<?php

namespace App\Models\AcademicManagement;

use Illuminate\Database\Eloquent\Model;
use App\Models\Account;

class Program extends Model
{
    protected $fillable = [
        'program_name',
        'program_code',
        'program_head_id',
        'program_status',
    ];

    public function head()
    {
        return $this->belongsTo(Account::class, 'program_head_id');
    }
}
