<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\FixtureController;
use App\Http\Controllers\Api\LeagueController;
use App\Http\Controllers\Api\StandingController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Support\Facades\Route;

// Rutes públiques d'autenticació
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutes protegides
Route::middleware(['auth:sanctum', 'userStatus'])->group(function () {
    // Auth
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Countries
    Route::get('/countries', [CountryController::class, 'index'])
        ->middleware('permission:read_countries');
    Route::get('/countries/{country}', [CountryController::class, 'show'])
        ->middleware('permission:read_countries');

    // Leagues
    Route::get('/leagues', [LeagueController::class, 'index'])
        ->middleware('permission:read_leagues');
    Route::get('/leagues/{league}', [LeagueController::class, 'show'])
        ->middleware('permission:read_leagues');

    // Teams
    Route::get('/teams', [TeamController::class, 'index'])
        ->middleware('permission:read_teams');
    Route::get('/teams/{team}', [TeamController::class, 'show'])
        ->middleware('permission:read_teams');

    // Fixtures
    Route::get('/fixtures', [FixtureController::class, 'index'])
        ->middleware('permission:read_fixtures');
    Route::get('/fixtures/live', [FixtureController::class, 'live'])
        ->middleware('permission:read_fixtures');
    Route::get('/fixtures/today', [FixtureController::class, 'today'])
        ->middleware('permission:read_fixtures');
    Route::get('/fixtures/{fixture}', [FixtureController::class, 'show'])
        ->middleware('permission:read_fixtures');

    // Standings
    Route::get('/standings', [StandingController::class, 'index'])
        ->middleware('permission:read_standings');
    Route::get('/standings/league/{league}', [StandingController::class, 'byLeague'])
        ->middleware('permission:read_standings');
});
