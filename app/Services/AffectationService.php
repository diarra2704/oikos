<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Membre;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AffectationService
{
    /**
     * Tranches d'age et leurs bornes.
     */
    private const TRANCHES = [
        '-18'   => [0, 17],
        '18-24' => [18, 24],
        '25-30' => [25, 30],
        '31-35' => [31, 35],
        '36-40' => [36, 40],
        '41-45' => [41, 45],
        '46-50' => [46, 50],
        '+51'   => [51, 200],
    ];

    /**
     * Poids de chaque critere dans le score final (sur 100).
     */
    private const POIDS_GENRE = 40;
    private const POIDS_AGE = 35;
    private const POIDS_CHARGE = 25;

    /**
     * Suggere les meilleurs faiseurs pour une ame donnee.
     *
     * @param int|null $fdId       FD dans laquelle chercher
     * @param string|null $genre   Genre de l'ame (M/F)
     * @param string|null $dateNaissance Date de naissance de l'ame
     * @param int $limit           Nombre max de suggestions
     * @return Collection          Collection triee par score decroissant
     */
    public function suggerer(?int $fdId, ?string $genre, ?string $dateNaissance, int $limit = 5): Collection
    {
        // Recuperer les faiseurs eligibles (actifs, dans la FD)
        $query = User::whereIn('role', [Role::FAISEUR, Role::LEADER_CELLULE])
            ->where('actif', true);

        if ($fdId) {
            $query->where('fd_id', $fdId);
        }

        $faiseurs = $query->withCount(['membresAffecter as nb_ames' => function ($q) {
            $q->where('actif', true);
        }])->get();

        if ($faiseurs->isEmpty()) {
            return collect();
        }

        // Age de l'ame
        $ageAme = null;
        $trancheAme = null;
        if ($dateNaissance) {
            $ageAme = Carbon::parse($dateNaissance)->age;
            $trancheAme = $this->getTranche($ageAme);
        }

        // Charge min/max pour normaliser
        $charges = $faiseurs->pluck('nb_ames');
        $chargeMin = $charges->min();
        $chargeMax = $charges->max();

        // Calculer le score pour chaque faiseur
        $resultats = $faiseurs->map(function (User $faiseur) use ($genre, $ageAme, $trancheAme, $chargeMin, $chargeMax) {
            $scoreGenre = $this->scorerGenre($faiseur, $genre);
            $scoreAge = $this->scorerAge($faiseur, $ageAme, $trancheAme);
            $scoreCharge = $this->scorerCharge($faiseur->nb_ames, $chargeMin, $chargeMax);

            $scoreTotal = round(
                ($scoreGenre * self::POIDS_GENRE +
                 $scoreAge * self::POIDS_AGE +
                 $scoreCharge * self::POIDS_CHARGE) / 100,
                1
            );

            // Age et tranche du faiseur
            $ageFaiseur = $faiseur->date_naissance ? $faiseur->date_naissance->age : null;
            $trancheFaiseur = $ageFaiseur !== null ? $this->getTranche($ageFaiseur) : null;

            return [
                'id' => $faiseur->id,
                'nom' => $faiseur->nom,
                'prenom' => $faiseur->prenom,
                'genre' => $faiseur->genre,
                'age' => $ageFaiseur,
                'tranche' => $trancheFaiseur,
                'nb_ames' => $faiseur->nb_ames,
                'cellule_id' => $faiseur->cellule_id,
                'score_total' => $scoreTotal,
                'score_genre' => round($scoreGenre * 100),
                'score_age' => round($scoreAge * 100),
                'score_charge' => round($scoreCharge * 100),
                'details' => $this->buildDetails($faiseur, $genre, $trancheAme, $trancheFaiseur, $faiseur->nb_ames, $chargeMin),
            ];
        });

        return $resultats->sortByDesc('score_total')->take($limit)->values();
    }

    /**
     * Score genre : 1.0 si meme genre, 0.3 si genre inconnu, 0.0 si different.
     */
    private function scorerGenre(User $faiseur, ?string $genreAme): float
    {
        if (!$genreAme || !$faiseur->genre) {
            return 0.3; // Pas assez d'info
        }
        return $faiseur->genre === $genreAme ? 1.0 : 0.0;
    }

    /**
     * Score age :
     * - 1.0 si meme tranche
     * - 0.7 si tranche adjacente
     * - 0.4 si 2 tranches d'ecart
     * - 0.1 si plus
     * - 0.5 si info manquante
     */
    private function scorerAge(User $faiseur, ?int $_ageAme, ?string $trancheAme): float
    {
        if (!$trancheAme || !$faiseur->date_naissance) {
            return 0.5;
        }

        $ageFaiseur = $faiseur->date_naissance->age;
        $trancheFaiseur = $this->getTranche($ageFaiseur);

        if ($trancheAme === $trancheFaiseur) {
            return 1.0;
        }

        // Calculer la distance en nombre de tranches
        $trancheKeys = array_keys(self::TRANCHES);
        $idxAme = array_search($trancheAme, $trancheKeys);
        $idxFaiseur = array_search($trancheFaiseur, $trancheKeys);
        $distance = abs($idxAme - $idxFaiseur);

        return match (true) {
            $distance === 1 => 0.7,
            $distance === 2 => 0.4,
            default => 0.1,
        };
    }

    /**
     * Score charge : favorise les faiseurs les moins charges.
     * 1.0 si charge minimale, decroit lineairement.
     */
    private function scorerCharge(int $charge, int $chargeMin, int $chargeMax): float
    {
        if ($chargeMax === $chargeMin) {
            return 1.0;
        }
        return 1.0 - (($charge - $chargeMin) / ($chargeMax - $chargeMin));
    }

    /**
     * Determine la tranche d'age.
     */
    private function getTranche(int $age): string
    {
        foreach (self::TRANCHES as $label => [$min, $max]) {
            if ($age >= $min && $age <= $max) {
                return $label;
            }
        }
        return '+51';
    }

    /**
     * Construit un texte explicatif pour le frontend.
     */
    private function buildDetails(User $faiseur, ?string $genreAme, ?string $trancheAme, ?string $trancheFaiseur, int $nbAmes, int $chargeMin): array
    {
        $tags = [];

        // Genre
        if ($genreAme && $faiseur->genre) {
            if ($faiseur->genre === $genreAme) {
                $tags[] = ['label' => 'Meme genre', 'color' => 'emerald'];
            } else {
                $tags[] = ['label' => 'Genre different', 'color' => 'red'];
            }
        }

        // Age
        if ($trancheAme && $trancheFaiseur) {
            if ($trancheAme === $trancheFaiseur) {
                $tags[] = ['label' => "Meme tranche ({$trancheFaiseur})", 'color' => 'emerald'];
            } else {
                $trancheKeys = array_keys(self::TRANCHES);
                $distance = abs(array_search($trancheAme, $trancheKeys) - array_search($trancheFaiseur, $trancheKeys));
                if ($distance === 1) {
                    $tags[] = ['label' => "Tranche proche ({$trancheFaiseur})", 'color' => 'amber'];
                } else {
                    $tags[] = ['label' => "Tranche {$trancheFaiseur}", 'color' => 'red'];
                }
            }
        }

        // Charge
        if ($nbAmes === $chargeMin) {
            $tags[] = ['label' => "{$nbAmes} ames (le moins charge)", 'color' => 'emerald'];
        } else {
            $tags[] = ['label' => "{$nbAmes} ames", 'color' => $nbAmes > $chargeMin + 3 ? 'red' : 'amber'];
        }

        return $tags;
    }
}
