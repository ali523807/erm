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
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('credit_note_number');
            $table->date('credit_date');
            $table->string('reason');
            $table->decimal('amount', 12, 2);
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->string('refund_method')->nullable();
            $table->string('refund_reference')->nullable();
            $table->string('status')->default('applied');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'credit_note_number']);
            $table->index(['company_id', 'invoice_id']);
            $table->index(['company_id', 'credit_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
