<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_phones', function (Blueprint $table) {
            $table->id();
            $table->string('department');
            $table->string('phone_number');
            $table->string('person_in_charge');
            $table->string('position');
            $table->string('extension')->nullable();
            $table->timestamps();

            $table->unique(['phone_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_phones');
    }
};

