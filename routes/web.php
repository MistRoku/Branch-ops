<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| These routes are loaded for the web application. They handle:
| - Authentication (login/logout)
| - POS terminal (default landing page after login)
| - Legal pages (Terms of Service, Privacy Policy)
|
*/

// Guest routes - accessible only when not logged in
Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Default landing page - redirect to POS terminal
    Route::get('/', function () {
        return redirect()->route('pos.terminal');
    })->name('home');
    
    // POS Terminal - Main operational screen (no sidebar)
    Route::get('/pos', function () {
        return view('pos.terminal');
    })->name('pos.terminal');
});

// Public legal pages (accessible to guests and authenticated users)
Route::get('/terms', function () {
    return view('auth.terms');
})->name('terms');

Route::get('/privacy', function () {
    return view('auth.privacy');
})->name('privacy');
