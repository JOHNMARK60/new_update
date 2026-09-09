<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = ['name', 'sku', 'category_id', 'supplier_id', 'price', 'quantity', 'low_stock_level', 'expiration_date', 'image_path'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'expiration_date' => 'date'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity <= $this->low_stock_level;
    }
}
