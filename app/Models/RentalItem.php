<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalItem extends Model
{
    protected $guarded = ['id'];
    public function rental()
    {
        return $this->belongsTo(Rental::class,'rental_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class,'product_id');
    }



}
