<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\FamilleDisciples;
use App\Models\User;

class FamilleDiscipesPolicy
{
    /**
     * Admin voit toutes les FD. Superviseur voit uniquement sa FD.
     */
    public function view(User $user, FamilleDisciples $fd): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->fd_id === $fd->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, FamilleDisciples $fd): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isSuperviseur() && $user->fd_id === $fd->id;
    }

    public function delete(User $user, FamilleDisciples $fd): bool
    {
        return $user->isAdmin();
    }
}
