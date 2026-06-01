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
}
