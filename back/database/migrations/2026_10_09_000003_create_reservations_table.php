<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained();
            $table->string('client');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->foreignId('entered_by_agency_id')->constrained('agencies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
