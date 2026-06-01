<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $firstUser = DB::table('users')->orderBy('id')->first();

        if (! $firstUser) {
            return;
        }

        $companyId = DB::table('companies')->orderBy('id')->value('id');

        if (! $companyId) {
            $companyId = DB::table('companies')->insertGetId([
                'name' => 'Default Company',
                'slug' => $this->uniqueCompanySlug('Default Company'),
                'email' => $firstUser->email,
                'timezone' => 'Asia/Calcutta',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('users')
            ->whereNull('current_company_id')
            ->update(['current_company_id' => $companyId]);

        DB::table('users')
            ->orderBy('id')
            ->get()
            ->each(function (object $user) use ($companyId, $firstUser): void {
                $exists = DB::table('company_user')
                    ->where('company_id', $companyId)
                    ->where('user_id', $user->id)
                    ->exists();

                if (! $exists) {
                    DB::table('company_user')->insert([
                        'company_id' => $companyId,
                        'user_id' => $user->id,
                        'role' => $user->id === $firstUser->id ? 'owner' : 'admin',
                        'joined_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

        foreach ($this->tenantTables() as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'company_id')) {
                DB::table($tableName)
                    ->whereNull('company_id')
                    ->update(['company_id' => $companyId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tenantTables() as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'company_id')) {
                DB::table($tableName)->update(['company_id' => null]);
            }
        }
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

    private function uniqueCompanySlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'company';
        $slug = $baseSlug;
        $counter = 2;

        while (DB::table('companies')->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
};
