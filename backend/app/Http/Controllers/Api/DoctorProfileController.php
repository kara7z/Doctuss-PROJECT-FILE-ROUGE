<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DoctorProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isDoctor()) {
            return response()->json(['message' => 'Only doctors can access this endpoint'], 403);
        }

        if (!$user->doctorProfile) {
            return response()->json(['message' => 'Doctor profile not found'], 404);
        }

        $profile = $user->doctorProfile->load([
            'specialty.category',
            'schedules',
            'verificationRequests' => function($q) {
                $q->latest()->limit(5);
            }
        ]);

        return response()->json([
            'data' => [
                'id' => $profile->id,
                'user_id' => $profile->user_id,
                'doctor_specialty_id' => $profile->doctor_specialty_id,
                'experience_start_date' => $profile->experience_start_date,
                'hospital_name' => $profile->hospital_name,
                'city' => $profile->city,
                'phone_number' => $profile->phone_number,
                'profile_picture' => $profile->profile_picture,
                'banner_picture' => $profile->banner_picture,
                'location_link' => $profile->location_link,
                'bio' => $profile->bio,
                'is_verified' => $profile->is_verified,
                'avg_rating' => $profile->avg_rating,
                'specialty' => $profile->specialty,
                'verification_requests' => $profile->verificationRequests,
            ]
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isDoctor()) {
            return response()->json(['message' => 'Only doctors can update their profile'], 403);
        }

        if (!$user->doctorProfile) {
            return response()->json(['message' => 'Doctor profile not found'], 404);
        }

        $data = $request->validate([
            'profile_picture' => ['nullable', 'string', 'max:2048'],
            'banner_picture' => ['nullable', 'string', 'max:2048'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'location_link' => ['nullable', 'url', 'max:2048'],
        ]);

        $user->doctorProfile->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $user->doctorProfile->fresh()
        ]);
    }
}
