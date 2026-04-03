<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminModerationController extends Controller
{
    public function suspendUser(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json([
                'message' => 'You cannot suspend your own account.',
            ], 422);
        }

        if ($user->isAdmin()) {
            return response()->json([
                'message' => 'Admin accounts cannot be suspended from this endpoint.',
            ], 422);
        }

        $user->update([
            'status' => UserStatus::SUSPENDED,
        ]);
        $user->tokens()->delete();

        return response()->json([
            'message' => 'User suspended successfully.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    public function activateUser(User $user): JsonResponse
    {
        $user->update([
            'status' => UserStatus::ACTIVE,
        ]);

        return response()->json([
            'message' => 'User activated successfully.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    public function verifyDoctor(DoctorProfile $doctorProfile): JsonResponse
    {
        $doctorProfile->update([
            'is_verified' => true,
        ]);

        return response()->json([
            'message' => 'Doctor verified successfully.',
            'doctor_profile' => $doctorProfile->fresh()->load('user:id,name,email,role,status', 'specialty:id,name,slug'),
        ]);
    }

    public function unverifyDoctor(DoctorProfile $doctorProfile): JsonResponse
    {
        $doctorProfile->update([
            'is_verified' => false,
        ]);

        return response()->json([
            'message' => 'Doctor verification removed successfully.',
            'doctor_profile' => $doctorProfile->fresh()->load('user:id,name,email,role,status', 'specialty:id,name,slug'),
        ]);
    }
}
