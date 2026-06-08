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
        Schema::create('return_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_agreement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('condition_status')->default('good');
            $table->text('condition_notes')->nullable();
            $table->text('missing_accessories')->nullable();
            $table->text('damage_notes')->nullable();
            $table->decimal('damage_amount', 12, 2)->default(0);
            $table->string('next_equipment_status')->default('available');
            $table->string('inspected_by')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();

            $table->unique(['rental_agreement_id', 'rental_item_id']);
            $table->index(['company_id', 'condition_status']);
            $table->index(['company_id', 'next_equipment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_inspections');
    }
};
