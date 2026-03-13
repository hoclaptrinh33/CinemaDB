<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TmdbImportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\NotificationController as NotificationCenterController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\CollectionCommentController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CollectionNominationController;
use App\Http\Controllers\CollectionTitleNoteController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\Front;
use App\Http\Controllers\Front\LeaderboardController;
use App\Http\Controllers\Moderate;
use App\Http\Controllers\NominationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\TenorController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────────────────────
// Public routes (no auth required)
// ──────────────────────────────────────────────────────────

Route::middleware('cache.public')->group(function () {
    Route::get('/', [Front\HomeController::class, 'index'])->name('home');
    Route::get('/collections', [CollectionController::class, 'publicIndex'])->name('collections.public.index');

    Route::prefix('titles')->name('titles.')->group(function () {
        Route::get('/', [Front\TitleController::class, 'index'])->name('index');
        Route::get('/{slug}', [Front\TitleController::class, 'show'])->name('show');
    });

    Route::get('/persons/{person}', [Front\PersonController::class, 'show'])->name('persons.show');
    Route::get('/leaderboards', [LeaderboardController::class, 'index'])->name('leaderboards.index');
});
Route::get('/api/search', SearchController::class)->name('api.search');

// Notifications API (auth required)
Route::middleware('auth')->prefix('api/notifications')->name('api.notifications.')->group(function () {
    Route::get('/unread', [NotificationController::class, 'unread'])->name('unread');
    Route::post('/{id}/read', [NotificationController::class, 'markRead'])->name('read');
    Route::post('/read-all', [NotificationController::class, 'readAll'])->name('readAll');
});

// ──────────────────────────────────────────────────────────
// Authenticated user routes (login only)
// ──────────────────────────────────────────────────────────

Route::middleware('auth')->group(function () {
    // Profile view
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    // Upload avatar và cover — chỉ cần đăng nhập, không cần verified email
    // (user Google đã xác thực qua OAuth, không nên bị chặn bởi middleware verified)
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar');
    Route::post('/profile/cover', [ProfileController::class, 'uploadCover'])->name('profile.cover');

    // Activity Log
    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');

    // Notification Center
    Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');

    // Feed
    Route::get('/feed', [FeedController::class, 'index'])->name('feed');

    // Tenor GIF search proxy (protects the API key server-side)
    Route::get('/api/gifs', [TenorController::class, 'search'])
        ->middleware('throttle:30,1')
        ->name('gifs.search');
});

// ──────────────────────────────────────────────────────────
// Verified email required routes (login + verified)
// ──────────────────────────────────────────────────────────

