<?php

namespace App\Http\Controllers;

use App\Models\FamilleImpact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FamilleImpactController extends Controller
{
    public function index()
    {
        $familles = FamilleImpact::orderBy('nom')->get();

        return Inertia::render('Parametrage/FamillesImpact', [
            'familles' => $familles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:191',
            'quartier' => 'nullable|string|max:191',
            'actif' => 'sometimes|boolean',
        ]);

        $validated['actif'] = $validated['actif'] ?? true;

        FamilleImpact::create($validated);

        return back()->with('success', 'Famille d\'impact ajoutée.');
    }

    public function update(Request $request, FamilleImpact $familleImpact)
    {
        $validated = $request->validate([
            'nom' => 'sometimes|required|string|max:191',
            'quartier' => 'nullable|string|max:191',
            'actif' => 'sometimes|boolean',
        ]);

        $familleImpact->update($validated);

        return back()->with('success', 'Famille d\'impact mise à jour.');
    }

    public function destroy(FamilleImpact $familleImpact)
    {
        $familleImpact->delete();
        return back()->with('success', 'Famille d\'impact supprimée.');
    }
}
