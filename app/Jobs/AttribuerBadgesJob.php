<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AttribuerBadgesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(BadgeService $badgeService): void
    {
        $users = User::where('actif', true)->get();

        $totalAttribues = 0;

        foreach ($users as $user) {
            $nouveaux = $badgeService->verifierEtAttribuer($user);
            $totalAttribues += count($nouveaux);

            foreach ($nouveaux as $badge) {
                Log::info("Badge attribué: {$badge->nom} -> {$user->prenom} {$user->nom}");
            }
        }

        Log::info("AttribuerBadgesJob terminé: {$totalAttribues} badges attribués");
    }
}
