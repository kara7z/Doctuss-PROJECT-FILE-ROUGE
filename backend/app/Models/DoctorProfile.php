<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

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
        'hospital_lat',
        'hospital_lng',
        'bio',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_verified'           => 'boolean',
            'experience_start_date' => 'date',
            'hospital_lat'          => 'float',
            'hospital_lng'          => 'float',
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

}
