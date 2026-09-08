<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Periode;
use Illuminate\Http\Request;

class PeriodeController extends Controller
{
    public function index()
    {
        return Periode::with('anneeScolaire:id,libelle')->orderByDesc('date_debut')->get();
    }

    public function store(Request $request)
    {
        $anneeActive = AnneeScolaire::where('active', true)->first();
        abort_if(! $anneeActive, 422, "Aucune année scolaire active — impossible de créer une période.");

        $data = $request->validate([
            'nom' => ['required', 'string'],
            'date_debut' => [
                'required', 'date',
                'after_or_equal:'.$anneeActive->date_debut,
                'before_or_equal:'.$anneeActive->date_fin,
            ],
            'date_fin' => [
                'required', 'date', 'after:date_debut',
                'after_or_equal:'.$anneeActive->date_debut,
                'before_or_equal:'.$anneeActive->date_fin,
            ],
        ], [
            'date_debut.after_or_equal' => "La date de début du Trimestre crée doit être comprise dans l'année scolaire active ({$anneeActive->date_debut} → {$anneeActive->date_fin}).",
            'date_debut.before_or_equal' => "La date de début du Trimestre crée doit être comprise dans l'année scolaire active ({$anneeActive->date_debut} → {$anneeActive->date_fin}).",
            'date_fin.after_or_equal' => "La date de fin du Trimestre crée doit être comprise dans l'année scolaire active ({$anneeActive->date_debut} → {$anneeActive->date_fin}).",
            'date_fin.before_or_equal' => "La date de fin du Trimestre crée doit être comprise dans l'année scolaire active ({$anneeActive->date_debut} → {$anneeActive->date_fin}).",
        ]);

        $periode = Periode::create([
            ...$data,
            'annee_scolaire_id' => $anneeActive->id,
        ]);

        return response()->json($periode, 201);
    }
}