<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_periods');
    }
};
