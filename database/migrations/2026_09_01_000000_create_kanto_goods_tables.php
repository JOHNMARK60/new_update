<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            Schema::create('categories', fn (Blueprint $t) => [$t->id(), $t->string('name', 120)->unique(), $t->timestamp('created_at')->useCurrent()]);
        }
        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', fn (Blueprint $t) => [$t->id(), $t->string('name', 150)->unique(), $t->string('contact_no', 50)->nullable(), $t->timestamp('created_at')->useCurrent()]);
        }
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $t) {
                $t->id();
                $t->string('name', 150);
                $t->string('sku', 50)->nullable()->unique();
                $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
                $t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
                $t->decimal('price', 10, 2)->default(0);
                $t->integer('quantity')->default(0);
                $t->integer('low_stock_level')->default(5);
                $t->date('expiration_date')->nullable();
                $t->string('image_path')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $t) {
                $t->id();
                $t->string('receipt_no', 50)->nullable()->unique();
                $t->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('cashier_name', 201)->nullable();
                $t->foreignId('product_id')->nullable();
                $t->integer('quantity')->default(1);
                $t->decimal('total_price', 10, 2)->default(0);
                $t->decimal('subtotal_amount', 10, 2)->default(0);
                $t->decimal('discount', 10, 2)->default(0);
                $t->decimal('tax', 10, 2)->default(0);
                $t->decimal('total_amount', 10, 2)->default(0);
                $t->decimal('tendered_amount', 10, 2)->default(0);
                $t->decimal('change_amount', 10, 2)->default(0);
                $t->string('payment_method', 50)->default('cash');
                $t->foreignId('user_id')->nullable();
                $t->dateTime('sale_date')->useCurrent();
                $t->string('status', 20)->default('paid');
                $t->string('closing_status', 20)->default('open');
                $t->dateTime('closed_at')->nullable();
            });
        }
        if (! Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
                $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $t->string('product_name', 150);
                $t->integer('quantity');
                $t->decimal('unit_price', 10, 2);
                $t->decimal('total_price', 10, 2);
                $t->timestamp('created_at')->useCurrent();
            });
        }
        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $t) {
                $t->id();
                $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
                $t->decimal('amount', 10, 2);
                $t->decimal('tendered_amount', 10, 2);
                $t->decimal('change_amount', 10, 2);
                $t->string('currency', 20)->default('PHP');
                $t->string('payment_method', 50)->default('cash');
                $t->dateTime('payment_date')->useCurrent();
            });
        }
        if (! Schema::hasTable('receipts')) {
            Schema::create('receipts', function (Blueprint $t) {
                $t->id();
                $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
                $t->string('receipt_no', 50)->unique();
                $t->json('receipt_data')->nullable();
                $t->dateTime('printed_at')->nullable();
                $t->timestamp('created_at')->useCurrent();
            });
        }
        if (! Schema::hasTable('inventory_logs')) {
            Schema::create('inventory_logs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $t->string('action', 100);
                $t->integer('quantity_change')->default(0);
                $t->integer('stock_before')->nullable();
                $t->integer('stock_after')->nullable();
                $t->string('reference_type', 50)->nullable();
                $t->unsignedBigInteger('reference_id')->nullable();
                $t->foreignId('created_by')->nullable();
                $t->timestamp('created_at')->useCurrent();
            });
        }
        if (! Schema::hasTable('closing_reports')) {
            Schema::create('closing_reports', function (Blueprint $t) {
                $t->id();
                $t->date('closing_date');
                $t->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('cashier_name', 201);
                $t->integer('total_transactions')->default(0);
                $t->integer('total_items_sold')->default(0);
                $t->decimal('total_sales', 10, 2)->default(0);
                $t->decimal('total_cash_received', 10, 2)->default(0);
                $t->decimal('expected_cash_amount', 10, 2)->default(0);
                $t->decimal('actual_cash_amount', 10, 2)->default(0);
                $t->decimal('difference_amount', 10, 2)->default(0);
                $t->dateTime('closing_time')->useCurrent();
                $t->foreignId('closed_by')->nullable();
                $t->string('status', 20)->default('closed');
                $t->string('notes')->nullable();
                $t->string('review_status', 20)->default('pending');
                $t->string('admin_feedback')->nullable();
                $t->foreignId('reviewed_by')->nullable();
                $t->dateTime('reviewed_at')->nullable();
                $t->unique(['closing_date', 'cashier_id']);
            });
        }
    }

    public function down(): void
    {
        foreach (['closing_reports', 'inventory_logs', 'receipts', 'payments', 'sale_items', 'sales', 'products', 'suppliers', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
