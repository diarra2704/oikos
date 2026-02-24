<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\Membre;
use App\Models\Temoignage;
use App\Models\Transfert;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'telephone' => $user->telephone,
                    'role' => $user->role->value,
                    'statut_spirituel' => $user->statut_spirituel->value,
                    'fd_id' => $user->fd_id,
                    'cellule_id' => $user->cellule_id,
                    'actif' => $user->actif,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'generated_password' => fn () => $request->session()->get('generated_password'),
                'generated_user_email' => fn () => $request->session()->get('generated_user_email'),
            ],
            'notifications' => fn () => $this->notifications($request),
        ];
    }

    /**
     * Notifications selon le rôle : transferts en attente, témoignages à valider, membres absents.
     *
     * @return array{items: array<int, array{type: string, label: string, url: string, count: int}>, total: int}
     */
    private function notifications(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            return ['items' => [], 'total' => 0];
        }

        $items = [];

        // Transferts en attente (admin) — uniquement ceux dont le membre existe (non supprimé)
        if ($user->role === Role::ADMIN) {
            $n = Transfert::enAttente()->whereHas('membre')->count();
            if ($n > 0) {
                $items[] = [
                    'type' => 'transferts',
                    'label' => $n === 1 ? '1 transfert en attente' : "{$n} transferts en attente",
                    'url' => route('transferts.index'),
                    'count' => $n,
                ];
            }
        }

        // Témoignages à valider (admin + superviseur)
        if ($user->role->isAtLeast(Role::SUPERVISEUR)) {
            $n = Temoignage::enAttente()->count();
            if ($n > 0) {
                $items[] = [
                    'type' => 'temoignages',
                    'label' => $n === 1 ? '1 témoignage à valider' : "{$n} témoignages à valider",
                    'url' => route('temoignages.index'),
                    'count' => $n,
                ];
            }
        }

        // Membres absents depuis 3 semaines (faiseur : mes âmes ; superviseur : sa FD) — uniquement membres actifs
        if ($user->role === Role::FAISEUR) {
            $n = Membre::where('suivi_par', $user->id)->where('actif', true)->absentDepuis(3)->count();
            if ($n > 0) {
                $items[] = [
                    'type' => 'absents',
                    'label' => $n === 1 ? '1 âme absente depuis 3 semaines' : "{$n} âmes absentes depuis 3 semaines",
                    'url' => route('membres.index', ['absent_depuis' => 3, 'actif' => 1]),
                    'count' => $n,
                ];
            }
        }
        if ($user->role === Role::SUPERVISEUR && $user->fd_id) {
            // Exclure le superviseur lui-même (fiche membre de la FD sans suivi de présence)
            $n = Membre::where('fd_id', $user->fd_id)
                ->where('actif', true)
                ->absentDepuis(3)
                ->where(function ($q) use ($user) {
                    // Ne pas compter si même email que le superviseur
                    $q->where('email', '!=', $user->email ?? '')->orWhereNull('email');
                })
                ->where(function ($q) use ($user) {
                    // Ni si même nom+prénom sans email (fiche superviseur créée sans email)
                    $q->whereNotNull('email')
                        ->orWhere('prenom', '!=', $user->prenom)
                        ->orWhere('nom', '!=', $user->nom);
                })
                ->count();
            if ($n > 0) {
                $items[] = [
                    'type' => 'absents',
                    'label' => $n === 1 ? '1 membre absent depuis 3 semaines' : "{$n} membres absents depuis 3 semaines",
                    'url' => route('membres.index', ['absent_depuis' => 3, 'actif' => 1]),
                    'count' => $n,
                ];
            }
        }

        $total = array_sum(array_column($items, 'count'));

        return ['items' => $items, 'total' => $total];
    }
}
