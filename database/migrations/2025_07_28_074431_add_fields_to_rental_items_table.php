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
        Schema::table('rental_items', function (Blueprint $table) {
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->string('duration_type')->nullable();
            $table->decimal('no_of_duration')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_items', function (Blueprint $table) {
            $table->dropColumn('start_date');
            $table->dropColumn('end_date');
            $table->dropColumn('duration_type');
            $table->dropColumn('no_of_duration');
        });
    }
};
