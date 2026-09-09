<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    public $timestamps = false;

    protected $fillable = ['sale_id', 'receipt_no', 'receipt_data', 'printed_at'];

    protected function casts(): array
    {
        return ['receipt_data' => 'array', 'printed_at' => 'datetime'];
    }
}
