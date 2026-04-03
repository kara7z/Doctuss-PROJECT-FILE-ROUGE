<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'appointment_id',
        'user_id',
        'rating',
        'comment',
    ];

    protected static function booted(): void
    {
        static::saved(function (Review $review) {
            self::recalculate((int) $review->appointment?->doctor_profile_id);
        });

        static::deleted(function (Review $review) {
            self::recalculate((int) $review->appointment?->doctor_profile_id);
        });
    }

    private static function recalculate(int $doctorProfileId): void
    {
        if ($doctorProfileId <= 0) {
            return;
        }

        $avg = Review::query()
            ->whereHas('appointment', fn ($query) => $query->where('doctor_profile_id', $doctorProfileId))
            ->avg('rating');

        DoctorProfile::whereKey($doctorProfileId)->update([
            'avg_rating'    => $avg !== null ? round((float) $avg, 2) : 0,
        ]);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
