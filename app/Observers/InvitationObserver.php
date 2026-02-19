<?php

namespace App\Observers;

use App\Models\Invitation;
use App\Services\PointsService;

class InvitationObserver
{
    public function __construct(
        private PointsService $pointsService
    ) {}

    public function created(Invitation $invitation): void
    {
        if ($invitation->est_venu) {
            $this->pointsService->attribuerPointsInvitationVenue($invitation);
        }
    }

    public function updated(Invitation $invitation): void
    {
        if ($invitation->wasChanged('est_venu') && $invitation->est_venu) {
            $this->pointsService->attribuerPointsInvitationVenue($invitation);
        }
    }
}
