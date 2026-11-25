<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Templates: ordered messages grouped into named sets
        | (e.g. "Google Testers", "Weekly Reminders")
        |--------------------------------------------------------------------------
        */

        Schema::create('campaign_templates', function (Blueprint $table) {
            $table->id();

            $table->string('template_group', 100);
            $table->unsignedSmallInteger('sequence');

            $table->string('type', 100)->default('general');
            $table->string('title');
            $table->text('body');

            $table->unique(['template_group', 'sequence']);
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Scheduled campaigns: send the next template of a set daily or weekly
        |--------------------------------------------------------------------------
        */

        Schema::create('scheduled_campaigns', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('template_group', 100);

            // Audience: indexes | all | group
            $table->string('audience', 20)->default('indexes');
            $table->json('student_indexes')->nullable();
            $table->string('faculty_code', 50)->nullable();
            $table->string('major_code', 50)->nullable();
            $table->string('batch', 50)->nullable();
            $table->unsignedTinyInteger('semester')->nullable();

            // Schedule: daily | weekly (weekday 0 = Sunday .. 6 = Saturday)
            $table->string('frequency', 10)->default('daily');
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->date('start_date');
            $table->string('send_time', 5)->default('13:30');
            $table->string('timezone', 64)->default('Africa/Khartoum');
            $table->unsignedSmallInteger('total_sends')->default(14);

            // active | paused | completed
            $table->string('status', 20)->default('active');
            $table->unsignedSmallInteger('sent_count')->default(0);
            $table->date('last_sent_date')->nullable();
            $table->json('last_result')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | Default "Google Testers" set: 14 daily messages (editable in admin)
        |--------------------------------------------------------------------------
        */

        $templates = [
            ['Welcome, Tester!', 'Thank you for joining the Student Desk testing program. Open the app today and explore your profile.'],
            ['Check Your Timetable', 'Day {number} of {total}: open Student Desk and check your timetable. Tell us if anything looks wrong.'],
            ['Your Results', 'Day {number}: take a look at the Results section and make sure everything loads correctly.'],
            ['Fees Overview', 'Day {number}: open the Fees section and review your fee details. Your feedback helps us improve.'],
            ['Notifications Test', 'Day {number}: tap this notification and mark it as read. Does it stay read? Let us know.'],
            ['Explore the App', 'Day {number}: spend a minute exploring Student Desk. Every visit helps us test the app.'],
            ['One Week Done!', 'You completed one week of testing. Thank you! Please keep opening the app every day.'],
            ['Halfway There', 'Day {number} of {total}: we are past the halfway mark. Open the app and check for updates.'],
            ['Profile Check', 'Day {number}: verify that your profile information is correct in Student Desk.'],
            ['Timetable Again', 'Day {number}: open your timetable once more. Did anything change or look broken?'],
            ['Share Feedback', 'Day {number}: found a bug or have an idea? Leave feedback on Google Play testing.'],
            ['Almost Finished', 'Only {remaining} days left in the testing program. Keep opening Student Desk daily.'],
            ['Final Days', 'Day {number}: one more day to go after today. Thank you for your support!'],
            ['Testing Complete', 'Day {total}: you finished the testing program. Thank you for helping us launch Student Desk!'],
        ];

        $now = now();

        DB::table('campaign_templates')->insert(
            collect($templates)->map(fn($template, $index) => [
                'template_group' => 'Google Testers',
                'sequence' => $index + 1,
                'type' => 'general',
                'title' => $template[0],
                'body' => $template[1],
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_campaigns');
        Schema::dropIfExists('campaign_templates');
    }
};
