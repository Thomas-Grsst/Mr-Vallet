<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->date('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_agency_id')->nullable()->constrained('agencies');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by_agency_id');
            $table->dropColumn('cancelled_at');
        });
    }
};
