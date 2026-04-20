<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\DoctorCategory;
use App\Models\DoctorSpecialty;
use App\Models\DoctorProfile;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\DoctorWorkingDate;
use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Enums\AppointmentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'role' => 'admin',
            'gender' => Gender::MALE,
            'birthday' => '1990-01-01',
            'status' => UserStatus::ACTIVE,
            'password' => 'password',
        ]);
        User::factory()->create([
            'name' => 'Client',
            'email' => 'client@gmail.com',
            'role' => 'client',
            'password' => 'password',
            'gender' => Gender::FEMALE,
            'birthday' => '1990-01-01',
            'status' => UserStatus::ACTIVE,
        ]);
        $doctorTestUser = User::factory()->create([
            'name' => 'Doctor',
            'email' => 'doctor@gmail.com',
            'role' => 'doctor',
            'gender' => Gender::FEMALE,
            'password' => 'password',
        ]);

        $categories = [
            'Primary Care' => ['General Practice', 'Internal Medicine', 'Family Medicine'],
            'Surgery' => ['General Surgery', 'Orthopedic Surgery', 'Neurosurgery'],
            'Pediatrics' => ['General Pediatrics', 'Pediatric Cardiology'],
            'Specialized Medicine' => ['Cardiology', 'Neurology', 'Dermatology', 'Psychiatry'],
            'Dentistry' => ['General Dentistry', 'Orthodontics'],
        ];

        $specialtyIds = [];
        foreach ($categories as $catName => $specs) {
            $category = DoctorCategory::create([
                'name' => $catName,
                'slug' => Str::slug($catName),
            ]);
            foreach ($specs as $specName) {
                $specialty = DoctorSpecialty::create([
                    'doctor_category_id' => $category->id,
                    'name' => $specName,
                    'slug' => Str::slug($specName),
                ]);
                $specialtyIds[] = $specialty->id;
            }
        }

        $doctors = User::factory()->count(50)->create([
            'role' => 'doctor',
        ]);

        $doctors->push($doctorTestUser);

        foreach ($doctors as $doctor) {
            $genderStr = $doctor->gender->value === 'female' ? 'women' : 'men';
            $photoId = rand(1, 99);
            
            // Medical-themed banner images from Unsplash
            $bannerImages = [
                'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=1200&h=400&fit=crop',
                'https://images.unsplash.com/photo-1538108149393-fbbd81895907?w=1200&h=400&fit=crop',
                'https://images.unsplash.com/photo-1516549655169-df83a0774514?w=1200&h=400&fit=crop',
                'https://images.unsplash.com/photo-1631217868264-e5b90bb7e133?w=1200&h=400&fit=crop',
                'https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=1200&h=400&fit=crop',
                'https://images.unsplash.com/photo-1666214280557-f1b5022eb634?w=1200&h=400&fit=crop',
                'https://images.unsplash.com/photo-1504813184591-01572f98c85f?w=1200&h=400&fit=crop',
                'https://images.unsplash.com/photo-1582719471384-894fbb16e074?w=1200&h=400&fit=crop',
            ];
            
            $profile = DoctorProfile::factory()->create([
                'user_id' => $doctor->id,
                'doctor_specialty_id' => $specialtyIds[array_rand($specialtyIds)],
                'profile_picture' => "https://randomuser.me/api/portraits/{$genderStr}/{$photoId}.jpg",
                'banner_picture' => $bannerImages[array_rand($bannerImages)],
            ]);

            // Assign random number of working days (3-7 days per week) with varied hours
            $numWorkingDays = rand(3, 7);
            
            // All possible days (0=Sunday, 1=Monday, ..., 6=Saturday)
            $allDays = [0, 1, 2, 3, 4, 5, 6];
            
            // Randomly select days
            $daysToSchedule = collect($allDays)->random($numWorkingDays);
            
            foreach ($daysToSchedule as $dayIndex) {
                // Weekdays (Mon-Fri) typically have longer hours
                if (in_array($dayIndex, [1, 2, 3, 4, 5])) {
                    $startHour = rand(7, 9); // Start between 7am-9am
                    $duration = rand(7, 10); // Work 7-10 hours
                } else {
                    // Weekends (Sat-Sun) typically have shorter hours
                    $startHour = rand(8, 10); // Start between 8am-10am
                    $duration = rand(4, 6); // Work 4-6 hours
                }
                
                $endHour = $startHour + $duration;
                
                Schedule::create([
                    'doctor_profile_id' => $profile->id,
                    'day_of_week'       => $dayIndex,
                    'start_time'        => sprintf('%02d:00:00', $startHour),
                    'end_time'          => sprintf('%02d:00:00', $endHour),
                ]);
            }

            // Assign at least 5 explicit working dates spread across the next 4 weeks
            $usedDates = [];
            $numDates   = rand(5, 8);
            for ($d = 0; $d < $numDates; $d++) {
                $daysAhead = rand(1, 28);
                $candidate = now()->addDays($daysAhead)->toDateString();
                if (in_array($candidate, $usedDates)) {
                    continue;
                }
                $usedDates[] = $candidate;
                $startHour = rand(8, 11);
                DoctorWorkingDate::create([
                    'doctor_profile_id' => $profile->id,
                    'working_date'      => $candidate,
                    'start_time'        => sprintf('%02d:00:00', $startHour),
                    'end_time'          => sprintf('%02d:00:00', $startHour + rand(4, 8)),
                ]);
            }
        } // end foreach ($doctors as $doctor)

        User::factory()->count(20)->create([
            'role' => 'client'
        ]);

        $clients = User::where('role', 'client')->get();
        $profiles = DoctorProfile::all();

        // 6. Generate random appointments and reviews
        for ($i = 0; $i < 150; $i++) {
            $client = $clients->random();
            $profile = $profiles->random();
            
            $appointment = Appointment::create([
                'client_id'         => $client->id,
                'doctor_profile_id' => $profile->id,
                'preferred_at'      => now()->subDays(rand(10, 60)),
                'proposed_at'       => now()->subDays(rand(10, 60)),
                'status'            => AppointmentStatus::CLOSED,
            ]);

            Review::create([
                'appointment_id' => $appointment->id,
                'user_id'        => $client->id,
                'rating'         => fake()->randomFloat(1, 3.5, 5.0),
                'comment'        => fake()->boolean(80) ? fake()->sentence(8) : null,
            ]);
        }
    }
}
