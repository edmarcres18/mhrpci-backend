<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_share_accesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_share_id');
            $table->string('email')->nullable();
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('success')->default(true);
            $table->timestamps();
            $table->foreign('inventory_share_id')->references('id')->on('inventory_shares')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_share_accesses');
    }
};

