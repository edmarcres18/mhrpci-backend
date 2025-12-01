<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumable_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_id')->constrained('consumables')->cascadeOnDelete();
            $table->unsignedInteger('quantity_used');
            $table->string('purpose');
            $table->string('used_by');
            $table->date('date_used');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumable_usages');
    }
};
