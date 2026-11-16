<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'role:admin,officer,security,registrar,it_admin'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/media/{file}', MediaController::class)->name('media');
});

Route::middleware(['auth', 'role:admin,officer'])->group(function () {
    Route::post('/items/import', [ItemController::class, 'import'])->name('items.import');
    Route::patch('/items/{item}/status', [ItemController::class, 'setStatus'])->name('items.status');
    Route::resource('items', ItemController::class)->only(['edit', 'update']);

    Route::patch('/claims/{claim}', [ClaimController::class, 'decide'])->name('claims.decide');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/{what}', [ReportController::class, 'export'])->name('reports.export');
});

Route::middleware(['auth', 'role:admin,officer,registrar'])->group(function () {
    Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
    Route::get('/claims/{claim}', [ClaimController::class, 'show'])->name('claims.show');
});

Route::middleware(['auth', 'role:admin,officer,security'])->group(function () {
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
});

Route::middleware(['auth', 'role:admin,officer'])->group(function () {
    Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    Route::patch('/users/{user}/password', [UserController::class, 'password'])->name('users.password');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

});

Route::middleware(['auth', 'role:admin,it_admin'])->group(function () {
    Route::get('/backup', [ReportController::class, 'backup'])->name('backup');
    Route::get('/activity', [ReportController::class, 'activity'])->name('activity.index');
});
