<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consumable_logs', function (Blueprint $table) {
            $table->dropForeign(['consumable_id']);
        });
        Schema::table('consumable_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('consumable_id')->nullable()->change();
            $table->foreign('consumable_id')->references('id')->on('consumables')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('consumable_logs', function (Blueprint $table) {
            $table->dropForeign(['consumable_id']);
        });
        Schema::table('consumable_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('consumable_id')->nullable(false)->change();
            $table->foreign('consumable_id')->references('id')->on('consumables')->onDelete('cascade');
        });
    }
};
