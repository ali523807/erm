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
            $table->string('type')->default('maintenance')->after('product_id');
            $table->string('title')->nullable()->after('type');
            $table->string('priority')->default('medium')->after('title');
            $table->date('scheduled_at')->nullable()->after('priority');
            $table->date('completed_at')->nullable()->after('service_date');
            $table->string('service_provider')->nullable()->after('technician_id');
            $table->decimal('downtime_hours', 8, 2)->default(0)->after('cost');
            $table->text('findings')->nullable()->after('description');
            $table->text('recommendations')->nullable()->after('findings');
            $table->boolean('affects_availability')->default(true)->after('status');
            $table->index(['company_id', 'status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status', 'scheduled_at']);
            $table->dropColumn([
                'type',
                'title',
                'priority',
                'scheduled_at',
                'completed_at',
                'service_provider',
                'downtime_hours',
                'findings',
                'recommendations',
                'affects_availability',
            ]);
        });
    }
};
