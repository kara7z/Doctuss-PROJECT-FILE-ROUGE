<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DoctorSpecialty;
use App\Models\DoctorCategory;
use App\Http\Resources\DoctorResource;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'doctor')
            ->whereHas('doctorProfile');

        if ($request->has('q') && !empty($request->input('q'))) {
            $searchTerm = $request->input('q');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('doctorProfile', function ($dp) use ($searchTerm) {
                      $dp->where('city', 'like', '%' . $searchTerm . '%')
                         ->orWhereHas('specialty', function ($spec) use ($searchTerm) {
                             $spec->where('name', 'like', '%' . $searchTerm . '%')
                                  ->orWhereHas('category', function ($cat) use ($searchTerm) {
                                      $cat->where('name', 'like', '%' . $searchTerm . '%');
                                  });
                         });
                  });
            });
        }

        if ($request->has('gender') && in_array($request->input('gender'), ['male', 'female'])) {
            $query->where('gender', $request->input('gender'));
        }

        if ($request->has('city') && !empty($request->input('city'))) {
            $city = $request->input('city');
            $query->whereHas('doctorProfile', function ($dp) use ($city) {
                $dp->where('city', 'like', '%' . $city . '%');
            });
        }

        if ($request->has('experience') && is_numeric($request->input('experience'))) {
            $years = (int)$request->input('experience');
            $cutoffDate = now()->subYears($years);
            $query->whereHas('doctorProfile', function ($dp) use ($cutoffDate) {
                $dp->where('experience_start_date', '<=', $cutoffDate);
            });
        }

        if ($request->has('specialties')) {
            $specialties = $request->input('specialties');
            if (is_string($specialties)) {
                $specialties = explode(',', $specialties);
            }
            if (is_array($specialties) && count($specialties) > 0) {
                $query->whereHas('doctorProfile', function ($dp) use ($specialties) {
                    $dp->whereIn('doctor_specialty_id', $specialties);
                });
            }
        }

        if ($request->has('categories')) {
            $categories = $request->input('categories');
            if (is_string($categories)) {
                $categories = explode(',', $categories);
            }
            if (is_array($categories) && count($categories) > 0) {
                $query->whereHas('doctorProfile.specialty', function ($spec) use ($categories) {
                    $spec->whereIn('doctor_category_id', $categories);
                });
            }
        }

        if ($request->has('status') && in_array($request->input('status'), ['Available', 'Busy', 'Unavailable'])) {
            $status = $request->input('status');
            $now = now();
            $todayStr = $now->toDateString();
            $currentTime = $now->format('H:i:s');
            $todayIndex = $now->dayOfWeek;

            if ($status === 'Unavailable') {
                $query->whereHas('doctorProfile', function ($dp) use ($todayStr, $currentTime, $todayIndex) {
                    $dp->whereDoesntHave('workingDates', function ($wd) use ($todayStr, $currentTime) {
                        $wd->whereDate('working_date', $todayStr)
                           ->where('start_time', '<=', $currentTime)
                           ->where('end_time', '>', $currentTime);
                    })->whereDoesntHave('schedules', function ($s) use ($todayIndex, $currentTime) {
                        $s->where('day_of_week', $todayIndex)
                          ->where('start_time', '<=', $currentTime)
                          ->where('end_time', '>', $currentTime);
                    });
                });
            } else {
                $query->whereHas('doctorProfile', function ($dp) use ($todayStr, $currentTime, $todayIndex, $now, $status) {
                    $dp->where(function ($q) use ($todayStr, $currentTime, $todayIndex) {
                        $q->whereHas('workingDates', function ($wd) use ($todayStr, $currentTime) {
                            $wd->whereDate('working_date', $todayStr)
                               ->where('start_time', '<=', $currentTime)
                               ->where('end_time', '>', $currentTime);
                        })->orWhereHas('schedules', function ($s) use ($todayIndex, $currentTime) {
                            $s->where('day_of_week', $todayIndex)
                              ->where('start_time', '<=', $currentTime)
                              ->where('end_time', '>', $currentTime);
                        });
                    });

                    if ($status === 'Available') {
                        $dp->whereDoesntHave('appointments', function ($appt) use ($now) {
                            $appt->where('status', 'approved')
                                 ->whereNotNull('proposed_at')
                                 ->where('proposed_at', '<=', $now)
                                 ->where('proposed_at', '>=', $now->copy()->subHour());
                        });
                    } elseif ($status === 'Busy') {
                        $dp->whereHas('appointments', function ($appt) use ($now) {
                            $appt->where('status', 'approved')
                                 ->whereNotNull('proposed_at')
                                 ->where('proposed_at', '<=', $now)
                                 ->where('proposed_at', '>=', $now->copy()->subHour());
                        });
                    }
                });
            }
        }

        $perPage = $request->input('per_page', 9);
        
        $doctors = $query->with(['doctorProfile' => function ($query) {
            $query->withCount('reviews')
                  ->with(['specialty.category', 'schedules', 'reviews.user', 'appointments' => function($q) {
                      $q->whereIn('status', ['approved', 'pending']);
                  }]);
        }])->paginate($perPage);

        return DoctorResource::collection($doctors);
    }

    public function show(User $user)
    {
        if ($user->role !== 'doctor') {
            return response()->json(['message' => 'User is not a doctor'], 404);
        }

        if (!$user->doctorProfile) {
            return response()->json(['message' => 'Doctor profile not found'], 404);
        }

        $user->load(['doctorProfile' => function ($query) {
            $query->withCount('reviews')
                  ->with([
                      'specialty.category', 
                      'schedules' => function($q) {
                          $q->orderBy('day_of_week');
                      }, 
                      'workingDates', 
                      'reviews.user', 
                      'appointments' => function($q) {
                          $q->whereIn('status', ['approved', 'pending']);
                      }
                  ]);
        }]);

        return new DoctorResource($user);
    }

    public function specialties()
    {
        return response()->json([
            'data' => DoctorSpecialty::all()
        ]);
    }

    public function categories()
    {
        return response()->json([
            'data' => DoctorCategory::orderBy('name')->get()
        ]);
    }

    public function cities()
    {
        return response()->json([
            'data' => \App\Models\DoctorProfile::distinct()
                ->orderBy('city')
                ->pluck('city')
                ->filter()
                ->values()
        ]);
    }
}
