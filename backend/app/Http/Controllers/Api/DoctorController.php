<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DoctorSpecialty;
use App\Http\Resources\DoctorResource;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $doctors = User::where('role', 'doctor')
            ->whereHas('doctorProfile', function ($query) {
                $query->where('is_verified', true);
            })
            ->with(['doctorProfile' => function ($query) {
                $query->withCount('reviews')
                      ->with(['specialty.category', 'schedules', 'appointments' => function($q) {
                          $q->where('status', 'approved');
                      }]);
            }])
            ->get();

        return DoctorResource::collection($doctors);
    }

    public function specialties()
    {
        return response()->json([
            'data' => DoctorSpecialty::all()
        ]);
    }
}
