<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();

            // Academic scope
            $table->string('faculty_code', 20)->nullable();
            $table->string('major_code', 20)->nullable();
            $table->string('batch', 20)->nullable();
            $table->unsignedTinyInteger('semester')->nullable();

            // Main API
            $table->boolean('api_active')->default(true);

            // Application tabs / features
            $table->boolean('fee_active')->default(true);
            $table->boolean('result_active')->default(true);
            $table->boolean('timetable_active')->default(true);

            // API data availability
            $table->boolean('show_result')->default(true);
            $table->boolean('show_timetable')->default(true);

            $table->integer('user_id')->nullable()->default(0);

            $table->timestamps();


            /*
            |----------------------------------------------------------------------
            | Prevent duplicate configuration
            |----------------------------------------------------------------------
            |
            | One configuration for each Faculty + Major + Batch + Semester.
            |
            */
            $table->unique(
                [
                    'faculty_code',
                    'major_code',
                    'batch',
                    'semester'
                ],
                'system_settings_academic_unique'
            );

            $table->index('faculty_code');
            $table->index('major_code');
            $table->index('batch');
            $table->index('semester');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
