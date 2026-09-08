<?php

namespace App\Http\Controllers;

use App\Models\Enseignant;
use Illuminate\Http\Request;

class EnseignantController extends Controller
{
    public function index()
    {
        return Enseignant::with('affectations.matiere', 'affectations.classe.niveau', 'specialites:id,nom')
            ->orderBy('nom')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string'],
            'prenom' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:enseignants,email'],
            'telephone' => ['nullable', 'string'],
        ]);

        $enseignant = Enseignant::create($data);

        return response()->json($enseignant->load('affectations', 'specialites:id,nom'), 201);
    }

    public function syncSpecialites(Request $request, Enseignant $enseignant)
    {
        $data = $request->validate([
            'matiere_ids' => ['present', 'array'],
            'matiere_ids.*' => ['exists:matieres,id'],
        ]);

        $enseignant->specialites()->sync($data['matiere_ids']);

        return $enseignant->load('specialites:id,nom');
    }

    public function syncAffectations(Request $request, Enseignant $enseignant)
    {
        $data = $request->validate([
            'affectations' => ['present', 'array'],
            'affectations.*.classe_id' => ['required', 'exists:classes,id'],
            'affectations.*.matiere_id' => ['required', 'exists:matieres,id'],
        ]);

        $enseignant->affectations()->delete();

        foreach ($data['affectations'] as $a) {
            $enseignant->affectations()->create([
                'classe_id' => $a['classe_id'],
                'matiere_id' => $a['matiere_id'],
            ]);
        }

        return $enseignant->load('affectations.matiere', 'affectations.classe.niveau');
    }

    public function mesClasses(Request $request)
    {
        $enseignant = Enseignant::where('user_id', $request->user()->id)->first();

        if (! $enseignant) {
            return response()->json(['message' => "Aucun profil enseignant lié à ce compte."], 404);
        }

        $affectations = $enseignant->affectations()->with(['matiere', 'classe.niveau'])->get();

        $resultat = $affectations->groupBy('classe_id')->map(function ($groupe) {
            $classe = $groupe->first()->classe;

            return [
                'id' => $classe->id,
                'nom' => $classe->nom,
                'niveau' => $classe->niveau->nom,
                'matieres' => $groupe->pluck('matiere.nom')->unique()->values(),
            ];
        })->values();

        return response()->json($resultat);
    }


    public function mesAffectations(Request $request)
{
    $enseignant = Enseignant::where('user_id', $request->user()->id)->first();

    if (! $enseignant) {
        return response()->json(['message' => "Aucun profil enseignant lié à ce compte."], 404);
    }

    return $enseignant->affectations()->with(['classe.niveau', 'matiere'])->get()->map(function ($a) {
        return [
            'classe_id' => $a->classe->id,
            'classe_nom' => $a->classe->nom,
            'niveau_nom' => $a->classe->niveau->nom,
            'matiere_id' => $a->matiere->id,
            'matiere_nom' => $a->matiere->nom,
        ];
    })->values();
}

public function listeClassePdf(Request $request, \App\Models\Classe $classe)
{
    $enseignant = Enseignant::where('user_id', $request->user()->id)->first();
    abort_if(! $enseignant, 404, "Aucun profil enseignant lié à ce compte.");

    $estAffecte = $enseignant->affectations()->where('classe_id', $classe->id)->exists();
    abort_if(! $estAffecte, 403, "Vous n'êtes pas affecté à cette classe.");

    $classe->load('niveau');

    $eleves = \App\Models\Eleve::whereHas('inscriptions', function ($q) use ($classe) {
        $q->where('classe_id', $classe->id)->where('statut', 'en_cours');
    })->orderBy('nom')->get();

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.liste-classe', [
        'classe' => $classe,
        'eleves' => $eleves,
        'enseignant' => $enseignant,
    ]);

    return $pdf->download('liste-'.$classe->nom.'.pdf');
}
}