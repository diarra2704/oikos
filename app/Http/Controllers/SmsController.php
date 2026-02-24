<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Jobs\EnvoyerSmsEnvoiJob;
use App\Models\FamilleDisciples;
use App\Models\Membre;
use App\Models\SmsEnvoi;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->role->isAtLeast(Role::SUPERVISEUR)) {
            abort(403, 'Accès réservé aux superviseurs et administrateurs.');
        }

        $query = SmsEnvoi::with(['familleDisciples:id,nom,couleur', 'createdBy:id,nom,prenom'])
            ->orderBy('created_at', 'desc');

        if ($user->isSuperviseur() && $user->fd_id) {
            $query->where('fd_id', $user->fd_id);
        }

        $envois = $query->paginate(15)->withQueryString();

        $smsService = SmsService::fromConfig();

        return Inertia::render('Sms/Index', [
            'envois' => $envois,
            'smsConfigure' => $smsService->isConfigured(),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        if (!$user->role->isAtLeast(Role::SUPERVISEUR)) {
            abort(403, 'Accès réservé aux superviseurs et administrateurs.');
        }

        $fdQuery = FamilleDisciples::orderBy('nom');
        if ($user->isSuperviseur() && $user->fd_id) {
            $fdQuery->where('id', $user->fd_id);
        }
        $familles = $fdQuery->get(['id', 'nom', 'couleur']);

        return Inertia::render('Sms/Create', [
            'familles' => $familles,
            'userFdId' => $user->fd_id,
        ]);
    }

    public function membresByFd(Request $request)
    {
        $user = $request->user();
        if (!$user->role->isAtLeast(Role::SUPERVISEUR)) {
            abort(403);
        }

        $fdId = $request->integer('fd_id');
        if ($user->isSuperviseur() && $user->fd_id != $fdId) {
            abort(403);
        }

        $membres = Membre::where('fd_id', $fdId)
            ->where('actif', true)
            ->where(function ($q) {
                $q->whereNotNull('telephone')->where('telephone', '!=', '');
            })
            ->orderBy('prenom')
            ->orderBy('nom')
            ->get(['id', 'prenom', 'nom', 'telephone']);

        return response()->json(['membres' => $membres]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->role->isAtLeast(Role::SUPERVISEUR)) {
            abort(403);
        }

        $validated = $request->validate([
            'nom' => 'nullable|string|max:100',
            'message' => 'required|string|max:1600',
            'fd_id' => 'required|exists:familles_disciples,id',
            'membre_ids' => 'nullable|array',
            'membre_ids.*' => 'integer|exists:membres,id',
            'date_programmee' => 'nullable|date|after_or_equal:now',
        ]);

        if ($user->isSuperviseur() && $user->fd_id != $validated['fd_id']) {
            abort(403);
        }

        $envoi = SmsEnvoi::create([
            'nom' => $validated['nom'] ?? null,
            'message' => $validated['message'],
            'fd_id' => $validated['fd_id'],
            'membre_ids' => !empty($validated['membre_ids']) ? array_values($validated['membre_ids']) : null,
            'statut' => 'programme',
            'date_programmee' => $validated['date_programmee'] ?? null,
            'created_by_id' => $user->id,
        ]);

        if ($envoi->date_programmee) {
            EnvoyerSmsEnvoiJob::dispatch($envoi)->delay($envoi->date_programmee);
            return redirect()->route('sms.index')->with('success', "SMS programmé pour le " . $envoi->date_programmee->format('d/m/Y à H:i'));
        }

        EnvoyerSmsEnvoiJob::dispatch($envoi);
        return redirect()->route('sms.index')->with('success', 'Envoi des SMS lancé.');
    }

    public function destroy(Request $request, SmsEnvoi $envoi)
    {
        $user = $request->user();
        if (!$user->role->isAtLeast(Role::SUPERVISEUR)) {
            abort(403);
        }
        if ($user->isSuperviseur() && $envoi->fd_id != $user->fd_id) {
            abort(403);
        }
        if ($envoi->statut !== 'programme') {
            return back()->with('error', 'Seuls les envois programmés peuvent être annulés.');
        }

        $envoi->update(['statut' => 'annule']);
        return back()->with('success', 'Envoi annulé.');
    }
}
