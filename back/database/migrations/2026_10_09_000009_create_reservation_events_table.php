<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained();
            $table->string('type');
            $table->date('occurred_on');
            $table->foreignId('user_id')->constrained();
            $table->date('previous_starts_at')->nullable();
            $table->date('previous_ends_at')->nullable();
            $table->date('new_starts_at')->nullable();
            $table->date('new_ends_at')->nullable();
            $table->string('previous_purchase_order')->nullable();
            $table->string('new_purchase_order')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_events');
    }
};
