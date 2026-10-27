<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Records owned by course instructors: courses and everything attached to
 * them. Instructors manage only their own courses, and only when their
 * Shield role grants the matching permission for the resource. Admins are
 * bypassed by the global gate rule; students are fully excluded.
 *
 * Register one concrete subclass per model (e.g. QuizPolicy) so the
 * class-level abilities know which Shield permission to check.
 */
class OwnedResourcePolicy extends ResourcePolicy
{
    /**
     * The model class this concrete policy governs; set by subclasses.
     */
    protected static string $resourceModel;

    public function viewAny(?User $user): bool
    {
        return $user !== null
            && $user->isInstructor()
            && $this->hasShieldPermission($user, 'ViewAny', static::$resourceModel);
    }

    public function view(?User $user, Model $model): bool
    {
        return $this->manages($user, $model, 'View');
    }

    public function create(?User $user): bool
    {
        return $user !== null
            && $user->isInstructor()
            && $this->hasShieldPermission($user, 'Create', static::$resourceModel);
    }

    public function update(?User $user, Model $model): bool
    {
        return $this->manages($user, $model, 'Update');
    }

    public function delete(?User $user, Model $model): bool
    {
        return $this->manages($user, $model, 'Delete');
    }

    public function deleteAny(?User $user): bool
    {
        return $user !== null
            && $user->isInstructor()
            && $this->hasShieldPermission($user, 'DeleteAny', static::$resourceModel);
    }

    public function reorder(?User $user): bool
    {
        return $user !== null
            && $user->isInstructor()
            && $this->hasShieldPermission($user, 'Reorder', static::$resourceModel);
    }

    public function replicate(?User $user, Model $model): bool
    {
        return $this->manages($user, $model, 'Replicate');
    }

    public function restore(?User $user, Model $model): bool
    {
        return $this->manages($user, $model, 'Restore');
    }

    public function forceDelete(?User $user, Model $model): bool
    {
        return $this->manages($user, $model, 'ForceDelete');
    }

    public function restoreAny(?User $user): bool
    {
        return $user !== null
            && $user->isInstructor()
            && $this->hasShieldPermission($user, 'RestoreAny', static::$resourceModel);
    }

    public function forceDeleteAny(?User $user): bool
    {
        return $user !== null
            && $user->isInstructor()
            && $this->hasShieldPermission($user, 'ForceDeleteAny', static::$resourceModel);
    }
}
