<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Users\BerandaController;
use App\Http\Controllers\Users\TentangKamiController;
use App\Http\Controllers\Users\ProdukController;
use App\Http\Controllers\Users\KontakController;

use App\Http\Controllers\Admins\DashboardController;
use App\Http\Controllers\Admins\StockController;

// Users
Route::get('/', [BerandaController::class, 'index'])->name('beranda');
Route::get('/tentang-kami', [TentangKamiController::class, 'index'])->name('tentang-kami');
Route::get('/produk', [ProdukController::class, 'index'])->name('produk');
Route::get('/kontak', [KontakController::class, 'index'])->name('kontak');

// Admins
Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
Route::get('/admin/stock', [StockController::class, 'index'])->name('admin.stock');
