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
        Schema::table('user_biometrics', function (Blueprint $table) {
            $table->string('device_id')->nullable()->unique(false)->change(); // Use unique(false) to remove unique constraint
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_biometrics', function (Blueprint $table) {
            // Re-add the unique constraint if device_id should be unique again
            // This might fail if there are existing null values.
            $table->string('device_id')->unique()->nullable(false)->change();
        });
    }
};