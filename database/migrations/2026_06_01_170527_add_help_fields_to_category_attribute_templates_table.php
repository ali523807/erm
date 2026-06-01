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
        Schema::table('category_attribute_templates', function (Blueprint $table) {
            $table->string('placeholder')->nullable()->after('unit');
            $table->text('help_text')->nullable()->after('placeholder');
            $table->json('options')->nullable()->after('help_text');
            $table->string('default_value')->nullable()->after('options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_attribute_templates', function (Blueprint $table) {
            $table->dropColumn([
                'placeholder',
                'help_text',
                'options',
                'default_value',
            ]);
        });
    }
};
