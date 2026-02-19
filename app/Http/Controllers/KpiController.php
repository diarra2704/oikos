<?php

namespace App\Http\Controllers;

use App\Services\KpiService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KpiController extends Controller
{
    public function __construct(
        private KpiService $kpiService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $debut = $request->get('debut', now()->startOfMonth()->format('Y-m-d'));
        $fin = $request->get('fin', now()->format('Y-m-d'));

        if ($user->isAdmin()) {
            // Admin voit les KPI globaux
            $kpis = $this->kpiService->global($debut, $fin);

            return Inertia::render('Kpi/Index', [
                'kpis' => $kpis,
                'periode' => ['debut' => $debut, 'fin' => $fin],
                'mode' => 'global',
            ]);
        }

        // Superviseur : KPI de sa FD uniquement
        if ($user->isSuperviseur() && $user->fd_id) {
            $kpis = $this->kpiService->pourFd(
                $user->fd_id,
                \Illuminate\Support\Carbon::parse($debut),
                \Illuminate\Support\Carbon::parse($fin)
            );

            $fd = \App\Models\FamilleDisciples::find($user->fd_id);

            return Inertia::render('Kpi/FdKpi', [
                'kpis' => $kpis,
                'fd' => $fd,
                'periode' => ['debut' => $debut, 'fin' => $fin],
            ]);
        }

        abort(403, 'Accès non autorisé.');
    }
}
