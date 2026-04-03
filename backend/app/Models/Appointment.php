<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'doctor_profile_id',
        'preferred_at',
        'proposed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'preferred_at' => 'datetime',
            'proposed_at'  => 'datetime',
            'status'       => AppointmentStatus::class,
        ];
    }

    // ── Relations ─────────────────────────────────────────────

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class, 'doctor_profile_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', AppointmentStatus::PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', AppointmentStatus::APPROVED);
    }

    public function scopeForDoctorProfile($query, int $doctorProfileId)
    {
        return $query->where('doctor_profile_id', $doctorProfileId);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }
}
