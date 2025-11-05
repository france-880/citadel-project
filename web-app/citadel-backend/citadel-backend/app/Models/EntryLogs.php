<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntryLogs extends Model
{
    protected $fillable = [
        'student_no',
        'timestamps',
        'method_id'
    ]

    public $timestamps = true;
}
