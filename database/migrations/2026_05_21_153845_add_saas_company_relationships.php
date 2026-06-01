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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('owner');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });

        foreach ($this->tenantTables() as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (array_reverse($this->tenantTables()) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }

        Schema::dropIfExists('company_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_company_id');
        });
    }

    /**
     * @return array<int, string>
     */
    private function tenantTables(): array
    {
        return [
            'categories',
            'products',
            'product_attributes',
            'customers',
            'rentals',
            'rental_items',
            'rental_agreements',
            'maintenance_logs',
        ];
    }
};
