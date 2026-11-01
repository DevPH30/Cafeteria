<?php

use App\Http\Controllers\Admin\AdministratorController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Customer\CustomerAuthController;
use App\Http\Controllers\Customer\CustomerPasswordResetController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:customer')->group(function () {
    Route::get('/cadastro', [CustomerAuthController::class, 'register'])->name('customer.register');
    Route::post('/cadastro', [CustomerAuthController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('customer.register.store');
    Route::post('/cliente/login', [CustomerAuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('customer.login');
    Route::get('/cliente/esqueci-senha', [CustomerPasswordResetController::class, 'create'])->name('customer.password.request');
    Route::post('/cliente/esqueci-senha', [CustomerPasswordResetController::class, 'email'])
        ->middleware('throttle:6,1')
        ->name('customer.password.email');
    Route::get('/cliente/redefinir-senha/{token}', [CustomerPasswordResetController::class, 'reset'])->name('customer.password.reset');
    Route::post('/cliente/redefinir-senha', [CustomerPasswordResetController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('customer.password.update');
});

Route::post('/cliente/logout', [CustomerAuthController::class, 'logout'])
    ->middleware('auth:customer')
    ->name('customer.logout');
Route::put('/cliente/perfil', [ProfileController::class, 'update'])
    ->middleware('auth:customer')
    ->name('customer.profile.update');
Route::post('/cliente/pedidos', [OrderController::class, 'store'])
    ->middleware('auth:customer')
    ->name('customer.orders.store');

Route::inertia('/', 'Cafe')->name('home');
Route::inertia('/produtos', 'Cafe')->name('products');
Route::inertia('/sobre-nos', 'Cafe')->name('about');
Route::inertia('/contatos', 'Cafe')->name('contact');
Route::inertia('/encomendas', 'Cafe')->name('orders');
Route::inertia('/checkout', 'Cafe')->name('checkout');
Route::inertia('/acompanhar-pedido', 'Cafe')->name('order-tracking');
Route::inertia('/minha-conta', 'Cafe')->name('customer.account');
Route::middleware('auth:web')->group(function () {
    Route::inertia('/admin', 'Cafe')->name('cafe-admin');
    Route::post('/admin/produtos', [ProductController::class, 'store'])->name('admin.products.store');
    Route::put('/admin/produtos/{product}', [ProductController::class, 'update'])->name('admin.products.update');
    Route::patch('/admin/produtos/{product}/estoque', [ProductController::class, 'updateStock'])->name('admin.products.stock');
    Route::delete('/admin/produtos/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');
    Route::patch('/admin/pedidos/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('admin.orders.status');
});
Route::middleware('auth:web')->prefix('/admin/administradores')->name('admin.administrators.')->group(function () {
    Route::get('/cadastro', [AdministratorController::class, 'create'])->name('create');
    Route::post('/cadastro', [AdministratorController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
