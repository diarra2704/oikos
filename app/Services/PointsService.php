<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\Membre;
use App\Models\MembreFormation;
use App\Models\PointLog;
use App\Models\Presence;
use App\Models\Rapport;
use App\Models\RapportMensuel;
use App\Models\User;
use Carbon\Carbon;

class PointsService
{
    /**
     * Paliers de points par pourcentage de présence au culte.
     * >= 40%: 1 pt, >= 60%: 2 pts, >= 80%: 3 pts
     */
    private const PALIERS_PRESENCE = [
        ['seuil' => 80, 'points' => 3],
        ['seuil' => 60, 'points' => 2],
        ['seuil' => 40, 'points' => 1],
    ];

    const PALIERS = [
        ['seuil' => 50, 'label' => 'Engagé', 'couleur' => '#10B981'],
        ['seuil' => 100, 'label' => 'Leader en herbe', 'couleur' => '#3B82F6'],
        ['seuil' => 200, 'label' => 'Champion', 'couleur' => '#8B5CF6'],
    ];

    /**
     * Vérifie si des points ont déjà été attribués pour cette action + référence.
     */
    private function dejaAttribue(int $userId, string $action, ?string $refType, $refId = null, ?string $description = null): bool
    {
        $q = PointLog::where('user_id', $userId)->where('action', $action);
        if ($refType) {
            $q->where('reference_type', $refType);
            $q->where('reference_id', $refId);
        }
        if ($description !== null) {
            $q->where('description', $description);
        }
        return $q->exists();
    }

