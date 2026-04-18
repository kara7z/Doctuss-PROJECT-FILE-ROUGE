<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Validation\ValidationException;
use App\Enums\AppointmentStatus;
use App\Models\DoctorWorkingDate;
use Carbon\Carbon;

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

    public function workingDates(): HasMany
    {
        return $this->hasMany(DoctorWorkingDate::class, 'doctor_profile_id');
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
        $todayStr = $now->toDateString();
        $currentTime = $now->format('H:i:s');
        $isDuringShift = false;

        // First, check explicit working_dates for today
        if ($this->relationLoaded('workingDates')) {
            $todayWorkingDate = $this->workingDates
                ->first(fn($wd) => $wd->working_date->toDateString() === $todayStr);

            if ($todayWorkingDate) {
                $isDuringShift = $currentTime >= $todayWorkingDate->start_time
                    && $currentTime <= $todayWorkingDate->end_time;
            }
        }

        // Fallback to recurring schedules
        if (!$isDuringShift && $this->relationLoaded('schedules')) {
            $todayIndex = $now->dayOfWeek;
            $todaysSchedules = $this->schedules->where('day_of_week', $todayIndex);
            foreach ($todaysSchedules as $schedule) {
                if ($currentTime >= $schedule->start_time && $currentTime <= $schedule->end_time) {
                    $isDuringShift = true;
                    break;
                }
            }
        }

        if (!$isDuringShift) {
            return 'Offline';
        }

        // Must eager load appointments!
        $activeAppointments = $this->appointments
            ->where('status', AppointmentStatus::APPROVED)
            ->filter(function ($appt) use ($now) {
                if (!$appt->proposed_at) return false;
                $start = Carbon::parse($appt->proposed_at);
                $end = $start->copy()->addHour();
                return $now->between($start, $end);
            });

        if ($activeAppointments->isNotEmpty()) {
            return 'Busy';
        }

        return 'Available';
    }

}
