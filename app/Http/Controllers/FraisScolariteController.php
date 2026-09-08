<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\FraisScolarite;
use App\Models\Niveau;
use Illuminate\Http\Request;

class FraisScolariteController extends Controller
{
    public function index()
    {
        $anneeActive = AnneeScolaire::where('active', true)->first();

        return Niveau::with(['fraisScolarite' => function ($q) use ($anneeActive) {
            $q->when($anneeActive, fn ($qq) => $qq->where('annee_scolaire_id', $anneeActive->id));
        }])->orderBy('nom')->get();
    }

    public function definir(Request $request, Niveau $niveau)
    {
        $data = $request->validate([
            'montant' => ['required', 'integer', 'min:0'],
        ]);

        $anneeActive = AnneeScolaire::where('active', true)->first();
        abort_if(! $anneeActive, 422, "Aucune année scolaire active — impossible de définir les frais.");

        $frais = FraisScolarite::updateOrCreate(
            ['niveau_id' => $niveau->id, 'annee_scolaire_id' => $anneeActive->id],
            ['montant' => $data['montant']]
        );

        return response()->json($frais, 201);
    }
}