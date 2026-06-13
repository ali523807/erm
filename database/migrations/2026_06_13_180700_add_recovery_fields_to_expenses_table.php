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
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->string('recovery_status')->default('not_invoiced')->after('is_billable');
            $table->timestamp('invoiced_at')->nullable()->after('recovery_status');
            $table->timestamp('recovered_at')->nullable()->after('invoiced_at');
            $table->index(['company_id', 'recovery_status']);
            $table->index(['invoice_id', 'recovery_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'recovery_status']);
            $table->dropIndex(['invoice_id', 'recovery_status']);
            $table->dropConstrainedForeignId('invoice_id');
            $table->dropColumn([
                'recovery_status',
                'invoiced_at',
                'recovered_at',
            ]);
        });
    }
};
