<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartementController extends Controller
{
    public function index()
    {
        $departements = Departement::orderBy('nom')->get();

        return Inertia::render('Parametrage/Departements', [
            'departements' => $departements,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:191',
            'description' => 'nullable|string|max:500',
            'actif' => 'sometimes|boolean',
        ]);

        $validated['actif'] = $validated['actif'] ?? true;

        Departement::create($validated);

        return back()->with('success', 'Département ajouté.');
    }

    public function update(Request $request, Departement $departement)
    {
        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:191',
            'description' => 'nullable|string|max:500',
            'actif' => 'sometimes|boolean',
        ]);

        $departement->update($validated);

        return back()->with('success', 'Département mis à jour.');
    }

    public function destroy(Departement $departement)
    {
        $departement->delete();
        return back()->with('success', 'Département supprimé.');
    }
}
