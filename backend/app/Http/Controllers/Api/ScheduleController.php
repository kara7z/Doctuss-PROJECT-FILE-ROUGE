<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ScheduleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Schedule::class);

        $query = Schedule::query()->with('doctorProfile.user:id,name,email');
        $doctorProfileId = $request->user()->doctorProfile?->id;

        if ($doctorProfileId) {
            $query->where('doctor_profile_id', $doctorProfileId);
        }

        return ScheduleResource::collection($query->orderBy('day_of_week')->orderBy('start_time')->get());
    }

    public function store(Request $request): ScheduleResource
    {
        $this->authorize('create', Schedule::class);

        $data = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $schedule = Schedule::query()->create([
            'doctor_profile_id' => $request->user()->doctorProfile->id,
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);

        return new ScheduleResource($schedule->load('doctorProfile.user:id,name,email'));
    }

    public function show(Schedule $schedule): ScheduleResource
    {
        $this->authorize('view', $schedule);

        return new ScheduleResource($schedule->load('doctorProfile.user:id,name,email'));
    }

    public function update(Request $request, Schedule $schedule): ScheduleResource
    {
        $this->authorize('update', $schedule);

        $data = $request->validate([
            'day_of_week' => ['sometimes', 'integer', 'between:0,6'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
        ]);

        if (isset($data['start_time'], $data['end_time']) && $data['start_time'] >= $data['end_time']) {
            abort(422, 'The end time must be after start time.');
        }

        $schedule->update($data);

        return new ScheduleResource($schedule->fresh()->load('doctorProfile.user:id,name,email'));
    }

    public function destroy(Schedule $schedule): Response
    {
        $this->authorize('delete', $schedule);
        $schedule->delete();

        return response()->noContent();
    }
}
