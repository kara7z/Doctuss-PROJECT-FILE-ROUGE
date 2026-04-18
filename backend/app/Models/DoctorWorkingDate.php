<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorWorkingDate extends Model
{
    protected $fillable = [
        'doctor_profile_id',
        'working_date',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'working_date' => 'date',
        ];
    }

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
    }
}
