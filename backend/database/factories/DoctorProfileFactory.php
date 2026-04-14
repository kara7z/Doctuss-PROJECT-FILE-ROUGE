<?php

namespace Database\Factories;

use App\Models\DoctorProfile;
use App\Models\User;
use App\Models\DoctorSpecialty;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorProfileFactory extends Factory
{
    protected $model = DoctorProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'doctor_specialty_id' => 1,
            
            'experience_start_date' => fake()->dateTimeBetween('-30 years', '-1 year'),
            'hospital_name' => fake()->company() . ' Hospital',
            'city' => fake()->city(),
            'phone_number' => fake()->phoneNumber(),
            
    
            'profile_picture' => 'https://randomuser.me/api/portraits/' . fake()->randomElement(['men', 'women']) . '/' . fake()->numberBetween(1, 99) . '.jpg',
            'banner_picture' => fake()->imageUrl(1200, 400, 'medical', true),
            
            'hospital_lat' => fake()->latitude(),
            'hospital_lng' => fake()->longitude(),
            'bio' => fake()->paragraphs(2, true),
            'is_verified' => true,
        ];
    }
}
