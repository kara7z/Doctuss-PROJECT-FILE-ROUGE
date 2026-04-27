<?php

namespace App\Http\Controllers\Api;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Appointment::class);

        $query = Appointment::query()
            ->with(['client:id,name,email,birthday,gender', 'doctorProfile.user:id,name,email', 'review']);

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

        $existingActiveAppointment = Appointment::query()
            ->where('client_id', $request->user()->id)
            ->where('doctor_profile_id', $data['doctor_profile_id'])
            ->whereIn('status', [AppointmentStatus::PENDING->value, AppointmentStatus::APPROVED->value])
            ->exists();

        if ($existingActiveAppointment) {
            throw ValidationException::withMessages([
                'doctor_profile_id' => [__('You can only have one active appointment per doctor at a time.')]
            ]);
        }

        $preferredAt = Carbon::parse($data['preferred_at']);
        if ($preferredAt->lt(now()->addHour())) {
            throw ValidationException::withMessages([
                'preferred_at' => ['appointment.must_be_one_hour_ahead']
            ]);
        }

        $userId = $request->user()->id;
        $conflict = Appointment::query()
            ->where('doctor_profile_id', $data['doctor_profile_id'])
            ->where(function ($query) use ($preferredAt) {
                $query->where('preferred_at', $preferredAt)
                      ->orWhere('proposed_at', $preferredAt);
            })
            ->where(function ($query) use ($userId) {
                $query->where('status', AppointmentStatus::APPROVED->value)
                      ->orWhere(function ($q2) use ($userId) {
                          $q2->where('status', AppointmentStatus::PENDING)
                             ->where('client_id', $userId);
                      });
            })
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'preferred_at' => [__('This time slot is already booked by you or is already approved.')]
            ]);
        }

        $appointment = Appointment::query()->create([
            'client_id' => $request->user()->id,
            'doctor_profile_id' => $data['doctor_profile_id'],
            'preferred_at' => $data['preferred_at'],
            'proposed_at' => $data['proposed_at'] ?? null,
            'status' => $data['status'] ?? AppointmentStatus::PENDING->value,
        ]);

        return new AppointmentResource($appointment->load(['client:id,name,email,birthday,gender', 'doctorProfile.user:id,name,email']));
    }

    public function show(Appointment $appointment): AppointmentResource
    {
        $this->authorize('view', $appointment);

        return new AppointmentResource(
            $appointment->load(['client:id,name,email,birthday,gender', 'doctorProfile.user:id,name,email', 'review'])
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

        $targetStatus = $data['status'] ?? ($appointment->status?->value ?? $appointment->status);
        if ($targetStatus === AppointmentStatus::APPROVED->value) {
            $approvedAt = isset($data['proposed_at']) && $data['proposed_at']
                ? Carbon::parse($data['proposed_at'])
                : ($appointment->proposed_at ?: $appointment->preferred_at);

            if ($approvedAt && $approvedAt->lt(now()->addHour())) {
                throw ValidationException::withMessages([
                    'proposed_at' => ['appointment.must_be_one_hour_ahead']
                ]);
            }

            if ($approvedAt) {
                $conflict = Appointment::query()
                    ->where('doctor_profile_id', $appointment->doctor_profile_id)
                    ->where('id', '!=', $appointment->id)
                    ->where('status', AppointmentStatus::APPROVED->value)
                    ->where(function ($query) use ($approvedAt) {
                        $query->where('proposed_at', $approvedAt)
                              ->orWhere(function ($q2) use ($approvedAt) {
                                  $q2->whereNull('proposed_at')
                                     ->where('preferred_at', $approvedAt);
                              });
                    })
                    ->exists();

                if ($conflict) {
                    throw ValidationException::withMessages([
                        'proposed_at' => [__('This time slot is already approved for another client.')]
                    ]);
                }
            }
        }

        $appointment->update($data);

        return new AppointmentResource(
            $appointment->fresh()->load(['client:id,name,email,birthday,gender', 'doctorProfile.user:id,name,email'])
        );
    }

    public function destroy(Appointment $appointment): Response
    {
        $this->authorize('delete', $appointment);
        $appointment->delete();

        return response()->noContent();
    }
}
