<?php

namespace App\Jobs;

use App\Models\Membre;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DetecterAbsencesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Détecter les membres absents depuis 3+ semaines
        $absents = Membre::where('actif', true)
            ->absentDepuis(3)
            ->with('faiseur')
            ->get();

        foreach ($absents as $membre) {
            Log::info("Alerte absence: {$membre->prenom} {$membre->nom} absent depuis 3+ semaines", [
                'membre_id' => $membre->id,
                'derniere_presence' => $membre->derniere_presence,
                'faiseur' => $membre->faiseur?->full_name,
            ]);
        }

        // Désactiver les membres absents depuis 8+ semaines
        $tresAbsents = Membre::where('actif', true)
            ->absentDepuis(8)
            ->get();

        foreach ($tresAbsents as $membre) {
            $membre->update(['actif' => false]);
            Log::warning("Membre désactivé (8+ semaines d'absence): {$membre->prenom} {$membre->nom}", [
                'membre_id' => $membre->id,
            ]);
        }

        Log::info("DetecterAbsencesJob terminé: {$absents->count()} alertes, {$tresAbsents->count()} désactivations");
    }
}
