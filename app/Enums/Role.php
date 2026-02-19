<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case SUPERVISEUR = 'superviseur';
    case LEADER_CELLULE = 'leader_cellule';
    case FAISEUR = 'faiseur';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrateur',
            self::SUPERVISEUR => 'Superviseur de FD',
            self::LEADER_CELLULE => 'Leader de Cellule',
            self::FAISEUR => 'Faiseur de Disciples',
        };
    }

    /**
     * Vérifie si le rôle a un niveau hiérarchique >= au rôle donné.
     */
    public function isAtLeast(self $role): bool
    {
        return $this->level() >= $role->level();
    }

    public function level(): int
    {
        return match ($this) {
            self::ADMIN => 40,
            self::SUPERVISEUR => 30,
            self::LEADER_CELLULE => 20,
            self::FAISEUR => 10,
        };
    }
}
