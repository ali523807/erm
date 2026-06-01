<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use BelongsToCompany, HasFactory;

    protected $guarded = ['id'];

    public function attributeTemplates(): HasMany
    {
        return $this->hasMany(CategoryAttributeTemplate::class);
    }
}
