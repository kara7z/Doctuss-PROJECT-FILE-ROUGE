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
    public function getUsers(Request $request): JsonResponse
    {
        $query = User::query()->with(['doctorProfile.specialty.category']);

        if ($request->has('role') && in_array($request->input('role'), ['client', 'doctor', 'admin'])) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status->value,
                    'gender' => $user->gender?->value,
                    'birthday' => $user->birthday,
                    'age' => $user->age,
                    'created_at' => $user->created_at,
                    'doctor_profile' => $user->doctorProfile ? [
                        'id' => $user->doctorProfile->id,
                        'is_verified' => $user->doctorProfile->is_verified,
                        'phone_number' => $user->doctorProfile->phone_number,
                        'city' => $user->doctorProfile->city,
                        'specialty' => $user->doctorProfile->specialty ? [
                            'id' => $user->doctorProfile->specialty->id,
                            'name' => $user->doctorProfile->specialty->name,
                            'category' => $user->doctorProfile->specialty->category ? [
                                'id' => $user->doctorProfile->specialty->category->id,
                                'name' => $user->doctorProfile->specialty->category->name,
                            ] : null,
                        ] : null,
                    ] : null,
                ];
            }),
        ]);
    }
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
