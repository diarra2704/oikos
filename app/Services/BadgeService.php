<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Invitation;
use App\Models\Membre;
use App\Models\Presence;
use App\Models\Temoignage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BadgeService
{
    /**
     * Vérifie et attribue automatiquement les badges pour un utilisateur.
     */
    public function verifierEtAttribuer(User $user): array
    {
        $badgesAttribues = [];
        $badges = Badge::all();

        foreach ($badges as $badge) {
            // Ne pas réattribuer
            if ($user->badges()->where('badge_id', $badge->id)->exists()) {
                continue;
            }

            if ($this->critereRempli($user, $badge)) {
                $user->badges()->attach($badge->id, ['attribue_le' => now()]);
                $badgesAttribues[] = $badge;
            }
        }

        return $badgesAttribues;
    }

    private function critereRempli(User $user, Badge $badge): bool
    {
        return match ($badge->slug) {
            'semeur' => $this->checkSemeur($user),
            'restaurateur' => $this->checkRestaurateur($user),
            'fidele' => $this->checkFidele($user),
            'transforme' => $this->checkTransforme($user),
            'pionnier' => $this->checkPionnier($user),
            'connecteur' => $this->checkConnecteur($user),
            'ambassadeur' => $this->checkAmbassadeur($user),
            'serviteur' => $this->checkServiteur($user),
            default => false,
        };
    }

    // Semeur: a invité 3+ personnes
    private function checkSemeur(User $user): bool
    {
        return Invitation::where('inviteur_id', $user->id)->count() >= 3;
    }

    // Restaurateur: a aidé à réengager 2 membres éloignés
    private function checkRestaurateur(User $user): bool
    {
        $mesAmes = Membre::where('suivi_par', $user->id)->pluck('id');
        if ($mesAmes->isEmpty()) return false;

        // Compter les membres qui étaient inactifs et sont redevenus actifs
        return Membre::whereIn('id', $mesAmes)
            ->where('actif', true)
            ->whereNotNull('derniere_presence')
            ->count() >= 2;
    }

    // Fidèle: 100% de participation sur les 3 derniers mois (12+ semaines)
    private function checkFidele(User $user): bool
    {
        $mesAmes = Membre::where('suivi_par', $user->id)->pluck('id');
        if ($mesAmes->isEmpty()) return false;

        $troisMois = now()->subMonths(3);
        $totalPointages = Presence::whereIn('membre_id', $mesAmes)
            ->where('date_evenement', '>=', $troisMois)
            ->count();

        $totalPresents = Presence::whereIn('membre_id', $mesAmes)
            ->where('date_evenement', '>=', $troisMois)
            ->where('present', true)
            ->count();

        return $totalPointages >= 12 && $totalPresents === $totalPointages;
    }

    // Transformé: a partagé un témoignage validé
    private function checkTransforme(User $user): bool
    {
        return Temoignage::where('user_id', $user->id)
            ->where('statut', 'valide')
            ->exists();
    }

    // Pionnier: leader de cellule ou superviseur
    private function checkPionnier(User $user): bool
    {
        return in_array($user->role->value, ['superviseur', 'leader_cellule']);
    }

    // Connecteur: a intégré 3+ nouveaux membres dans sa FD
    private function checkConnecteur(User $user): bool
    {
        return Membre::where('invite_par', $user->id)->count() >= 3;
    }

    // Ambassadeur: 5+ invitations
    private function checkAmbassadeur(User $user): bool
    {
        return Invitation::where('inviteur_id', $user->id)->count() >= 5;
    }

    // Serviteur: a soumis 10+ rapports
    private function checkServiteur(User $user): bool
    {
        return $user->rapports()->where('statut', '!=', 'brouillon')->count() >= 10;
    }

    /**
     * Récupère tous les badges avec statut pour un utilisateur.
     */
    public function getBadgesAvecStatut(User $user): array
    {
        $badges = Badge::all();
        $userBadgeIds = $user->badges()->pluck('badge_id')->toArray();

        return $badges->map(function ($badge) use ($userBadgeIds) {
            return [
                'id' => $badge->id,
                'nom' => $badge->nom,
                'slug' => $badge->slug,
                'description' => $badge->description,
                'icone' => $badge->icone,
                'couleur' => $badge->couleur,
                'obtenu' => in_array($badge->id, $userBadgeIds),
            ];
        })->toArray();
    }
}
