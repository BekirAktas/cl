<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Team\TeamController;

Route::post('/generate-fixtures', [TeamController::class, 'generateFixtures'])->name('api.generateFixtures');
Route::post('/league/play-week', [TeamController::class, 'playWeek'])->name('api.league.playWeek');
Route::post('/league/play-all', [TeamController::class, 'playAll'])->name('api.league.playAll');
Route::post('/league/reset', [TeamController::class, 'reset'])->name('api.league.reset');
Route::post('/league/edit-match', [TeamController::class, 'editMatch'])->name('api.league.editMatch');