<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $module, string $action, string $description, ?Model $subject = null, array $properties = [], ?Request $request = null): ActivityLog
    {
        $request ??= request();
        $user = auth()->user();
        $companyId = $user?->current_company_id ?? ($subject?->company_id ?? null);

        return ActivityLog::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'user_id' => $user?->id,
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * @param  array<int, string>  $except
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function changesFor(Model $model, array $except = []): array
    {
        return collect($model->getChanges())
            ->except([...$except, 'updated_at'])
            ->mapWithKeys(fn (mixed $value, string $key): array => [
                $key => [
                    'old' => $model->getOriginal($key),
                    'new' => $value,
                ],
            ])
            ->all();
    }
}
