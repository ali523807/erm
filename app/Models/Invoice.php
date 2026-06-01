<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'damage_amount' => 'decimal:2',
            'late_fee_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function recalculateTotals(): void
    {
        $rental = $this->rental()->with('rentalItems')->first();

        if ($rental) {
            $this->subtotal = (float) $rental->rentalItems->sum('total_price');
            $this->deposit_amount = (float) $rental->rentalItems->sum('deposit_amount');
        }

        $this->paid_amount = (float) $this->payments()->sum('amount');
        $this->total_amount = max(0, (float) $this->subtotal
            + (float) $this->tax_amount
            + (float) $this->damage_amount
            + (float) $this->late_fee_amount
            - (float) $this->discount_amount);
        $this->balance_due = max(0, (float) $this->total_amount - (float) $this->paid_amount);
        $this->status = $this->calculatedStatus();
        $this->save();
    }

    private function calculatedStatus(): string
    {
        if ((float) $this->balance_due <= 0 && (float) $this->total_amount > 0) {
            return 'paid';
        }

        if ((float) $this->paid_amount > 0) {
            return 'partial';
        }

        if ($this->due_date && now()->toDateString() > $this->due_date->toDateString()) {
            return 'overdue';
        }

        return 'issued';
    }
}
