<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Eleve;
use Illuminate\Http\Request;

class ClasseController extends Controller
{
    public function index()
    {
        return Classe::with('niveau')->orderBy('nom')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string'],
            'niveau_id' => ['required', 'exists:niveaux,id'],
            'annee_scolaire_id' => ['required', 'exists:annees_scolaires,id'],
            'capacite_max' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json(Classe::create($data)->load('niveau'), 201);
    }

    public function fichePresence(Classe $classe)
    {
        $classe->load('niveau');

        $eleves = Eleve::whereHas('inscriptions', function ($q) use ($classe) {
            $q->where('classe_id', $classe->id)->where('statut', 'en_cours');
        })->orderBy('nom')->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.fiche-presence', [
            'classe' => $classe,
            'eleves' => $eleves,
            'date' => now()->format('d/m/Y'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('fiche-presence-'.$classe->nom.'-'.now()->format('Y-m-d').'.pdf');
    }
}