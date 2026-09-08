<?php

namespace App\Http\Controllers;

use App\Models\Inscription;
use Illuminate\Http\Request;

class InscriptionController extends Controller
{
    public function index(Request $request)
    {
        return Inscription::with(['eleve', 'classe'])
            ->latest('date_inscription')
            ->limit($request->integer('limit', 10))
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'eleve_id' => ['required', 'exists:eleves,id'],
            'classe_id' => ['required', 'exists:classes,id'],
            'annee_scolaire_id' => ['required', 'exists:annees_scolaires,id'],
            'date_inscription' => ['required', 'date'],
        ]);

        $inscription = Inscription::create($data);

        return response()->json($inscription->load(['eleve', 'classe']), 201);
    }

    public function stats()
    {
        $anneeActive = \App\Models\AnneeScolaire::where('active', true)->first();

        $nouvelles = Inscription::when($anneeActive, fn ($q) => $q->where('annee_scolaire_id', $anneeActive->id))
            ->whereMonth('created_at', now()->month)
            ->count();

        $dossiersIncomplets = \App\Models\Eleve::whereNull('tuteur_id')
            ->orWhereNull('date_naissance')
            ->count();

        return [
            'nouvelles_inscriptions' => $nouvelles,
            'dossiers_incomplets' => $dossiersIncomplets,
        ];
    }
}