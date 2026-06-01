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
            $table->string('equipment_code')->nullable()->after('company_id');
            $table->foreignId('branch_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->foreignId('storage_location_id')->nullable()->after('warehouse_id')->constrained()->nullOnDelete();
            $table->string('ownership_type')->default('owned')->after('location');
            $table->date('acquisition_date')->nullable()->after('ownership_type');
            $table->decimal('acquisition_cost', 12, 2)->default(0)->after('acquisition_date');
            $table->decimal('replacement_value', 12, 2)->default(0)->after('acquisition_cost');
            $table->string('unit_of_measure')->default('unit')->after('replacement_value');
            $table->string('default_rate_type')->nullable()->after('unit_of_measure');
            $table->decimal('default_rate', 12, 2)->default(0)->after('default_rate_type');
            $table->string('condition')->nullable()->after('default_rate');
            $table->date('certificate_expires_at')->nullable()->after('warranty_expiry');

            $table->unique(['company_id', 'equipment_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'equipment_code']);
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('storage_location_id');
            $table->dropColumn([
                'equipment_code',
                'ownership_type',
                'acquisition_date',
                'acquisition_cost',
                'replacement_value',
                'unit_of_measure',
                'default_rate_type',
                'default_rate',
                'condition',
                'certificate_expires_at',
            ]);
        });
    }
};
