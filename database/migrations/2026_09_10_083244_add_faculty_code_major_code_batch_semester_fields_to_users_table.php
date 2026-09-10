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
        Schema::table('users', function (Blueprint $table) {
            $table->string('faculty_code', 50)
                ->nullable()
                ->unique()
                ->after('phone');

            $table->string('major_code', 50)
                ->nullable()
                ->unique()
                ->after('faculty_code');

            $table->string('batch', 20)
                ->nullable()
                ->after('major_code');

            $table->integer('semester')
                ->nullable()
                ->after('batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
             $table->dropColumn(['faculty_code', 'major_code', 'batch', 'semester']);
        });
    }
};
