<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RoomController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Make index page PUBLIC (accessible without login)
Route::get('/index', function () {
    return view('index');
})->name('index');

Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');

// Login routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Registration routes
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Protected routes (require authentication) - if you have any
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard'); // Create a separate dashboard if needed
    })->name('dashboard');

    Route::post('/rooms/{room}/reserve', [RoomController::class, 'reserve'])->name('rooms.reserve');
    Route::post('/rooms/{room}/release', [RoomController::class, 'release'])->name('rooms.release');
});