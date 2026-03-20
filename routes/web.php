<?php

use App\Http\Controllers\WordleGameController;
use Illuminate\Support\Facades\Route;


Route::get('/', [WordleGameController::class, 'index'])->name('home');
Route::post('/register/player', [WordleGameController::class, 'registerPlayer']);
Route::post('/login/player', [WordleGameController::class, 'loginPlayer']);

Route::middleware('wordle.auth')->group(function () {
    Route::get('/existGame/{id?}', [WordleGameController::class, 'existGame'])->name('player.existGame');
    Route::post('/store/activity', [WordleGameController::class, 'storeActivity'])->name('tracker.store');
    Route::get('/player/activity/{id?}', [WordleGameController::class, 'getActivity'])->name('player.activity');
});


// Route::get('/', function () {
//     return Inertia::render('Welcome');
// })->name('home');

// Route::get('dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
