<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Club\AttendanceController;
use App\Http\Controllers\Club\ExpenseController;
use App\Http\Controllers\Club\FundController;
use App\Http\Controllers\Club\KitPurchaseController;
use App\Http\Controllers\Club\MatchController;
use App\Http\Controllers\Club\MemberController;
use App\Http\Controllers\ClubPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/tin-tuc', [ClubPageController::class, 'news'])->name('news.index');
Route::get('/thanh-vien', [ClubPageController::class, 'members'])->name('members.index');
Route::get('/thong-ke', [ClubPageController::class, 'stats'])->name('stats.index');

Route::middleware('guest')->group(function () {
    Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::name('club.')->group(function () {
            Route::resource('members', MemberController::class)->except(['show', 'create', 'edit']);
            Route::resource('matches', MatchController::class)
                ->parameters(['matches' => 'gameMatch'])
                ->except(['create', 'edit']);
            Route::put('matches/{gameMatch}/stats', [MatchController::class, 'updateStats'])
                ->name('matches.stats.update');
            Route::delete('funds/groups/{groupKey}', [FundController::class, 'destroyGroup'])
                ->name('funds.groups.destroy');
            Route::resource('funds', FundController::class)
                ->parameters(['funds' => 'fundContribution'])
                ->except(['show', 'create', 'edit']);
            Route::resource('expenses', ExpenseController::class)
                ->parameters(['expenses' => 'fundExpense'])
                ->except(['show', 'create', 'edit']);
            Route::get('kits', [KitPurchaseController::class, 'index'])->name('kits.index');
            Route::put('kits/{kitPurchase}', [KitPurchaseController::class, 'update'])->name('kits.update');

            Route::get('attendances', [AttendanceController::class, 'index'])->name('attendances.index');
            Route::get('attendances/{gameMatch}', [AttendanceController::class, 'edit'])->name('attendances.edit');
            Route::put('attendances/{gameMatch}', [AttendanceController::class, 'update'])->name('attendances.update');
        });
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
