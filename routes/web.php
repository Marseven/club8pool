<?php

use App\Http\Controllers\Admin\CompetitionController as AdminCompetitionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DrawController;
use App\Http\Controllers\Admin\MatchController;
use App\Http\Controllers\Admin\PlayerController as AdminPlayerController;
use App\Http\Controllers\Admin\RefereeController as AdminRefereeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Public\CompetitionController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\LiveController;
use App\Http\Controllers\Public\PlayerController;
use App\Http\Controllers\Public\RegisterController;
use App\Http\Controllers\Public\TournamentsController;
use App\Http\Controllers\Public\TvController;
use App\Http\Controllers\Referee\RefereeController;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/', LandingController::class)->name('home');
Route::get('/competitions', [CompetitionController::class, 'show'])->name('competition.current');
Route::get('/competitions/{slug}', [CompetitionController::class, 'show'])->name('competition.show');
Route::get('/joueurs', [PlayerController::class, 'index'])->name('players.index');
Route::get('/joueurs/{player}', [PlayerController::class, 'show'])->name('player.show');
Route::get('/inscription', [RegisterController::class, 'index'])->name('register.index');
Route::get('/inscription/{competition:slug}', [RegisterController::class, 'show'])->name('register.show');
Route::post('/inscription/{competition:slug}', [RegisterController::class, 'store'])->name('register.store');
Route::get('/tournois', [TournamentsController::class, 'index'])->name('tournaments.index');
Route::get('/live', LiveController::class)->name('live');
Route::get('/tv', [TvController::class, 'show'])->name('tv');
Route::get('/tv/table/{tableId}', [TvController::class, 'show'])->name('tv.table');

// Auth
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/competitions', [AdminCompetitionController::class, 'index'])->name('competitions.index');
    Route::get('/competitions/nouvelle', [AdminCompetitionController::class, 'create'])->name('competitions.create');
    Route::post('/competitions', [AdminCompetitionController::class, 'store'])->name('competitions.store');
    Route::get('/competitions/{competition}', [AdminCompetitionController::class, 'show'])->name('competitions.show');
    Route::get('/competitions/{competition}/edit', [AdminCompetitionController::class, 'edit'])->name('competitions.edit');
    Route::patch('/competitions/{competition}', [AdminCompetitionController::class, 'update'])->name('competitions.update');
    Route::post('/competitions/{competition}/logo', [AdminCompetitionController::class, 'uploadLogo'])->name('competitions.logo.upload');
    Route::delete('/competitions/{competition}/logo', [AdminCompetitionController::class, 'removeLogo'])->name('competitions.logo.remove');

    Route::get('/tirage', [DrawController::class, 'show'])->name('draw');
    Route::post('/tirage', [DrawController::class, 'commit'])->name('draw.commit');

    Route::get('/phase-finale', [\App\Http\Controllers\Admin\KnockoutController::class, 'show'])->name('knockout.show');
    Route::post('/phase-finale', [\App\Http\Controllers\Admin\KnockoutController::class, 'generate'])->name('knockout.generate');
    Route::post('/phase-finale/matchs/{match}/lancer', [\App\Http\Controllers\Admin\KnockoutController::class, 'startMatch'])->name('knockout.match.start');
    Route::post('/phase-finale/matchs/{match}/frame', [\App\Http\Controllers\Admin\KnockoutController::class, 'scoreFrame'])->name('knockout.match.frame');
    Route::post('/phase-finale/matchs/{match}/undo', [\App\Http\Controllers\Admin\KnockoutController::class, 'undoFrame'])->name('knockout.match.undo');
    Route::post('/phase-finale/matchs/{match}/clore', [\App\Http\Controllers\Admin\KnockoutController::class, 'closeMatch'])->name('knockout.match.close');

    Route::get('/poules', [\App\Http\Controllers\Admin\PoolController::class, 'index'])->name('pools.index');
    Route::patch('/poules/matchs/{match}', [\App\Http\Controllers\Admin\PoolController::class, 'updateMatch'])->name('pools.matches.update');
    Route::post('/poules/matchs/{match}/reset', [\App\Http\Controllers\Admin\PoolController::class, 'resetMatch'])->name('pools.matches.reset');
    Route::post('/poules/matchs/{match}/frame', [\App\Http\Controllers\Admin\PoolController::class, 'scoreFrame'])->name('pools.matches.frame');
    Route::post('/poules/matchs/{match}/undo', [\App\Http\Controllers\Admin\PoolController::class, 'undoFrame'])->name('pools.matches.undo');
    Route::post('/poules/matchs/{match}/lancer', [\App\Http\Controllers\Admin\PoolController::class, 'startMatch'])->name('pools.matches.start');

    Route::get('/import', [\App\Http\Controllers\Admin\ImportController::class, 'show'])->name('import.show');
    Route::post('/import', [\App\Http\Controllers\Admin\ImportController::class, 'preview'])->name('import.preview');
    Route::post('/import/confirm', [\App\Http\Controllers\Admin\ImportController::class, 'commit'])->name('import.commit');
    Route::post('/import/annuler', [\App\Http\Controllers\Admin\ImportController::class, 'cancel'])->name('import.cancel');

    Route::patch('/matchs/{match}', [MatchController::class, 'update'])->name('matches.update');

    Route::get('/joueurs', [AdminPlayerController::class, 'index'])->name('players.index');
    Route::post('/joueurs', [AdminPlayerController::class, 'store'])->name('players.store');

    Route::get('/arbitres', [AdminRefereeController::class, 'index'])->name('referees.index');
    Route::post('/arbitres', [AdminRefereeController::class, 'store'])->name('referees.store');

    Route::get('/classement', [\App\Http\Controllers\Admin\RatingController::class, 'index'])->name('rating.index');

    Route::get('/competitions/{competition}/stats', [\App\Http\Controllers\Admin\StatsController::class, 'show'])->name('competitions.stats');
    Route::post('/competitions/{competition}/stats/recalculate', [\App\Http\Controllers\Admin\StatsController::class, 'recalculate'])->name('competitions.stats.recalculate');
});

// Référee (espace mobile web fallback)
Route::middleware(['auth', 'referee'])->prefix('arbitre')->name('referee.')->group(function () {
    Route::get('/', [RefereeController::class, 'queue'])->name('queue');
    Route::get('/tables', [RefereeController::class, 'tables'])->name('tables');
    Route::get('/match/{match}/pre', [RefereeController::class, 'preMatch'])->name('match.pre');
    Route::get('/match/{match}/live', [RefereeController::class, 'live'])->name('match.live');
    Route::get('/match/{match}/fin', [RefereeController::class, 'endMatch'])->name('match.end');
    Route::post('/match/{match}/frame', [RefereeController::class, 'commitFrame'])->name('match.frame');
    Route::post('/match/{match}/signer', [RefereeController::class, 'sign'])->name('match.sign');
    Route::post('/match/{match}/claim', [RefereeController::class, 'claim'])->name('match.claim');
});
