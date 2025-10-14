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
        Schema::table('site_informations', function (Blueprint $table) {
            $table->string('site_name')->default('Laravel Starter Kit')->after('id');
            $table->string('site_logo')->nullable()->after('site_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_informations', function (Blueprint $table) {
            $table->dropColumn(['site_name', 'site_logo']);
        });
    }
};
