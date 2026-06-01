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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('timezone');
            $table->string('locale')->default('en')->after('currency');
            $table->string('date_format')->default('Y-m-d')->after('locale');
            $table->string('measurement_system')->default('metric')->after('date_format');
            $table->string('tax_name')->nullable()->after('measurement_system');
            $table->string('tax_number')->nullable()->after('tax_name');
            $table->decimal('default_tax_rate', 8, 4)->default(0)->after('tax_number');
            $table->boolean('tax_inclusive')->default(false)->after('default_tax_rate');
            $table->string('address_line_1')->nullable()->after('tax_inclusive');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('state_region')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state_region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'currency',
                'locale',
                'date_format',
                'measurement_system',
                'tax_name',
                'tax_number',
                'default_tax_rate',
                'tax_inclusive',
                'address_line_1',
                'address_line_2',
                'city',
                'state_region',
                'postal_code',
            ]);
        });
    }
};
