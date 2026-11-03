<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Route::resource maps all 7 CRUD routes/names at once — the Laravel
    // equivalent of Symfony's #[Route] attributes on each controller method,
    // except declared here instead of on the controller. `only()` drops the
    // 'show' route since the Edit page doubles as the detail view.
    Route::resource('tasks', TaskController::class)->only([
        'index', 'create', 'store', 'edit', 'update', 'destroy',
    ]);
});

require __DIR__.'/auth.php';