    private function attribuer(User $user, string $action, int $points, string $description = '', $reference = null): ?PointLog
    {
        $refType = $reference ? get_class($reference) : null;
        $refId = $reference?->id;

        if ($this->dejaAttribue($user->id, $action, $refType, $refId)) {
            return null;
        }

        return PointLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'points' => $points,
            'description' => $description,
            'reference_type' => $refType,
            'reference_id' => $refId,
        ]);
    }

    private function attribuerPourPresenceCulte(User $user, int $points, string $description, string $dateStr): ?PointLog
    {
        if (PointLog::where('user_id', $user->id)
            ->where('action', 'presence_culte')
            ->where('description', 'like', "Présence culte {$dateStr}:%")
            ->exists()) {
            return null;
        }

        return PointLog::create([
            'user_id' => $user->id,
            'action' => 'presence_culte',
            'points' => $points,
            'description' => $description,
            'reference_type' => null,
            'reference_id' => null,
        ]);
    }

    /**
     * 1 - Présence des âmes au culte : selon pourcentage (>=40%: 1pt, >=60%: 2pts, >=80%: 3pts).
     * À appeler après chaque culte (ex: commande artisan ou job).
     */
    public function attribuerPointsPresenceCulte(Carbon $dateCulte): int
    {
        $faiseurs = User::where('role', 'faiseur')->get();
        $count = 0;
        $dateStr = $dateCulte->format('Y-m-d');

        foreach ($faiseurs as $faiseur) {
            $ames = Membre::where('suivi_par', $faiseur->id)->where('actif', true)->get();
            $total = $ames->count();
            if ($total === 0) {
                continue;
            }

            $amesIds = $ames->pluck('id');
            $presents = Presence::whereIn('membre_id', $amesIds)
                ->where('date_evenement', $dateCulte)
                ->where('type_evenement', 'culte')
                ->where('present', true)
                ->distinct()
                ->count('membre_id');

            $pourcent = (int) round(($presents / $total) * 100);

            $points = 0;
            foreach (self::PALIERS_PRESENCE as $p) {
                if ($pourcent >= $p['seuil']) {
                    $points = $p['points'];
                    break;
                }
            }

            if ($points > 0) {
                $desc = "Présence culte {$dateStr}: {$presents}/{$total} ({$pourcent}%)";
                $log = $this->attribuerPourPresenceCulte($faiseur, $points, $desc, $dateStr);
                if ($log) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * 2 - Invitation effective : 1 point par invité ayant assisté au culte.
     */
    public function attribuerPointsInvitationVenue(Invitation $invitation): ?PointLog
    {
        if (!$invitation->est_venu || !$invitation->inviteur_id) {
            return null;
        }

        $user = User::find($invitation->inviteur_id);
        if (!$user) {
            return null;
        }

        return $this->attribuer(
            $user,
            'invitation_venue',
            1,
            "Invité {$invitation->nom_invite} venu au culte",
            $invitation
        );
    }

    /**
     * Délai rapport hebdo : dimanche 20h de la semaine concernée.
     * Délai rapport mensuel : +2 jours après le dernier jour du mois.
     */
    public function rapportSoumisDansDelai(Rapport $rapport): bool
    {
        $createdAt = Carbon::parse($rapport->created_at);

        if ($rapport->type === 'hebdomadaire') {
            $finSemaine = Carbon::parse($rapport->periode_fin)->endOfDay()->setTime(20, 0);
            return $createdAt->lte($finSemaine);
        }

        if ($rapport->type === 'mensuel') {
            $finMois = Carbon::parse($rapport->periode_fin)->endOfMonth();
            $delai = $finMois->copy()->addDays(2)->endOfDay();
            return $createdAt->lte($delai);
        }

        return false;
    }

    /**
     * 3 - Rapport hebdo/mensuel soumis dans les délais : 1 point par rapport.
     */
    public function attribuerPointsRapport(Rapport $rapport): ?PointLog
    {
        if ($rapport->statut !== 'soumis') {
            return null;
        }

        $user = User::find($rapport->auteur_id);
        if (!$user) {
            return null;
        }

        if (!$this->rapportSoumisDansDelai($rapport)) {
            return null;
        }

        $typeLabel = $rapport->type === 'hebdomadaire' ? 'hebdo' : 'mensuel';
        return $this->attribuer(
            $user,
            'rapport_soumis',
            1,
            "Rapport {$typeLabel} soumis dans les délais",
            $rapport
        );
    }

    /**
     * Rapport mensuel (modèle RapportMensuel) : délai +2 jours après fin du mois.
     */
    public function rapportMensuelSoumisDansDelai(RapportMensuel $rapport): bool
    {
        $createdAt = Carbon::parse($rapport->created_at);
        $finMois = Carbon::parse($rapport->mois . '-01')->endOfMonth();
        $delai = $finMois->copy()->addDays(2)->endOfDay();
        return $createdAt->lte($delai);
    }

    public function attribuerPointsRapportMensuel(RapportMensuel $rapport): ?PointLog
    {
        $user = User::find($rapport->faiseur_id);
        if (!$user || !$this->rapportMensuelSoumisDansDelai($rapport)) {
            return null;
        }

        return $this->attribuer(
            $user,
            'rapport_mensuel_soumis',
            1,
            'Rapport mensuel soumis dans les délais',
            $rapport
        );
    }

    /**
     * 4 - Formation : 1 pt quand l'âme commence, 2 pts quand l'âme valide.
     */
    public function attribuerPointsFormationDebut(MembreFormation $formation): ?PointLog
    {
        if ($formation->statut_formation !== 'en_cours') {
            return null;
        }

        $membre = $formation->membre;
        if (!$membre || !$membre->suivi_par) {
            return null;
        }

        $user = User::find($membre->suivi_par);
        if (!$user) {
            return null;
        }

        return $this->attribuer(
            $user,
            'formation_debut',
            1,
            "Âme a commencé la formation {$formation->type_formation}",
            $formation
        );
    }

    public function attribuerPointsFormationValidee(MembreFormation $formation): ?PointLog
    {
        if ($formation->statut_formation !== 'validee') {
            return null;
        }

        $membre = $formation->membre;
        if (!$membre || !$membre->suivi_par) {
            return null;
        }

        $user = User::find($membre->suivi_par);
        if (!$user) {
            return null;
        }

        return $this->attribuer(
            $user,
            'formation_validee',
            2,
            "Âme a validé la formation {$formation->type_formation}",
            $formation
        );
    }

    /**
     * 5 - Famille d'impact : 1 pt régulier, 2 pts engagé.
     */
    public function attribuerPointsFamilleImpact(Membre $membre): ?PointLog
    {
        if (!$membre->statut_famille_impact || !$membre->suivi_par) {
            return null;
        }

        $user = User::find($membre->suivi_par);
        if (!$user) {
            return null;
        }

        $points = 0;
        $action = '';
        $desc = '';

        if ($membre->statut_famille_impact === 'est_regulier') {
            $points = 1;
            $action = 'famille_impact_regulier';
            $desc = 'Âme régulière dans une famille d\'impact';
        } elseif ($membre->statut_famille_impact === 'est_engage') {
            $points = 2;
            $action = 'famille_impact_engage';
            $desc = 'Âme engagée dans une famille d\'impact';
        }

        if ($points === 0) {
            return null;
        }

        return $this->attribuer($user, $action, $points, $desc, $membre);
    }

    /**
     * 6 - Service : 2 points quand l'âme commence le service dans un département.
     */
    public function attribuerPointsServiceDebut(Membre $membre): ?PointLog
    {
        if (!$membre->en_service_depuis || !$membre->suivi_par) {
            return null;
        }

        $user = User::find($membre->suivi_par);
        if (!$user) {
            return null;
        }

        return $this->attribuer(
            $user,
            'service_debut',
            2,
            "Âme a commencé le service dans un département",
            $membre
        );
    }

    /**
     * Méthode legacy pour compatibilité (BadgeController, TemoignageController).
     */
    public function attribuerPoints(User $user, string $action, string $description = '', $reference = null): PointLog
    {
        $pointsMap = [
            'presence' => 0,
            'invitation' => 1,
            'temoignage' => 5,
            'membre_amene' => 10,
            'rapport_soumis' => 1,
        ];
        $points = $pointsMap[$action] ?? 0;

        $log = PointLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'points' => $points,
            'description' => $description,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
        ]);

        return $log;
    }

    public function getTotalPoints(User $user): int
    {
        return PointLog::where('user_id', $user->id)->sum('points');
    }

    public function getPalierActuel(User $user): ?array
    {
        $total = $this->getTotalPoints($user);
        $palier = null;

        foreach (self::PALIERS as $p) {
            if ($total >= $p['seuil']) {
                $palier = $p;
            }
        }

        return $palier;
    }

    public function getProgression(User $user): array
    {
        $total = $this->getTotalPoints($user);
        $palierActuel = $this->getPalierActuel($user);
        $prochainPalier = null;

        foreach (self::PALIERS as $p) {
            if ($total < $p['seuil']) {
                $prochainPalier = $p;
                break;
            }
        }

        return [
            'total' => $total,
            'palier_actuel' => $palierActuel,
            'prochain_palier' => $prochainPalier,
            'progression' => $prochainPalier
                ? round(($total / $prochainPalier['seuil']) * 100)
                : 100,
        ];
    }
}
