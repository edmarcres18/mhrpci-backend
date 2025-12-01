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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('token')->unique();
            $table->string('role');
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            $table->boolean('used')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['token', 'used']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
