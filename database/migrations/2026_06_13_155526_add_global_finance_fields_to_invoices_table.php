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
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('tax_profile_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->string('currency', 3)->default('USD')->after('invoice_number');
            $table->string('base_currency', 3)->default('USD')->after('currency');
            $table->decimal('exchange_rate', 18, 8)->default(1)->after('base_currency');
            $table->decimal('base_total_amount', 12, 2)->default(0)->after('total_amount');
            $table->decimal('base_balance_due', 12, 2)->default(0)->after('balance_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_profile_id');
            $table->dropColumn([
                'currency',
                'base_currency',
                'exchange_rate',
                'base_total_amount',
                'base_balance_due',
            ]);
        });
    }
};
