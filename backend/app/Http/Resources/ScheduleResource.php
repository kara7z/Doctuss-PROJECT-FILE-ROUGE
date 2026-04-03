<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $doctorProfile = $this->relationLoaded('doctorProfile') ? $this->doctorProfile : null;
        $doctorUser = $doctorProfile && $doctorProfile->relationLoaded('user') ? $doctorProfile->user : null;

        return [
            'id' => $this->id,
            'doctor_profile_id' => $this->doctor_profile_id,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'doctor' => $doctorUser ? [
                    'id' => $doctorUser->id,
                    'name' => $doctorUser->name,
                    'email' => $doctorUser->email,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
