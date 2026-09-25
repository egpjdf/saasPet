<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/health', fn () => response()->json(['status' => 'ok']));

// Platform Admin
Route::middleware(['auth:sanctum'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => Inertia::render('Admin/Dashboard'))->name('dashboard');
});

// Organization + Workspace (Inertia pages)
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::prefix('{organization}')->name('organization.')->group(function () {
        Route::get('/', fn () => Inertia::render('Organization/Dashboard'))->name('dashboard');
        
        Route::prefix('{workspace}')->name('workspace.')->group(function () {
            Route::get('/', fn () => Inertia::render('Workspace/Dashboard'))->name('dashboard');
        });
    });
});

require __DIR__ . '/auth.php';