<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deleted_consumables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_id')->constrained('consumables')->cascadeOnDelete();
            $table->string('consumable_name');
            $table->text('consumable_description')->nullable();
            $table->string('consumable_brand')->nullable();
            $table->unsignedInteger('current_quantity')->default(0);
            $table->unsignedInteger('threshold_limit')->default(0);
            $table->string('unit', 50)->default('pcs');
            $table->timestamp('deleted_at');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('restore_status')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deleted_consumables');
    }
};
