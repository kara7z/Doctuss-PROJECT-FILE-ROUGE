<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'gender',
        'birthday',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'status'            => UserStatus::class,
            'gender'            => Gender::class,
            'birthday'          => 'date',
        ];
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birthday?->age;
    }


    public function isClient(): bool  { return $this->role === 'client'; }
    public function isDoctor(): bool  { return $this->role === 'doctor'; }
    public function isAdmin(): bool   { return $this->role === 'admin';  }


    public function doctorProfile(): HasOne
    {
        return $this->hasOne(DoctorProfile::class);
    }

    public function verificationRequestsReviewed(): HasMany
    {
        return $this->hasMany(VerificationRequest::class, 'reviewed_by');
    }


    public function appointmentsAsClient(): HasMany
    {
        return $this->hasMany(Appointment::class, 'client_id');
    }


    public function appointmentsAsDoctor(): HasManyThrough
    {
        return $this->hasManyThrough(
            Appointment::class,
            DoctorProfile::class,
            'user_id',
            'doctor_profile_id',
            'id',
            'id'
        );
    }


    public function schedules(): HasManyThrough
    {
        return $this->hasManyThrough(
            Schedule::class,
            DoctorProfile::class,
            'user_id',
            'doctor_profile_id',
            'id',
            'id'
        );
    }


    public function reviewsWritten(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id');
    }

}
