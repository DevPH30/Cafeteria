<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'category', 'price', 'image'])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
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

    public function getDescriptionAttribute(): string
    {
        return 'Preparado artesanalmente no Café Aroma.';
    }
}
