<?php

namespace App\Policies;

use App\Models\Text;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TextPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->id === $text->user_id; //can the user see this text??
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Text $text): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Text $text): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Text $text): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Text $text): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Text $text): bool
    {
        return false;
    }
}
