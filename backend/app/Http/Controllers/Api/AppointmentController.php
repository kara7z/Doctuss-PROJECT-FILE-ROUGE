<?php

namespace App\Http\Controllers\Api;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AppointmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Appointment::class);

        $query = Appointment::query()
            ->with(['client:id,name,email', 'doctorProfile.user:id,name,email']);

        $user = $request->user();
        if ($user->isClient()) {
            $query->where('client_id', $user->id);
        } elseif ($user->isDoctor()) {
            $query->where('doctor_profile_id', $user->doctorProfile?->id ?? 0);
        }

        return AppointmentResource::collection($query->latest()->paginate(15));
    }

    public function store(Request $request): AppointmentResource
    {
        $this->authorize('create', Appointment::class);

        $data = $request->validate([
            'doctor_profile_id' => ['required', 'integer', 'exists:doctor_profiles,id'],
            'preferred_at' => ['required', 'date'],
            'proposed_at' => ['nullable', 'date'],
            'status' => ['nullable', 'in:' . implode(',', AppointmentStatus::values())],
        ]);

        $appointment = Appointment::query()->create([
            'client_id' => $request->user()->id,
            'doctor_profile_id' => $data['doctor_profile_id'],
            'preferred_at' => $data['preferred_at'],
            'proposed_at' => $data['proposed_at'] ?? null,
            'status' => $data['status'] ?? AppointmentStatus::PENDING->value,
        ]);

        return new AppointmentResource($appointment->load(['client:id,name,email', 'doctorProfile.user:id,name,email']));
    }

    public function show(Appointment $appointment): AppointmentResource
    {
        $this->authorize('view', $appointment);

        return new AppointmentResource(
            $appointment->load(['client:id,name,email', 'doctorProfile.user:id,name,email', 'review'])
        );
    }

    public function update(Request $request, Appointment $appointment): AppointmentResource
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'preferred_at' => ['sometimes', 'date'],
            'proposed_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'in:' . implode(',', AppointmentStatus::values())],
        ]);

        $appointment->update($data);

        return new AppointmentResource(
            $appointment->fresh()->load(['client:id,name,email', 'doctorProfile.user:id,name,email'])
        );
    }

    public function destroy(Appointment $appointment): Response
    {
        $this->authorize('delete', $appointment);
        $appointment->delete();

        return response()->noContent();
    }
}
