<?php

namespace App\Policies;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{

    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }


    public function viewAny(User $user): bool
    {
        return $user->isClient() || $user->isDoctor();
    }


    public function view(User $user, Review $review): bool
    {
        $appointment = $review->appointment;

        if (! $appointment) {
            return false;
        }

        return $user->id === $review->user_id
            || $user->id === $appointment->client_id
            || $user->id === $appointment->doctorProfile?->user_id;
    }


    public function create(User $user, Appointment $appointment): bool
    {
        return $user->isClient()
            && $user->id === $appointment->client_id
            && $appointment->status === AppointmentStatus::CLOSED
            && $appointment->review === null;
    }

    public function update(User $user, Review $review): bool
    {
        if ($review->created_at === null) {
            return false;
        }

        return $user->id === $review->user_id
            && $review->created_at->diffInHours(now()) <= 48;
    }


    public function delete(User $user, Review $review): bool
    {
        return false;
    }
}
