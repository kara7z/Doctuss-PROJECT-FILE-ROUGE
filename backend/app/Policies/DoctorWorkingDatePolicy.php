<?php

namespace App\Policies;

use App\Models\DoctorWorkingDate;
use App\Models\User;

class DoctorWorkingDatePolicy
{
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function create(User $user): bool
    {
        return $user->isDoctor()
            && $user->doctorProfile?->is_verified === true;
    }
}
