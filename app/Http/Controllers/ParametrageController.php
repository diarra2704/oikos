<?php

namespace App\Http\Controllers;

use App\Models\ParametreValeur;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParametrageController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type', array_key_first(ParametreValeur::types()));

        if (!array_key_exists($type, ParametreValeur::types())) {
            $type = array_key_first(ParametreValeur::types());
        }

        $items = ParametreValeur::tousParType($type)->get();
        $types = ParametreValeur::types();

        return Inertia::render('Parametrage/Index', [
            'typeCourant' => $type,
            'types' => $types,
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(ParametreValeur::types())),
            'valeur' => 'required|string|max:100',
            'libelle' => 'required|string|max:191',
            'ordre' => 'nullable|integer|min:0',
        ]);

        $validated['ordre'] = $validated['ordre'] ?? ParametreValeur::where('type', $validated['type'])->max('ordre') + 1;

        // Unicité type + valeur
        $exists = ParametreValeur::where('type', $validated['type'])
            ->where('valeur', $validated['valeur'])
            ->exists();
        if ($exists) {
            return back()->withErrors(['valeur' => 'Cette valeur existe déjà pour ce type.']);
        }

        ParametreValeur::create($validated);

        return back()->with('success', 'Élément ajouté.');
    }

    public function update(Request $request, ParametreValeur $parametreValeur)
    {
        $validated = $request->validate([
            'valeur' => 'sometimes|required|string|max:100',
            'libelle' => 'sometimes|required|string|max:191',
            'ordre' => 'nullable|integer|min:0',
            'actif' => 'sometimes|boolean',
        ]);

        if (isset($validated['valeur'])) {
            $exists = ParametreValeur::where('type', $parametreValeur->type)
                ->where('valeur', $validated['valeur'])
                ->where('id', '!=', $parametreValeur->id)
                ->exists();
            if ($exists) {
                return back()->withErrors(['valeur' => 'Cette valeur existe déjà pour ce type.']);
            }
        }

        $parametreValeur->update($validated);

        return back()->with('success', 'Élément mis à jour.');
    }

    public function destroy(ParametreValeur $parametreValeur)
    {
        $type = $parametreValeur->type;
        $parametreValeur->delete();
        return redirect()->route('parametrage.index', ['type' => $type])
            ->with('success', 'Élément supprimé.');
    }
}
