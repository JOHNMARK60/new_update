<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClosingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HeldSaleController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::get('/admin/login', [AuthController::class, 'create'])->name('admin.login');
    Route::get('/cashier/login', [AuthController::class, 'create'])->name('cashier.login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'requestLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.update');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->whereNumber('product')->name('products.show');
    Route::get('/inventory-status', InventoryController::class)->name('inventory.status');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/csv', [ReportController::class, 'csv'])->name('reports.csv');
    Route::get('/reports/export/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    Route::get('/closings', [ClosingController::class, 'index'])->name('closings.index');
    Route::post('/closings', [ClosingController::class, 'store'])->name('closings.store');
    Route::get('/closings/expected', [ClosingController::class, 'expected'])->name('closings.expected');
    Route::view('/roles-and-permissions', 'roles')->name('roles.index');
    Route::get('/sales/{sale}/receipt', [PosController::class, 'receipt'])->name('sales.receipt');
    Route::middleware('role:admin,cashier')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
        Route::post('/pos/holds', [HeldSaleController::class, 'store'])->name('pos.holds.store');
        Route::delete('/pos/holds/{heldSale}', [HeldSaleController::class, 'destroy'])->name('pos.holds.destroy');
        Route::post('/pos/shift/open', [ShiftController::class, 'open'])->name('pos.shift.open');
        Route::post('/pos/shift/close', [ShiftController::class, 'close'])->name('pos.shift.close');
    });
    Route::middleware('role:admin')->group(function () {
        Route::resource('products', ProductController::class)->except(['index', 'show']);
        Route::resource('users', UserController::class)->except('show');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
        Route::patch('/closings/{closing}/review', [ClosingController::class, 'review'])->name('closings.review');
        Route::patch('/sales/{sale}/void', [PosController::class, 'void'])->name('sales.void');
    });
});
