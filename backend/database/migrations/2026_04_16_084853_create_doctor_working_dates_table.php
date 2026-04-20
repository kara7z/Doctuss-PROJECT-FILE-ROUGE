<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('doctor_working_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_profile_id')
                  ->constrained('doctor_profiles')
                  ->cascadeOnDelete();
            $table->date('working_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index(['doctor_profile_id', 'working_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_working_dates');
    }
};
