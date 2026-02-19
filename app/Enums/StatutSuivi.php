<?php

namespace App\Enums;

enum StatutSuivi: string
{
    case ACTIF = 'actif';
    case PAUSE = 'pause';
    case TERMINE = 'termine';

    public function label(): string
    {
        return match ($this) {
            self::ACTIF => 'Actif',
            self::PAUSE => 'En pause',
            self::TERMINE => 'Terminé',
        };
    }
}
