<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function doctorProfiles(): HasManyThrough
    {
        return $this->hasManyThrough(
            DoctorProfile::class,
            DoctorSpecialty::class,
            'doctor_category_id',
            'doctor_specialty_id',
            'id',
            'id'
        );
    }

    public function specialties(): HasMany
    {
        return $this->hasMany(DoctorSpecialty::class, 'doctor_category_id');
    }
}
