<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumable_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consumable_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action');
            $table->json('changes')->nullable();
            $table->timestamps();

            $table->foreign('consumable_id')->references('id')->on('consumables')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumable_logs');
    }
};

