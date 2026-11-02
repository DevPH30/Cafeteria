<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'number',
    'status',
    'fulfillment',
    'payment_method',
    'subtotal',
    'delivery_fee',
    'total',
    'items',
    'delivery_address',
    'scheduled_date',
    'scheduled_time',
    'message',
    'value',
    'detail',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'items' => 'array',
            'scheduled_date' => 'date:Y-m-d',
            'value' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
