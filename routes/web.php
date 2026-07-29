<?php

use App\Http\Controllers\Admin\AdviceRequestController as AdminAdviceRequestController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PlantController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\AdviceRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'application' => config('app.name'),
    ]);
})->name('health');

Route::get('/conseil', [AdviceRequestController::class, 'create'])->name('advice.create');
Route::post('/conseil', [AdviceRequestController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('advice.store');
Route::get('/conseil/suivi/{token}', [AdviceRequestController::class, 'track'])
    ->where('token', '[a-f0-9]{64}')
    ->middleware('throttle:20,1')
    ->name('advice.track');
Route::get('/conseil/suivi/{token}/status', [AdviceRequestController::class, 'status'])
    ->where('token', '[a-f0-9]{64}')
    ->middleware('throttle:60,1')
    ->name('advice.status');

Route::get('/admin', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('admin.dashboard');

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('advice-requests', AdminAdviceRequestController::class)->only(['index', 'show']);
    Route::resource('plants', PlantController::class)->except(['show']);
    Route::patch('plants/{plant}/deactivate', [PlantController::class, 'deactivate'])
        ->name('plants.deactivate');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
