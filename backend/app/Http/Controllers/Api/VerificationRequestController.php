<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\VerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isDoctor()) {
            return response()->json([
                'message' => 'Only doctors can submit verification requests.',
            ], 403);
        }

        if (! $user->doctorProfile) {
            return response()->json([
                'message' => 'Doctor profile is required before submitting verification.',
            ], 422);
        }

        $data = $request->validate([
            'document' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        // Store the uploaded file
        $path = $data['document']->store('verification-documents', 'public');

        $verificationRequest = VerificationRequest::query()->create([
            'doctor_profile_id' => $user->doctorProfile->id,
            'proposed_at' => now(),
            'status' => RequestStatus::PENDING,
            'document_path' => $path,
        ]);

        return response()->json([
            'message' => 'Verification request submitted.',
            'request' => $verificationRequest,
        ], 201);
    }

    public function index(Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Only admins can view verification requests.',
            ], 403);
        }

        return VerificationRequest::query()
            ->with([
                'doctorProfile:id,user_id,doctor_specialty_id,is_verified',
                'doctorProfile.user:id,name,email,role,status',
                'doctorProfile.specialty:id,doctor_category_id,name,slug',
                'doctorProfile.specialty.category:id,name,slug',
                'reviewer:id,name,email',
            ])
            ->latest()
            ->get();
    }

    public function approve(Request $request, VerificationRequest $verificationRequest): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Only admins can approve verification requests.',
            ], 403);
        }

        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $verificationRequest->update([
            'status' => RequestStatus::APPROVED,
            'admin_notes' => $data['admin_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $verificationRequest->doctorProfile()->update([
            'is_verified' => true,
        ]);

        return response()->json([
            'message' => 'Verification request approved.',
            'request' => $verificationRequest->fresh(),
        ]);
    }

    public function reject(Request $request, VerificationRequest $verificationRequest): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Only admins can reject verification requests.',
            ], 403);
        }

        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $verificationRequest->update([
            'status' => RequestStatus::REJECTED,
            'admin_notes' => $data['admin_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $verificationRequest->doctorProfile()->update([
            'is_verified' => false,
        ]);

        return response()->json([
            'message' => 'Verification request rejected.',
            'request' => $verificationRequest->fresh(),
        ]);
    }
}

