<?php

namespace App\Policies;

use App\Models\Membre;
use App\Models\User;

class MembrePolicy
{
    /**
     * Admin voit tout. Superviseur voit les membres de sa FD.
     * Leader voit les membres de sa cellule. Faiseur voit ses âmes.
     */
    public function view(User $user, Membre $membre): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSuperviseur()) {
            return $user->fd_id === $membre->fd_id;
        }

        if ($user->isLeaderCellule()) {
            return $user->cellule_id === $membre->cellule_id;
        }

        // Faiseur : uniquement ses âmes
        return $membre->suivi_par === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRoleAtLeast(\App\Enums\Role::FAISEUR);
    }

    public function update(User $user, Membre $membre): bool
    {
        return $this->view($user, $membre);
    }

    public function delete(User $user, Membre $membre): bool
    {
        return $user->isAdmin() || ($user->isSuperviseur() && $user->fd_id === $membre->fd_id);
    }
}
