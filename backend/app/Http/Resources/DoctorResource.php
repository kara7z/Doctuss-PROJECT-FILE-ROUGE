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
            'status' => $this->status?->value ?? $this->status,
            'gender' => $this->gender?->value ?? $this->gender,
            'birthday' => $this->birthday,
            'age' => $this->age,
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
                    'location_link' => $profile->location_link,
                    'bio' => $profile->bio,
                    'current_status' => $profile->current_status,
                    'is_verified' => $profile->is_verified,
                    'avg_rating' => $profile->avg_rating,
                    'reviews_count' => $profile->reviews_count ?? 0,
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
                    'schedules' => $profile->relationLoaded('schedules') ? $profile->schedules->map(fn($s) => [
                        'id' => $s->id,
                        'day_of_week' => $s->day_of_week,
                        'day_name' => $s->day_name,
                        'start_time' => $s->start_time,
                        'end_time' => $s->end_time,
                    ]) : null,
                    'appointments' => $profile->relationLoaded('appointments') ? $profile->appointments->map(fn($a) => [
                        'id' => $a->id,
                        'client_id' => $a->client_id,
                        'preferred_at' => $a->preferred_at?->format('Y-m-d H:i:s'),
                        'proposed_at' => $a->proposed_at?->format('Y-m-d H:i:s'),
                        'status' => $a->status->value,
                    ]) : null,
                    'reviews' => $profile->relationLoaded('reviews') ? $profile->reviews->map(fn($r) => [
                        'id' => $r->id,
                        'rating' => $r->rating,
                        'comment' => $r->comment,
                        'created_at' => $r->created_at,
                        'user' => $r->relationLoaded('user') && $r->user ? [
                            'id' => $r->user->id,
                            'name' => $r->user->name,
                            'email' => $r->user->email,
                        ] : null,
                    ])->sortByDesc('created_at')->values() : null,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
