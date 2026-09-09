<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'customer_name')) {
                $table->string('customer_name', 150)->nullable()->after('cashier_name');
            }
            if (! Schema::hasColumn('sales', 'discount_type')) {
                $table->string('discount_type', 20)->default('fixed')->after('discount');
            }
            if (! Schema::hasColumn('sales', 'discount_value')) {
                $table->decimal('discount_value', 10, 2)->default(0)->after('discount_type');
            }
            if (! Schema::hasColumn('sales', 'discount_reason')) {
                $table->string('discount_reason')->nullable()->after('discount_value');
            }
            if (! Schema::hasColumn('sales', 'shift_id')) {
                $table->unsignedBigInteger('shift_id')->nullable()->index()->after('cashier_id');
            }
            if (! Schema::hasColumn('sales', 'voided_by')) {
                $table->unsignedBigInteger('voided_by')->nullable()->after('status');
                $table->dateTime('voided_at')->nullable()->after('voided_by');
                $table->string('void_reason')->nullable()->after('voided_at');
            }
        });

        if (! Schema::hasTable('held_sales')) {
            Schema::create('held_sales', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->string('label', 120);
                $table->json('cart');
                $table->string('customer_name', 150)->nullable();
                $table->decimal('discount', 10, 2)->default(0);
                $table->string('discount_type', 20)->default('fixed');
                $table->decimal('discount_value', 10, 2)->default(0);
                $table->string('discount_reason')->nullable();
                $table->decimal('tax', 10, 2)->default(0);
                $table->string('payment_method', 50)->default('cash');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cashier_shifts')) {
            Schema::create('cashier_shifts', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->dateTime('opened_at');
                $table->dateTime('closed_at')->nullable();
                $table->decimal('opening_cash', 10, 2)->default(0);
                $table->decimal('closing_cash', 10, 2)->nullable();
                $table->string('status', 20)->default('open');
                $table->string('notes')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('held_sales');
        Schema::dropIfExists('cashier_shifts');
        Schema::table('sales', function (Blueprint $table) {
            foreach (['customer_name', 'discount_type', 'discount_value', 'discount_reason', 'shift_id', 'voided_by', 'voided_at', 'void_reason'] as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
