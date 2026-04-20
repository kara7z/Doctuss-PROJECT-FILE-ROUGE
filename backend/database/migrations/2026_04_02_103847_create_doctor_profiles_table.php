<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_specialty_id')->constrained('doctor_specialties')->cascadeOnDelete();
            $table->date('experience_start_date');
            $table->string('hospital_name');
            $table->string('city');
            $table->string('phone_number')->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('banner_picture')->nullable();
            $table->string('location_link')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_profiles');
    }
};
