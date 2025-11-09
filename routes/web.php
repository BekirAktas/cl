<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Team\TeamController;

Route::get('/', [TeamController::class, 'index'])->name('home');
Route::get('/fixtures', [TeamController::class, 'fixtures'])->name('fixtures');
Route::get('/simulation', [TeamController::class, 'simulation'])->name('simulation');
Route::post('/generate-fixtures', [TeamController::class, 'generateFixtures'])->name('generateFixtures');
Route::post('/league/play-week', [TeamController::class, 'playWeek'])->name('league.playWeek');
Route::post('/league/play-all', [TeamController::class, 'playAll'])->name('league.playAll');
Route::post('/league/reset', [TeamController::class, 'reset'])->name('league.reset');
Route::post('/league/edit-match', [TeamController::class, 'editMatch'])->name('league.editMatch');
