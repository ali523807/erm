<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rental extends Model
{
    protected $guarded = ['id'];
    public function rentalItems() {
        return $this->hasMany(RentalItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

}
