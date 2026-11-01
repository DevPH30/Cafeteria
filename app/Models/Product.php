<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'category', 'price', 'image', 'stock', 'minimum_stock'])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'minimum_stock' => 'integer',
        ];
    }

    public function getGroupAttribute(): string
    {
        return match ($this->category) {
            'coffee' => 'Cafés',
            'cupcakes' => 'Cupcakes',
            'news' => 'Novidades',
        };
    }

}
