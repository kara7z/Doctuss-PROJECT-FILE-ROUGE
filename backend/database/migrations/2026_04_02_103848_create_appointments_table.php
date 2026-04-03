<?php

use App\Enums\AppointmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('doctor_profile_id')
                  ->constrained('doctor_profiles')
                  ->cascadeOnDelete();
            $table->dateTime('preferred_at');
            $table->dateTime('proposed_at')->nullable();
            $table->enum('status', AppointmentStatus::values())->default(AppointmentStatus::PENDING->value);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
