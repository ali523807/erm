<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalAgreement extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'agreement_date' => 'date',
            'valid_until' => 'date',
            'signed_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'returned_at' => 'datetime',
            'damage_amount' => 'decimal:2',
            'customer_accepted_checkout' => 'boolean',
            'customer_accepted_return' => 'boolean',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
