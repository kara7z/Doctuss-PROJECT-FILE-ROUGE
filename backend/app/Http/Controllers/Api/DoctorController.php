<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query()
            ->where('role', 'doctor')
            ->whereHas('doctorProfile', fn ($q) => $q->where('is_verified', true))
            ->with([
                'doctorProfile.specialty.category',
            ]);

        return DoctorResource::collection($query->latest()->paginate(15));
    }

    public function show(User $user): DoctorResource
    {
        abort_unless($user->isDoctor() && $user->doctorProfile, 404);

        return new DoctorResource(
            $user->load('doctorProfile.specialty.category')
        );
    }
}

