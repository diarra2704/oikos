<?php

namespace App\Console\Commands;

use App\Services\PointsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculerPointsPresenceCulte extends Command
{
    protected $signature = 'points:presence-culte {date? : Date du culte (Y-m-d). Par défaut : dimanche dernier}';

    protected $description = 'Calcule et attribue les points de présence au culte pour les faiseurs (délai : chaque dimanche, après les présences)';

    public function handle(PointsService $pointsService): int
    {
        $dateStr = $this->argument('date');
        $date = $dateStr
            ? Carbon::parse($dateStr)->startOfDay()
            : Carbon::parse('last sunday');

        $count = $pointsService->attribuerPointsPresenceCulte($date);

        $this->info("Points présence culte attribués pour le {$date->format('d/m/Y')} : {$count} faiseur(s).");

        return Command::SUCCESS;
    }
}
