<?php

namespace App\Providers;

use App\Listeners\EnsureInstructorProfile;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Models\User;
use App\Policies\AdminOnlyPolicy;
use App\Policies\ResourcePolicy;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Events\RoleAttachedEvent;

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
        $this->configureDefaults();
        $this->registerModelPolicies();
        $this->registerAdminBypass();
        // Hold the instructor role and a matching profile row appears.
        Event::listen(RoleAttachedEvent::class, EnsureInstructorProfile::class);
        FilamentShield::enforcePolicies();

        // Compose lazily at render time: querying in boot() would run
        // before migrations in the test suite and on every CLI boot.
        View::composer('*', function ($view): void {
            $view->with('categories', Category::query()
                ->active()
                ->whereHas('courses', fn($query) => $query->published())
                ->withCount('courses')
                ->get());

            $view->with('siteSettings', SiteSetting::current());
        });
    }

    /**
     * Let admins (Shield super_admin role) pass every authorization check,
     * including the Shield-generated policies that rely on Spatie
     * permissions. Returning null defers to normal policies for everyone
     * else.
     */
    protected function registerAdminBypass(): void
    {
        Gate::before(function (?User $user, string $ability): ?bool {
            return $user !== null && $user->isAdmin() ? true : null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn(): ?Password => app()->isProduction()
                ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
                : null,
        );
    }

    /**
     * Map the Shield-aware policies onto every LMS model so Filament
     * resources are authorized without duplicating policy logic.
     *
     * Global platform data stays admin-only; every course-attached model
     * gets a per-model policy that pairs Shield permissions with record
     * ownership.
     */
    protected function registerModelPolicies(): void
    {
        $adminOnly = [
            Category::class,
            User::class,
            Order::class,
            Payment::class,
        ];

        foreach (ResourcePolicy::models() as $model) {
            Gate::policy($model, in_array($model, $adminOnly, true)
                ? AdminOnlyPolicy::class
                : 'App\\Policies\\' . class_basename($model) . 'Policy');
        }
    }
}
