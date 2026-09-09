<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'action', 'quantity_change', 'stock_before', 'stock_after', 'reference_type', 'reference_id', 'created_by', 'created_at'];
}
