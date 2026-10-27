<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Global platform data — categories and users — that only administrators
 * may manage. Instructors only need course-level resources.
 */
class AdminOnlyPolicy extends ResourcePolicy
{
    public function viewAny(?User $user): bool
    {
        return $user !== null && $user->isAdmin();
    }

    public function view(?User $user, Model $model): bool
    {
        return $user !== null && $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Model $model): bool
    {
        return $user !== null && $user->isAdmin();
    }

    public function delete(User $user, Model $model): bool
    {
        return $user !== null && $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user !== null && $user->isAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user !== null && $user->isAdmin();
    }
}
