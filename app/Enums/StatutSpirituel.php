<?php

namespace App\Enums;

enum StatutSpirituel: string
{
    case NA = 'NA';
    case NC = 'NC';
    case FIDELE = 'fidele';
    case STAR = 'STAR';
    case FAISEUR_DISCIPLE = 'faiseur_disciple';

    public function label(): string
    {
        return match ($this) {
            self::NA => 'Nouveau Arrivant',
            self::NC => 'Nouveau Converti',
            self::FIDELE => 'Fidèle',
            self::STAR => 'S.T.A.R',
            self::FAISEUR_DISCIPLE => 'Faiseur de Disciples',
        };
    }

    /**
     * Retourne le niveau de maturité spirituelle (pour la progression).
     */
    public function niveau(): int
    {
        return match ($this) {
            self::NA => 1,
            self::NC => 2,
            self::FIDELE => 3,
            self::STAR => 4,
            self::FAISEUR_DISCIPLE => 5,
        };
    }
}
