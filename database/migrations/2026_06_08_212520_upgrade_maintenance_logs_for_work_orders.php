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
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->string('work_order_number')->nullable()->after('id');
            $table->foreignId('assigned_to')->nullable()->after('technician_id')->constrained('users')->nullOnDelete();
            $table->foreignId('return_inspection_id')->nullable()->after('assigned_to')->constrained()->nullOnDelete();
            $table->decimal('parts_cost', 12, 2)->default(0)->after('cost');
            $table->decimal('labor_cost', 12, 2)->default(0)->after('parts_cost');
            $table->decimal('vendor_cost', 12, 2)->default(0)->after('labor_cost');
            $table->text('completion_notes')->nullable()->after('recommendations');
            $table->string('final_equipment_status')->nullable()->after('completion_notes');

            $table->unique(['company_id', 'work_order_number']);
            $table->index(['company_id', 'assigned_to']);
            $table->index(['company_id', 'priority', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'work_order_number']);
            $table->dropIndex(['company_id', 'assigned_to']);
            $table->dropIndex(['company_id', 'priority', 'status']);
            $table->dropConstrainedForeignId('return_inspection_id');
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn([
                'work_order_number',
                'parts_cost',
                'labor_cost',
                'vendor_cost',
                'completion_notes',
                'final_equipment_status',
            ]);
        });
    }
};
