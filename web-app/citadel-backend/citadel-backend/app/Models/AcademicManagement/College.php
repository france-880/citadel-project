<?php

namespace App\Models\AcademicManagement;

use Illuminate\Database\Eloquent\Model;
use App\Models\Account;

class College extends Model
{
    protected $fillable = [
        'college_name',
        'college_code',
        'college_dean_id',
        'college_status',
    ];

    public function dean()
    {
        return $this->belongsTo(Account::class, 'college_dean_id');
    }
}
