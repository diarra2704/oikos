<?php

namespace App\Policies;

use App\Models\Cellule;
use App\Models\User;

class CellulePolicy
{
    public function view(User $user, Cellule $cellule): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSuperviseur()) {
            return $user->fd_id === $cellule->fd_id;
        }

        // Leader ou faiseur : uniquement sa cellule
        return $user->cellule_id === $cellule->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperviseur();
    }

    public function update(User $user, Cellule $cellule): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isSuperviseur() && $user->fd_id === $cellule->fd_id;
    }

    public function delete(User $user, Cellule $cellule): bool
    {
        return $user->isAdmin() || ($user->isSuperviseur() && $user->fd_id === $cellule->fd_id);
    }
}
