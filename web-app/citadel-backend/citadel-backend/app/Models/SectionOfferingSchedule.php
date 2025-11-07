<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionOfferingSchedule extends Model
{
    protected $fillable = [
        'section_offering_id',
        'day',
        'start_time',
        'end_time',
    ];

    /**
     * Get the section offering that owns the schedule.
     */
    public function sectionOffering(): BelongsTo
    {
        return $this->belongsTo(SectionOffering::class);
    }
}
