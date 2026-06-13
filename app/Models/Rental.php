<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rental extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'rental_start_date' => 'date:Y-m-d',
            'rental_end_date' => 'date:Y-m-d',
            'delivery_date' => 'date:Y-m-d',
            'pickup_date' => 'date:Y-m-d',
        ];
    }

    public function rentalItems(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quote(): HasOne
    {
        return $this->hasOne(Quote::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function agreement(): HasOne
    {
        return $this->hasOne(RentalAgreement::class);
    }

    public function depositTransactions(): HasMany
    {
        return $this->hasMany(DepositTransaction::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function depositRequiredAmount(): float
    {
        $items = $this->relationLoaded('rentalItems')
            ? $this->rentalItems
            : $this->rentalItems()->get();

        return (float) $items->sum('deposit_amount');
    }

    public function depositCollectedAmount(): float
    {
        return $this->depositAmountForType('collected');
    }

    public function depositRefundedAmount(): float
    {
        return $this->depositAmountForType('refunded');
    }

    public function depositAppliedAmount(): float
    {
        return $this->depositAmountForType('applied');
    }

    public function depositHeldAmount(): float
    {
        return max(0, $this->depositCollectedAmount() - $this->depositRefundedAmount() - $this->depositAppliedAmount());
    }

    public function depositOutstandingAmount(): float
    {
        return max(0, $this->depositRequiredAmount() - $this->depositCollectedAmount());
    }

    private function depositAmountForType(string $type): float
    {
        if ($this->relationLoaded('depositTransactions')) {
            return (float) $this->depositTransactions->where('type', $type)->sum('amount');
        }

        return (float) $this->depositTransactions()->where('type', $type)->sum('amount');
    }
}
