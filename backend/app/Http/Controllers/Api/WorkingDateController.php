<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorWorkingDate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WorkingDateController extends Controller
{
    public function store(Request $request): Response
    {
        $this->authorize('create', DoctorWorkingDate::class);

        $data = $request->validate([
            'working_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        DoctorWorkingDate::query()->create([
            'doctor_profile_id' => $request->user()->doctorProfile->id,
            'working_date' => $data['working_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);

        return response()->noContent();
    }
}
