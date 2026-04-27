<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $doctorProfile = $this->relationLoaded('doctorProfile') ? $this->doctorProfile : null;
        $doctorUser = $doctorProfile && $doctorProfile->relationLoaded('user') ? $doctorProfile->user : null;

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'doctor_profile_id' => $this->doctor_profile_id,
            'preferred_at' => $this->preferred_at?->format('Y-m-d H:i:s'),
            'proposed_at' => $this->proposed_at?->format('Y-m-d H:i:s'),
            'status' => $this->status?->value ?? $this->status,
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'email' => $this->client->email,
                    'age' => $this->client->age,
                    'gender' => $this->client->gender?->value ?? $this->client->gender,
                ];
            }),
            'doctor_profile' => $doctorProfile ? [
                'id' => $doctorProfile->id,
                'user' => $doctorUser ? [
                    'id' => $doctorUser->id,
                    'name' => $doctorUser->name,
                    'email' => $doctorUser->email,
                ] : null,
            ] : null,
            'review' => $this->whenLoaded('review'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
