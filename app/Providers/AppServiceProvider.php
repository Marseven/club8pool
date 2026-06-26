<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\MatchIncident;
use App\Models\Player;
use App\Models\Registration;

use App\Policies\CompetitionPolicy;
use App\Policies\MatchIncidentPolicy;
use App\Policies\MatchPolicy;
use App\Policies\PlayerPolicy;
use App\Policies\RegistrationPolicy;

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
        Gate::policy(Competition::class, CompetitionPolicy::class);
        Gate::policy(GameMatch::class, MatchPolicy::class);
        Gate::policy(Player::class, PlayerPolicy::class);
        Gate::policy(Registration::class, RegistrationPolicy::class);
        Gate::policy(MatchIncident::class, MatchIncidentPolicy::class);
    }
}
