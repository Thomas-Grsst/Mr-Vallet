<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->string('type');
            $table->foreignId('agency_id')->constrained();
            $table->date('last_vgp_at')->nullable();
            $table->date('workshop_until')->nullable();
            $table->string('workshop_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
