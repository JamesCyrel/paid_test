<?php

use App\Http\Controllers\MeetingController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::post('/meetings/join', [MeetingController::class, 'join'])->name('meetings.join');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
