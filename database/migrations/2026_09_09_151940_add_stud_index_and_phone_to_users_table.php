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

            $table->string('stud_index', 50)
                ->nullable()
                ->unique()
                ->after('id');

            $table->string('phone', 30)
                ->nullable()
                ->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['stud_index']);
            $table->dropColumn(['stud_index', 'phone']);
        });
    }
};
