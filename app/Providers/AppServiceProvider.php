<?php

namespace App\Providers;

use App\Events\HelpfulVoteToggled;
use App\Events\ReviewCreated;
use App\Events\UserFollowed;
use App\Listeners\AwardBadgesOnReview;
use App\Listeners\SendFollowNotification;
use App\Listeners\UpdateReputationOnHelpful;
use App\Listeners\UpdateReputationOnReview;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use App\Policies\ReviewPolicy;
use App\Policies\TitlePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::policy(Title::class, TitlePolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // Gamification events
        Event::listen(ReviewCreated::class, AwardBadgesOnReview::class);
        Event::listen(ReviewCreated::class, UpdateReputationOnReview::class);
        Event::listen(HelpfulVoteToggled::class, UpdateReputationOnHelpful::class);

        // Social follow
        Event::listen(UserFollowed::class, SendFollowNotification::class);
    }
}
