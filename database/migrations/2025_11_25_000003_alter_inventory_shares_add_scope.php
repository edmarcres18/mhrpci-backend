<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_shares', function (Blueprint $table) {
            $table->string('scope')->default('single');
            $table->json('accountable_list')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_shares', function (Blueprint $table) {
            $table->dropColumn('scope');
            $table->dropColumn('accountable_list');
        });
    }
};

