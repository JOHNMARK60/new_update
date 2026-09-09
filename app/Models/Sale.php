<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    public $timestamps = false;

    protected $fillable = ['receipt_no', 'cashier_id', 'shift_id', 'cashier_name', 'customer_name', 'product_id', 'quantity', 'total_price', 'subtotal_amount', 'discount', 'discount_type', 'discount_value', 'discount_reason', 'tax', 'total_amount', 'tendered_amount', 'change_amount', 'payment_method', 'user_id', 'sale_date', 'status', 'voided_by', 'voided_at', 'void_reason', 'closing_status', 'closed_at'];

    protected function casts(): array
    {
        return ['sale_date' => 'datetime', 'voided_at' => 'datetime', 'closed_at' => 'datetime', 'subtotal_amount' => 'decimal:2', 'discount' => 'decimal:2', 'discount_value' => 'decimal:2', 'tax' => 'decimal:2', 'total_amount' => 'decimal:2', 'tendered_amount' => 'decimal:2', 'change_amount' => 'decimal:2'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
