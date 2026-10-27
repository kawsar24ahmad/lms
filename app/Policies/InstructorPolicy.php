<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Instructor;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class InstructorPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Instructor');
    }

    public function view(AuthUser $authUser, Instructor $instructor): bool
    {
        return $authUser->can('View:Instructor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Instructor');
    }

    public function update(AuthUser $authUser, Instructor $instructor): bool
    {
        return $authUser->can('Update:Instructor');
    }

    public function delete(AuthUser $authUser, Instructor $instructor): bool
    {
        return $authUser->can('Delete:Instructor');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Instructor');
    }

    public function restore(AuthUser $authUser, Instructor $instructor): bool
    {
        return $authUser->can('Restore:Instructor');
    }

    public function forceDelete(AuthUser $authUser, Instructor $instructor): bool
    {
        return $authUser->can('ForceDelete:Instructor');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Instructor');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Instructor');
    }

    public function replicate(AuthUser $authUser, Instructor $instructor): bool
    {
        return $authUser->can('Replicate:Instructor');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Instructor');
    }
}
