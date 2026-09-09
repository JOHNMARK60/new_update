<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeldSale extends Model
{
    protected $fillable = ['user_id', 'label', 'cart', 'customer_name', 'discount', 'discount_type', 'discount_value', 'discount_reason', 'tax', 'payment_method'];

    protected function casts(): array
    {
        return ['cart' => 'array', 'discount' => 'decimal:2', 'discount_value' => 'decimal:2', 'tax' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
