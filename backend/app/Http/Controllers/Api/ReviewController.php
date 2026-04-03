<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Appointment;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ReviewController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Review::class);

        $query = Review::query()->with([
            'user:id,name,email',
            'appointment:id,client_id,doctor_profile_id,status,preferred_at',
            'appointment.doctorProfile.user:id,name,email',
        ]);

        $user = $request->user();
        if ($user->isClient()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isDoctor()) {
            $query->whereHas('appointment', fn ($q) => $q->where('doctor_profile_id', $user->doctorProfile?->id ?? 0));
        }

        return ReviewResource::collection($query->latest()->paginate(15));
    }

    public function store(Request $request): ReviewResource
    {
        $data = $request->validate([
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ]);

        $appointment = Appointment::query()->findOrFail($data['appointment_id']);
        $this->authorize('create', [Review::class, $appointment]);

        $review = Review::query()->create([
            'appointment_id' => $appointment->id,
            'user_id' => $request->user()->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return new ReviewResource($review->load('user:id,name,email', 'appointment'));
    }

    public function show(Review $review): ReviewResource
    {
        $this->authorize('view', $review);

        return new ReviewResource($review->load([
            'user:id,name,email',
            'appointment:id,client_id,doctor_profile_id,status,preferred_at',
            'appointment.doctorProfile.user:id,name,email',
        ]));
    }

    public function destroy(Review $review): Response
    {
        $this->authorize('delete', $review);
        $review->delete();

        return response()->noContent();
    }
}
