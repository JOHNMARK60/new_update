<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $timestamps = false;

    protected $fillable = ['sale_id', 'amount', 'tendered_amount', 'change_amount', 'currency', 'payment_method', 'payment_date'];

    protected function casts(): array
    {
        return ['payment_date' => 'datetime'];
    }
}
