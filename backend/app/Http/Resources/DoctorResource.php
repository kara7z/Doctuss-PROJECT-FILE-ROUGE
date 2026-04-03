<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->whenLoaded('doctorProfile');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'gender' => $this->gender?->value ?? $this->gender,
            'birthday' => $this->birthday,
            'doctor_profile' => $this->whenLoaded('doctorProfile', function () use ($profile) {
                $specialty = $profile->relationLoaded('specialty') ? $profile->specialty : null;
                $category = $specialty && $specialty->relationLoaded('category') ? $specialty->category : null;

                return [
                    'id' => $profile->id,
                    'doctor_specialty_id' => $profile->doctor_specialty_id,
                    'experience_start_date' => $profile->experience_start_date,
                    'hospital_name' => $profile->hospital_name,
                    'city' => $profile->city,
                    'phone_number' => $profile->phone_number,
                    'profile_picture' => $profile->profile_picture,
                    'banner_picture' => $profile->banner_picture,
                    'hospital_lat' => $profile->hospital_lat,
                    'hospital_lng' => $profile->hospital_lng,
                    'bio' => $profile->bio,
                    'is_verified' => $profile->is_verified,
                    'avg_rating' => $profile->avg_rating,
                    'specialty' => $specialty ? [
                        'id' => $specialty->id,
                        'name' => $specialty->name,
                        'slug' => $specialty->slug,
                        'category' => $category ? [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                        ] : null,
                    ] : null,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
