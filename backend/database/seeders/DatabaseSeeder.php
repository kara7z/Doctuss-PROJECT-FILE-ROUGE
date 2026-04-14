<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\DoctorCategory;
use App\Models\DoctorSpecialty;
use App\Models\DoctorProfile;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\Schedule;
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
            
            $profile = DoctorProfile::factory()->create([
                'user_id' => $doctor->id,
                'doctor_specialty_id' => $specialtyIds[array_rand($specialtyIds)],
                'profile_picture' => "https://randomuser.me/api/portraits/{$genderStr}/{$photoId}.jpg",
            ]);

            // Assign standard 9-5 schedule to random weekdays, and guarantee today is scheduled
            $daysToSchedule = collect([1, 2, 3, 4, 5, now()->dayOfWeek])->unique()->random(4);
            foreach ($daysToSchedule as $dayIndex) {
                Schedule::create([
                    'doctor_profile_id' => $profile->id,
                    'day_of_week'       => $dayIndex,
                    // If today, make it safely encompass now() so UI shows Available
                    'start_time'        => $dayIndex === now()->dayOfWeek ? '00:00:00' : '09:00:00',
                    'end_time'          => $dayIndex === now()->dayOfWeek ? '23:59:59' : '17:00:00',
                ]);
            }
        }
        
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
