<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use Illuminate\Http\Request;

class AnneeScolaireController extends Controller
{
    public function index()
    {
        return AnneeScolaire::orderByDesc('date_debut')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'unique:annees_scolaires,libelle'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'active' => ['sometimes', 'boolean'],
        ]);

        if (! empty($data['active'])) {
            AnneeScolaire::query()->update(['active' => false]);
        }

        return response()->json(AnneeScolaire::create($data), 201);
    }
    public function update(Request $request, AnneeScolaire $anneeScolaire)
{
    $data = $request->validate([
        'date_debut' => ['required', 'date'],
        'date_fin' => ['required', 'date', 'after:date_debut'],
        'active' => ['sometimes', 'boolean'],
    ]);

    if (! empty($data['active'])) {
        AnneeScolaire::query()->where('id', '!=', $anneeScolaire->id)->update(['active' => false]);
    }

    $anneeScolaire->update($data);

    return $anneeScolaire;
}
}