Route::middleware(['auth', 'verified'])->group(function () {
    // Profile write
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Reviews
    Route::post('/titles/{title}/reviews', [ReviewController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('reviews.store');
    Route::patch('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/{review}/helpful', [ReviewController::class, 'helpful'])
        ->middleware('throttle:60,1')
        ->name('reviews.helpful');

    // Comments / Discussion
    Route::post('/titles/{title:title_id}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('comments.store');
    Route::patch('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/{comment}/like', [CommentController::class, 'like'])->name('comments.like');
    Route::post('/comments/{comment}/hide', [CommentController::class, 'hide'])
        ->middleware('role:ADMIN,MODERATOR')
        ->name('comments.hide');

    // Nominations
    Route::post('/titles/{title:title_id}/nominate', [NominationController::class, 'nominate'])->name('nominations.nominate');
    Route::delete('/titles/{title:title_id}/nominate', [NominationController::class, 'unnominate'])->name('nominations.unnominate');

    // Collections
    Route::get('/collections/create', fn() => inertia('Collections/Create'))->name('collections.create');
    Route::post('/collections', [CollectionController::class, 'store'])->name('collections.store');
    Route::patch('/collections/{collection:slug}', [CollectionController::class, 'update'])->name('collections.update');
    Route::delete('/collections/{collection:slug}', [CollectionController::class, 'destroy'])->name('collections.destroy');
    Route::post('/collections/{collection:slug}/titles', [CollectionController::class, 'addTitle'])->name('collections.titles.add');
    Route::delete('/collections/{collection:slug}/titles/{title:title_id}', [CollectionController::class, 'removeTitle'])->name('collections.titles.remove');

    // Collections — publish / copy / nominate
    Route::post('/collections/{collection:slug}/publish', [CollectionController::class, 'publish'])->name('collections.publish');
    Route::delete('/collections/{collection:slug}/publish', [CollectionController::class, 'unpublish'])->name('collections.unpublish');
    Route::post('/collections/{collection:slug}/copy', [CollectionController::class, 'copy'])->name('collections.copy');
    Route::post('/collections/{collection:slug}/cover', [CollectionController::class, 'uploadCover'])->name('collections.cover');
    Route::post('/collections/{collection:slug}/nominate', [CollectionNominationController::class, 'nominate'])->name('collections.nominate');
    Route::delete('/collections/{collection:slug}/nominate', [CollectionNominationController::class, 'unnominate'])->name('collections.unnominate');

    // Collection comments
    Route::post('/collections/{collection:slug}/comments', [CollectionCommentController::class, 'store'])->name('collection.comments.store');
    Route::patch('/collection-comments/{comment}', [CollectionCommentController::class, 'update'])->name('collection.comments.update');
    Route::delete('/collection-comments/{comment}', [CollectionCommentController::class, 'destroy'])->name('collection.comments.destroy');
    Route::post('/collection-comments/{comment}/like', [CollectionCommentController::class, 'like'])->name('collection.comments.like');
    Route::post('/collection-comments/{comment}/hide', [CollectionCommentController::class, 'hide'])
        ->middleware('role:ADMIN,MODERATOR')
        ->name('collection.comments.hide');

    // Collections — per-title notes & watch
    Route::put('/collections/{collection:slug}/titles/{title:title_id}/note', [CollectionTitleNoteController::class, 'upsert'])->name('collections.titles.note');
    Route::post('/collections/{collection:slug}/titles/{title:title_id}/watch', [CollectionTitleNoteController::class, 'toggleWatch'])->name('collections.titles.watch');

    // Collections — collaborators
    Route::post('/collections/{collection:slug}/collaborators', [CollaboratorController::class, 'store'])->name('collections.collaborators.invite');
    Route::post('/collections/{collection:slug}/collaborators/accept', [CollaboratorController::class, 'accept'])->name('collections.collaborators.accept');
    Route::delete('/collections/{collection:slug}/collaborators/{user}', [CollaboratorController::class, 'destroy'])->name('collections.collaborators.destroy');

    // Social — follow / unfollow
    Route::post('/users/{user}/follow', [FollowController::class, 'store'])->name('users.follow');
    Route::delete('/users/{user}/follow', [FollowController::class, 'destroy'])->name('users.unfollow');
});

// Public user profiles
Route::get('/users/{username}', [UserProfileController::class, 'show'])->name('users.show');

// Collections — public read
Route::get('/users/{user}/collections', [CollectionController::class, 'index'])->name('users.collections');
Route::get('/collections/{collection:slug}', [CollectionController::class, 'show'])->name('collections.show');

// ──────────────────────────────────────────────────────────
// Moderation routes (ADMIN or MODERATOR)
// ──────────────────────────────────────────────────────────

Route::prefix('moderate')
    ->name('moderate.')
    ->middleware(['auth', 'role:ADMIN,MODERATOR'])
    ->group(function () {
        Route::get('/reviews', [Moderate\ReviewController::class, 'index'])->name('reviews.index');
        Route::patch('/reviews/{review}/status', [Moderate\ReviewController::class, 'updateStatus'])->name('reviews.status');
        Route::delete('/reviews/{review}', [Moderate\ReviewController::class, 'destroy'])->name('reviews.destroy');

        Route::get('/audit-logs', [Moderate\AuditLogController::class, 'index'])->name('audit-logs.index');
    });

// ──────────────────────────────────────────────────────────
// Admin routes (ADMIN only)
// ──────────────────────────────────────────────────────────

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:ADMIN'])
    ->group(function () {
        // Dashboard
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // Titles CRUD
        Route::resource('titles', Admin\TitleController::class);

        // Seasons (nested under title)
        Route::prefix('titles/{title}')->name('titles.')->group(function () {
            Route::post('/seasons', [Admin\SeasonController::class, 'store'])->name('seasons.store');
            Route::patch('/seasons/{season}', [Admin\SeasonController::class, 'update'])->name('seasons.update');
            Route::delete('/seasons/{season}', [Admin\SeasonController::class, 'destroy'])->name('seasons.destroy');

            // Episodes (nested under season)
            Route::post('/seasons/{season}/episodes', [Admin\EpisodeController::class, 'store'])->name('seasons.episodes.store');
            Route::patch('/seasons/{season}/episodes/{episode}', [Admin\EpisodeController::class, 'update'])->name('seasons.episodes.update');
            Route::delete('/seasons/{season}/episodes/{episode}', [Admin\EpisodeController::class, 'destroy'])->name('seasons.episodes.destroy');
        });

        // Persons CRUD
        Route::resource('persons', Admin\PersonController::class);

        // Studios CRUD
        Route::resource('studios', Admin\StudioController::class);

        // Reviews management
        Route::get('/reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
        Route::patch('/reviews/{review}/status', [Admin\ReviewController::class, 'updateStatus'])->name('reviews.status');
        Route::delete('/reviews/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

        // Users management
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
        Route::patch('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/reputation', [Admin\UserController::class, 'adjustReputation'])->name('users.reputation');

        // Audit logs
        Route::get('/audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit-logs.index');

        // TMDB manual import
        Route::get('/tmdb-import', [Admin\TmdbImportController::class, 'index'])->name('tmdb-import.index');
        Route::get('/tmdb-import/logs', [Admin\TmdbImportController::class, 'logs'])->name('tmdb-import.logs');
        Route::post('/tmdb-import', [Admin\TmdbImportController::class, 'store'])->name('tmdb-import.store');
        Route::delete('/tmdb-import/{log}', [Admin\TmdbImportController::class, 'cancelJob'])->name('tmdb-import.cancel');
        Route::post('/tmdb-import/cancel-all', [Admin\TmdbImportController::class, 'cancelAll'])->name('tmdb-import.cancel-all');

        // Badges CRUD
        Route::resource('badges', Admin\BadgeController::class)->except(['show']);

        // Lookup tables CRUD
        Route::resource('countries', CountryController::class)->except(['show']);
        Route::resource('languages', LanguageController::class)->except(['show']);
        Route::resource('genres', GenreController::class)->except(['show']);
        Route::resource('roles', RoleController::class)->except(['show']);
    });

require __DIR__ . '/auth.php';
