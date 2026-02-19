<?php

namespace App\Observers;

use App\Models\Membre;
use App\Services\PointsService;

class MembreObserver
{
    public function __construct(
        private PointsService $pointsService
    ) {}

    public function updated(Membre $membre): void
    {
        if ($membre->wasChanged('statut_famille_impact') && in_array($membre->statut_famille_impact, ['est_regulier', 'est_engage'], true)) {
            $this->pointsService->attribuerPointsFamilleImpact($membre);
        }

        if ($membre->wasChanged('en_service_depuis') && $membre->en_service_depuis !== null) {
            $this->pointsService->attribuerPointsServiceDebut($membre);
        }
    }
}
