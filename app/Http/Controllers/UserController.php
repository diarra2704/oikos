<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Cellule;
use App\Models\FamilleDisciples;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Accès réservé aux administrateurs.');
        }

        $users = User::with(['familleDisciples:id,nom,couleur', 'cellule:id,nom,fd_id'])
            ->orderBy('role')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get(['id', 'nom', 'prenom', 'email', 'telephone', 'role', 'fd_id', 'cellule_id', 'actif', 'created_at']);

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }

    public function create(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Accès réservé aux administrateurs.');
        }

        $familles = FamilleDisciples::orderBy('nom')->get(['id', 'nom', 'couleur']);
        $cellules = Cellule::with('familleDisciples:id,nom')->orderBy('fd_id')->orderBy('nom')->get(['id', 'nom', 'fd_id']);
        $roles = array_map(fn (Role $r) => ['value' => $r->value, 'label' => $r->label()], Role::cases());

        return Inertia::render('Users/Create', [
            'familles' => $familles,
            'cellules' => $cellules,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Accès réservé aux administrateurs.');
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|max:191|unique:users,email',
            'telephone' => 'nullable|string|max:20',
            'password' => ['nullable', 'string', 'min:8', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::in(array_column(Role::cases(), 'value'))],
            'fd_id' => 'nullable|exists:familles_disciples,id',
            'cellule_id' => 'nullable|exists:cellules,id',
            'actif' => 'sometimes|boolean',
        ]);

        $generatedPassword = null;
        if (empty($validated['password'])) {
            $validated['password'] = Str::password(12);
            $generatedPassword = $validated['password'];
        }
        $validated['password'] = bcrypt($validated['password']);
        $validated['actif'] = $validated['actif'] ?? true;

        // Cohérence rôle / FD / cellule
        if (in_array($validated['role'], [Role::SUPERVISEUR->value, Role::LEADER_CELLULE->value, Role::FAISEUR->value], true)) {
            if (empty($validated['fd_id'])) {
                return back()->withErrors(['fd_id' => 'Ce rôle doit être rattaché à une Famille de Disciples.'])->withInput();
            }
            $validated['fd_id'] = (int) $validated['fd_id'];
        } else {
            $validated['fd_id'] = null;
            $validated['cellule_id'] = null;
        }

        if ($validated['role'] === Role::FAISEUR->value || $validated['role'] === Role::LEADER_CELLULE->value) {
            if (!empty($validated['cellule_id'])) {
                $cellule = Cellule::find($validated['cellule_id']);
                if ($cellule && $cellule->fd_id != ($validated['fd_id'] ?? null)) {
                    $validated['cellule_id'] = null;
                }
            }
        } else {
            $validated['cellule_id'] = null;
        }

        User::create($validated);

        $message = 'Utilisateur créé. Le compte est actif : il peut se connecter avec son email et le mot de passe défini.';
        $flash = ['success' => $message];
        if ($generatedPassword !== null) {
            $flash['generated_password'] = $generatedPassword;
            $flash['generated_user_email'] = $validated['email'];
            $flash['success'] = 'Compte créé. Communiquez le mot de passe temporaire à l\'utilisateur (il pourra le modifier après connexion).';
        }

        return redirect()->route('users.index')->with($flash);
    }

    /**
     * Affiche le formulaire de réinitialisation du mot de passe (admin).
     */
    public function showResetPassword(Request $request, User $user)
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Accès réservé aux administrateurs.');
        }

        return Inertia::render('Users/ResetPassword', [
            'user' => $user->only(['id', 'nom', 'prenom', 'email']),
        ]);
    }

    /**
     * Réinitialise le mot de passe d'un utilisateur (admin).
     */
    public function resetPassword(Request $request, User $user)
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Accès réservé aux administrateurs.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed', Password::defaults()],
        ]);

        $user->update(['password' => bcrypt($validated['password'])]);

        return redirect()->route('users.index')->with('success', "Mot de passe de {$user->prenom} {$user->nom} réinitialisé.");
    }
}
