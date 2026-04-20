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
            'city' => fake()->randomElement(['Casablanca', 'Rabat', 'Marrakech', 'Fes', 'Tangier', 'Agadir', 'Meknes', 'Oujda', 'Kenitra', 'Tetouan']),
            'phone_number' => fake()->phoneNumber(),
            
    
            'profile_picture' => 'https://randomuser.me/api/portraits/' . fake()->randomElement(['men', 'women']) . '/' . fake()->numberBetween(1, 99) . '.jpg',
            'banner_picture' => fake()->imageUrl(1200, 400, 'medical', true),
            
            'location_link' => fake()->boolean(70) ? 'https://maps.google.com/?q=' . fake()->latitude() . ',' . fake()->longitude() : null,
            'bio' => fake()->paragraphs(2, true),
            'is_verified' => true,
        ];
    }
}
