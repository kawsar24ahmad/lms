<?php

namespace App\Listeners;

use App\Models\Instructor;
use App\Models\User;
use Spatie\Permission\Events\RoleAttachedEvent;

/**
 * Assigning the instructor role to a user should guarantee a matching
 * instructor profile, so the Instructors resource lists everyone who
 * holds the role. Assignment paths: UserFactory states, Filament form
 * create-option, and Shield's Roles UI (syncRoles delegates to
 * assignRole), all of which fire RoleAttachedEvent.
 */
class EnsureInstructorProfile
{
    public function handle(RoleAttachedEvent $event): void
    {
        if (! $event->model instanceof User) {
            return;
        }

        if (! $event->model->hasRole(User::ROLE_INSTRUCTOR)) {
            return;
        }

        Instructor::query()->firstOrCreate([
            'user_id' => $event->model->getKey(),
        ]);
    }
}
