<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_profile_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    // ── Day label helper ──────────────────────────────────────

    public function getDayNameAttribute(): string
    {
        return ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']
            [$this->day_of_week] ?? 'Unknown';
    }

    // ── Relations ─────────────────────────────────────────────

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
    }
}
