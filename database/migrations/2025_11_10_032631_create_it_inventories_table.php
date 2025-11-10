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
        Schema::create('it_inventories', function (Blueprint $table) {
            $table->id();

            // Core identification
            $table->string('asset_tag')->unique();
            $table->string('category')->index(); // e.g., Laptop, Desktop, Server, Peripheral
            $table->string('type')->nullable(); // e.g., MacBook Pro, ThinkPad, etc.
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable()->unique();

            // Ownership and location
            $table->string('status')->default('stock')->index(); // stock, in_use, repair, retired, lost
            $table->string('location')->nullable();
            // Assigned to: free-form person or department name (nullable)
            $table->string('assigned_to')->nullable();

            // Procurement details
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 10, 2)->nullable();
            $table->string('supplier')->nullable();
            $table->date('warranty_expires_at')->nullable();

            // Misc
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('it_inventories');
    }
};
