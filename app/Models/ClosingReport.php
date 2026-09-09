<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClosingReport extends Model
{
    public $timestamps = false;

    protected $fillable = ['closing_date', 'cashier_id', 'cashier_name', 'total_transactions', 'total_items_sold', 'total_sales', 'total_cash_received', 'expected_cash_amount', 'actual_cash_amount', 'difference_amount', 'closing_time', 'closed_by', 'status', 'notes', 'review_status', 'admin_feedback', 'reviewed_by', 'reviewed_at'];

    protected function casts(): array
    {
        return ['closing_date' => 'date', 'closing_time' => 'datetime', 'reviewed_at' => 'datetime'];
    }
}
