<?php

namespace App\Policies;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{

    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null; // continue to the specific method
    }


    public function viewAny(User $user): bool
    {
        return $user->isClient() || $user->isDoctor();
    }


    public function view(User $user, Appointment $appointment): bool
    {
        $doctorUserId = $appointment->doctorProfile?->user_id;

        return $user->id === $appointment->client_id
            || $user->id === $doctorUserId;
    }


    public function create(User $user): bool
    {
        return $user->isClient();
    }


    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->id !== $appointment->doctorProfile?->user_id) {
            return false;
        }

        return ! in_array($appointment->status, [
            AppointmentStatus::CLOSED,
            AppointmentStatus::CANCELLED,
        ], true);
    }


    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->client_id
            && $appointment->status === AppointmentStatus::PENDING;
    }


    public function restore(User $user, Appointment $appointment): bool
    {
        return false;
    }


    public function close(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->doctorProfile?->user_id
            && $appointment->status === AppointmentStatus::APPROVED;
    }
}
