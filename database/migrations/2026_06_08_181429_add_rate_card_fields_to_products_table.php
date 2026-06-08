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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('hourly_rate', 12, 2)->default(0)->after('default_rate');
            $table->decimal('daily_rate', 12, 2)->default(0)->after('hourly_rate');
            $table->decimal('weekly_rate', 12, 2)->default(0)->after('daily_rate');
            $table->decimal('monthly_rate', 12, 2)->default(0)->after('weekly_rate');
            $table->decimal('custom_rate', 12, 2)->default(0)->after('monthly_rate');
            $table->decimal('default_deposit_amount', 12, 2)->default(0)->after('custom_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'hourly_rate',
                'daily_rate',
                'weekly_rate',
                'monthly_rate',
                'custom_rate',
                'default_deposit_amount',
            ]);
        });
    }
};
