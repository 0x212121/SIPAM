<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\PhotoEvidenceController;
use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Auth routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    
    // Project members
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
    Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');
    
    // Photo evidence - streaming endpoints
    Route::get('/projects/{project}/photos/{photo}/original', [PhotoEvidenceController::class, 'original'])
        ->name('photos.original');
    Route::get('/projects/{project}/photos/{photo}/preview', [PhotoEvidenceController::class, 'preview'])
        ->name('photos.preview');
    Route::get('/projects/{project}/photos/{photo}/thumb', [PhotoEvidenceController::class, 'thumb'])
        ->name('photos.thumb');
    
    // Photo evidence - CRUD
    Route::post('/projects/{project}/photos', [PhotoEvidenceController::class, 'store'])->name('photos.store');
    Route::get('/projects/{project}/photos/{photo}', [PhotoEvidenceController::class, 'show'])->name('photos.show');
    Route::put('/projects/{project}/photos/{photo}', [PhotoEvidenceController::class, 'update'])->name('photos.update');
    Route::delete('/projects/{project}/photos/{photo}', [PhotoEvidenceController::class, 'destroy'])->name('photos.destroy');
    
    // Export
    Route::get('/projects/{project}/export.csv', [ExportController::class, 'csv'])->name('projects.export.csv');
    Route::get('/projects/{project}/export.geojson', [ExportController::class, 'geojson'])->name('projects.export.geojson');
});
