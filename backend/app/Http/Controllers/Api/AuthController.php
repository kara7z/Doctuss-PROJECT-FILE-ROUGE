<?php

namespace App\Http\Controllers\Api;

use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:client,doctor'],
            'gender' => ['required', 'in:' . implode(',', \App\Enums\Gender::values())],
            'birthday' => ['required', 'date', $request->role === 'doctor' ? 'before:-18 years' : 'before:today'],
        ]);

        if ($data['role'] === 'doctor') {
            $doctorData = $request->validate([
                'doctor_specialty_id' => ['required', 'exists:doctor_specialties,id'],
                'experience_start_date' => ['required', 'date', 'before:today', 'after:birthday'],
                'hospital_name' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
                'phone_number' => ['required', 'string', 'max:20'],
                'bio' => ['required', 'string'],
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'status' => UserStatus::ACTIVE,
            'gender' => $data['gender'],
            'birthday' => $data['birthday'],
        ]);

        if ($data['role'] === 'doctor') {
            \App\Models\DoctorProfile::create([
                'user_id' => $user->id,
                'doctor_specialty_id' => $doctorData['doctor_specialty_id'],
                'experience_start_date' => $doctorData['experience_start_date'],
                'hospital_name' => $doctorData['hospital_name'],
                'city' => $doctorData['city'],
                'phone_number' => $doctorData['phone_number'],
                'bio' => $doctorData['bio'],
                'is_verified' => false,
            ]);
        }

        Auth::login($user);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registered successfully.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status === UserStatus::SUSPENDED) {
            return response()->json([
                'message' => 'Your account is suspended.',
            ], 403);
        }

        Auth::login($user);

        $token = $user->createToken('auth-token')->plainTextToken;
        return response()->json([
            'message' => 'Logged in successfully.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
