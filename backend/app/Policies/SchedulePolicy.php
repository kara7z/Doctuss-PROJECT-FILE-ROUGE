<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
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
        return true;
    }


    public function view(User $user, Schedule $schedule): bool
    {
        return true;
    }


    public function create(User $user): bool
    {
        return $user->isDoctor()
            && $user->doctorProfile?->is_verified === true;
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $user->isDoctor()
            && $user->id === $schedule->doctorProfile?->user_id;
    }


    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->isDoctor()
            && $user->id === $schedule->doctorProfile?->user_id;
    }
}
