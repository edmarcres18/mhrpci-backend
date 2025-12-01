<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->string('consumable_name');
            $table->text('consumable_description')->nullable();
            $table->string('consumable_brand')->nullable();
            $table->unsignedInteger('current_quantity')->default(0);
            $table->unsignedInteger('threshold_limit')->default(0);
            $table->string('unit', 50)->default('pcs');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumables');
    }
};
