<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorSpecialty extends Model
{
    protected $fillable = [
        'doctor_category_id',
        'name',
        'slug',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DoctorCategory::class, 'doctor_category_id');
    }

    public function doctorProfiles(): HasMany
    {
        return $this->hasMany(DoctorProfile::class);
    }
}
