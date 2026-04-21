<?php

namespace App\Policies;

use App\Models\Athlete;
use App\Models\User;

class AthletesPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Athlete $athlete): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Athlete $athlete): bool
    {
        // Owner can update
        $authorized = $user->id == $athlete->owner_id;
        // Admin override: allow if user has is_admin flag set
        if (!$authorized && !empty($user->is_admin)) {
            $authorized = (bool) $user->is_admin;
        }
        return $authorized;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Athlete $athlete): bool
    {
        return $user->id === $athlete->owner_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Athlete $athlete): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Athlete $athlete): bool
    {
        return false;
    }
}
