<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Validation\ValidationException;
use App\Enums\AppointmentStatus;

class DoctorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'doctor_specialty_id',
        'experience_start_date',
        'hospital_name',
        'city',
        'phone_number',
        'profile_picture',
        'banner_picture',
        'location_link',
        'bio',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_verified'           => 'boolean',
            'experience_start_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (DoctorProfile $profile): void {
            $user = User::query()->find($profile->user_id);

            if (! $user || ! $user->isDoctor()) {
                throw ValidationException::withMessages([
                    'user_id' => 'Doctor profile can only belong to users with role doctor.',
                ]);
            }
        });
    }

    // ── Relations ─────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(DoctorSpecialty::class, 'doctor_specialty_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_profile_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'doctor_profile_id');
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class);
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(Review::class, Appointment::class, 'doctor_profile_id', 'appointment_id');
    }

    public function getCurrentStatusAttribute(): string
    {
        $now = now();
        $currentTime = $now->format('H:i:s');
        $todayIndex = $now->dayOfWeek;
        $isDuringShift = $this->schedules()
            ->where('day_of_week', $todayIndex)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>', $currentTime)
            ->exists();

        if (!$isDuringShift) {
            return 'Unavailable';
        }

        $hourAgo = $now->copy()->subHour();
        $activeAppointments = $this->appointments()
            ->where('status', AppointmentStatus::APPROVED->value)
            ->where(function ($query) use ($hourAgo, $now) {
                $query->where(function ($q) use ($hourAgo, $now) {
                    $q->whereNotNull('proposed_at')
                        ->whereBetween('proposed_at', [$hourAgo, $now]);
                })->orWhere(function ($q) use ($hourAgo, $now) {
                    $q->whereNull('proposed_at')
                        ->whereNotNull('preferred_at')
                        ->whereBetween('preferred_at', [$hourAgo, $now]);
                });
            })
            ->exists();

        if ($activeAppointments) {
            return 'Busy';
        }

        return 'Available';
    }

}
