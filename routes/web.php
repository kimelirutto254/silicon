<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScoreController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DiscoveryController::class, 'index'])->name('discovery.index');

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login/demo', [AuthController::class, 'demo'])->name('login.demo');
Route::get('/auth/linkedin', [AuthController::class, 'redirect'])->name('linkedin.redirect');
Route::get('/auth/linkedin/callback', [AuthController::class, 'callback'])->name('linkedin.callback');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/role', [RoleController::class, 'edit'])->name('role.edit');
    Route::post('/role', [RoleController::class, 'update'])->name('role.update');

    Route::get('/profiles/create', [ProfileController::class, 'create'])->name('profiles.create');
    Route::post('/profiles', [ProfileController::class, 'store'])->name('profiles.store');
    Route::post('/profiles/suggest-tags', [ProfileController::class, 'suggestTags'])->name('profiles.suggest-tags');
    Route::get('/profiles/{profile:slug}/edit', [ProfileController::class, 'edit'])->name('profiles.edit');
    Route::put('/profiles/{profile:slug}', [ProfileController::class, 'update'])->name('profiles.update');
    Route::post('/profiles/{profile:slug}/recommendations', [RecommendationController::class, 'store'])->name('recommendations.store');

    Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/profiles/{profile:slug}/edit', [AdminController::class, 'edit'])->name('admin.profiles.edit');
    Route::post('/admin/profiles/{profile:slug}/approve', [AdminController::class, 'approve'])->name('admin.profiles.approve');
    Route::post('/admin/profiles/{profile:slug}/reject', [AdminController::class, 'reject'])->name('admin.profiles.reject');
    Route::post('/admin/profiles/bulk', [AdminController::class, 'bulk'])->name('admin.profiles.bulk');
    Route::post('/admin/duplicates/{duplicate}/merge', [AdminController::class, 'mergeDuplicate'])->name('admin.duplicates.merge');
    Route::post('/admin/duplicates/{duplicate}/dismiss', [AdminController::class, 'dismissDuplicate'])->name('admin.duplicates.dismiss');
    Route::post('/admin/conflicts/{recommendation}', [AdminController::class, 'conflict'])->name('admin.conflicts.decide');
    Route::post('/admin/profiles/{profile:slug}/data-quality', [AdminController::class, 'refreshDataQuality'])->name('admin.profiles.data-quality');
});

Route::get('/profiles/{profile:slug}', [ProfileController::class, 'show'])->name('profiles.show');
Route::get('/profiles/{profile:slug}/score', [ScoreController::class, 'show'])->name('profiles.score');
