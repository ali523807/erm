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
        Schema::table('rental_agreements', function (Blueprint $table) {
            $table->string('agreement_number')->nullable()->after('rental_id');
            $table->string('status')->default('draft')->after('agreement_pdf_url');
            $table->date('agreement_date')->nullable()->after('status');
            $table->date('valid_until')->nullable()->after('agreement_date');
            $table->text('terms')->nullable()->after('valid_until');
            $table->text('checkout_condition')->nullable()->after('terms');
            $table->text('checkout_accessories')->nullable()->after('checkout_condition');
            $table->text('checkout_notes')->nullable()->after('checkout_accessories');
            $table->string('checkout_representative')->nullable()->after('checkout_notes');
            $table->string('checkout_id_number')->nullable()->after('checkout_representative');
            $table->timestamp('checked_out_at')->nullable()->after('checkout_id_number');
            $table->text('return_condition')->nullable()->after('checked_out_at');
            $table->text('return_missing_accessories')->nullable()->after('return_condition');
            $table->text('return_damage_notes')->nullable()->after('return_missing_accessories');
            $table->decimal('damage_amount', 12, 2)->default(0)->after('return_damage_notes');
            $table->string('return_representative')->nullable()->after('damage_amount');
            $table->timestamp('returned_at')->nullable()->after('return_representative');
            $table->boolean('customer_accepted_checkout')->default(false)->after('returned_at');
            $table->boolean('customer_accepted_return')->default(false)->after('customer_accepted_checkout');
            $table->text('internal_notes')->nullable()->after('customer_accepted_return');

            $table->unique(['company_id', 'agreement_number']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_agreements', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'agreement_number']);
            $table->dropIndex(['company_id', 'status']);
            $table->dropColumn([
                'agreement_number',
                'status',
                'agreement_date',
                'valid_until',
                'terms',
                'checkout_condition',
                'checkout_accessories',
                'checkout_notes',
                'checkout_representative',
                'checkout_id_number',
                'checked_out_at',
                'return_condition',
                'return_missing_accessories',
                'return_damage_notes',
                'damage_amount',
                'return_representative',
                'returned_at',
                'customer_accepted_checkout',
                'customer_accepted_return',
                'internal_notes',
            ]);
        });
    }
};
