<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            $companyId = auth()->user()?->current_company_id;

            if ($companyId) {
                $builder->where($builder->getModel()->getTable().'.company_id', $companyId);
            }
        });

        static::creating(function (self $model): void {
            if (! $model->company_id && auth()->user()?->current_company_id) {
                $model->company_id = auth()->user()->current_company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
