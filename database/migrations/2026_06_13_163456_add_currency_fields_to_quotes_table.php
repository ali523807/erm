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
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('quote_number');
            $table->string('base_currency', 3)->default('USD')->after('currency');
            $table->decimal('exchange_rate', 18, 8)->default(1)->after('base_currency');
            $table->decimal('base_total_amount', 12, 2)->default(0)->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'currency',
                'base_currency',
                'exchange_rate',
                'base_total_amount',
            ]);
        });
    }
};
