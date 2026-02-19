<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Membre;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnniversaireController extends Controller
{
    /**
     * Liste des anniversaires (naissance + conversion) pour un mois donné.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $mois = (int) $request->input('mois', now()->month);
        $annee = (int) $request->input('annee', now()->year);

        $query = Membre::query();
        if ($user->role === Role::SUPERVISEUR && $user->fd_id) {
            $query->where('fd_id', $user->fd_id);
        } elseif ($user->role === Role::LEADER_CELLULE && $user->cellule_id) {
            $query->where('cellule_id', $user->cellule_id);
        } elseif ($user->role === Role::FAISEUR) {
            $query->where('suivi_par', $user->id);
        }

        $naissance = (clone $query)
            ->anniversairesNaissanceMois($mois, $annee)
            ->orderByRaw('DAY(date_naissance)')
            ->get()
            ->map(fn (Membre $m) => [
                'id' => $m->id,
                'label' => $m->full_name,
                'date' => $m->date_naissance?->format('d/m/Y'),
                'jour' => (int) $m->date_naissance?->day,
                'type' => 'naissance',
            ]);

        $conversion = (clone $query)
            ->anniversairesConversionMois($mois, $annee)
            ->orderByRaw('DAY(date_conversion)')
            ->get()
            ->map(fn (Membre $m) => [
                'id' => $m->id,
                'label' => $m->full_name,
                'date' => $m->date_conversion?->format('d/m/Y'),
                'jour' => (int) $m->date_conversion?->day,
                'type' => 'conversion',
            ]);

        $moisLabel = Carbon::createFromDate($annee, $mois, 1)->locale('fr_FR')->translatedFormat('F Y');
        $moisOptions = $this->getMoisOptions();

        return Inertia::render('Anniversaires/Index', [
            'naissance' => $naissance->values()->all(),
            'conversion' => $conversion->values()->all(),
            'mois' => $mois,
            'annee' => $annee,
            'mois_label' => $moisLabel,
            'mois_options' => $moisOptions,
        ]);
    }

    private function getMoisOptions(): array
    {
        $out = [];
        for ($m = 1; $m <= 12; $m++) {
            $out[] = [
                'value' => $m,
                'label' => Carbon::createFromDate(2000, $m, 1)->locale('fr_FR')->translatedFormat('F'),
            ];
        }
        return $out;
    }
}
