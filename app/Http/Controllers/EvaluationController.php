<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Evaluation;
use App\Models\Note;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    private function enseignantConnecte(Request $request): Enseignant
    {
        $enseignant = Enseignant::where('user_id', $request->user()->id)->first();

        abort_if(! $enseignant, 404, "Aucun profil enseignant lié à ce compte.");

        return $enseignant;
    }

    private function verifierAffectation(Enseignant $enseignant, int $classeId, int $matiereId): void
    {
        $existe = $enseignant->affectations()
            ->where('classe_id', $classeId)
            ->where('matiere_id', $matiereId)
            ->exists();

        abort_if(! $existe, 403, "Vous n'êtes pas affecté à cette classe pour cette matière.");
    }

   public function index(Request $request)
{
    $enseignant = $this->enseignantConnecte($request);

    $data = $request->validate([
        'classe_id' => ['required', 'exists:classes,id'],
        'matiere_id' => ['required', 'exists:matieres,id'],
        'periode_id' => ['required', 'exists:periodes,id'],
    ]);

    $this->verifierAffectation($enseignant, $data['classe_id'], $data['matiere_id']);

    return Evaluation::where('enseignant_id', $enseignant->id)
        ->where('classe_id', $data['classe_id'])
        ->where('matiere_id', $data['matiere_id'])
        ->where('periode_id', $data['periode_id'])
        ->orderByDesc('date_evaluation')
        ->get();
}

    public function store(Request $request)
{
    $enseignant = $this->enseignantConnecte($request);

    $data = $request->validate([
        'classe_id' => ['required', 'exists:classes,id'],
        'matiere_id' => ['required', 'exists:matieres,id'],
        'periode_id' => ['required', 'exists:periodes,id'],
        'titre' => ['required', 'string'],
        'type' => ['required', 'in:devoir,interrogation,composition'],
        'date_evaluation' => ['required', 'date'],
    ]);

    $this->verifierAffectation($enseignant, $data['classe_id'], $data['matiere_id']);


     $existeDeja = Evaluation::where('classe_id', $data['classe_id'])
            ->where('matiere_id', $data['matiere_id'])
            ->where('periode_id', $data['periode_id'])
            ->where('type', $data['type'])
            ->exists();
 
        if ($existeDeja) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'type' => ["Une évaluation de type \"{$data['type']}\" existe déjà pour cette classe, cette matière et cette période. Une seule par type est autorisée."],
            ]);
        }

    $classe = \App\Models\Classe::findOrFail($data['classe_id']);
    $matiere = \App\Models\Matiere::findOrFail($data['matiere_id']);

    $niveauMatiere = $matiere->niveaux()->where('niveaux.id', $classe->niveau_id)->first();
    $coefficient = $niveauMatiere?->pivot->coefficient ?? 1;

    $evaluation = Evaluation::create([
        ...$data,
        'enseignant_id' => $enseignant->id,
        'coefficient' => $coefficient,
    ]);

    return response()->json($evaluation, 201);
}

   public function recap(Request $request)
{
    $enseignant = $this->enseignantConnecte($request);

    $data = $request->validate([
        'classe_id' => ['required', 'exists:classes,id'],
        'matiere_id' => ['required', 'exists:matieres,id'],
        'periode_id' => ['required', 'exists:periodes,id'],
    ]);

    $this->verifierAffectation($enseignant, $data['classe_id'], $data['matiere_id']);

    $evaluations = Evaluation::where('enseignant_id', $enseignant->id)
        ->where('classe_id', $data['classe_id'])
        ->where('matiere_id', $data['matiere_id'])
        ->where('periode_id', $data['periode_id'])
        ->orderBy('date_evaluation')
        ->get();

    $eleves = Eleve::whereHas('inscriptions', function ($q) use ($data) {
        $q->where('classe_id', $data['classe_id'])->where('statut', 'en_cours');
    })->orderBy('nom')->get(['id', 'nom', 'prenom', 'matricule']);

    $notes = Note::whereIn('evaluation_id', $evaluations->pluck('id'))->get()->groupBy('eleve_id');

    $resultatEleves = $eleves->map(function ($eleve) use ($evaluations, $notes) {
        $notesEleve = $notes->get($eleve->id, collect())->keyBy('evaluation_id');
        $sommePonderee = 0;
        $sommeCoef = 0;
        $parEvaluation = [];

        foreach ($evaluations as $evaluation) {
            $note = $notesEleve->get($evaluation->id);
            $valeur = $note?->valeur;
            $parEvaluation[$evaluation->id] = $valeur;

            if ($valeur !== null) {
                $sommePonderee += $valeur * $evaluation->coefficient;
                $sommeCoef += $evaluation->coefficient;
            }
        }

        return [
            'eleve_id' => $eleve->id,
            'nom' => $eleve->nom,
            'prenom' => $eleve->prenom,
            'matricule' => $eleve->matricule,
            'notes' => $parEvaluation,
            'moyenne' => $sommeCoef > 0 ? round($sommePonderee / $sommeCoef, 2) : null,
        ];
    });

    return response()->json([
        'evaluations' => $evaluations,
        'eleves' => $resultatEleves,
    ]);
}

    public function notes(Request $request, Evaluation $evaluation)
    {
        $enseignant = $this->enseignantConnecte($request);
        abort_if($evaluation->enseignant_id !== $enseignant->id, 403);

        $eleves = Eleve::whereHas('inscriptions', function ($q) use ($evaluation) {
            $q->where('classe_id', $evaluation->classe_id)->where('statut', 'en_cours');
        })->orderBy('nom')->get(['id', 'nom', 'prenom', 'matricule']);

        $notesExistantes = $evaluation->notes()->get()->keyBy('eleve_id');

        return $eleves->map(function ($eleve) use ($notesExistantes) {
            $note = $notesExistantes->get($eleve->id);
            return [
                'eleve_id' => $eleve->id,
                'nom' => $eleve->nom,
                'prenom' => $eleve->prenom,
                'matricule' => $eleve->matricule,
                'valeur' => $note?->valeur,
                'appreciation' => $note?->appreciation,
            ];
        });
    }

    public function storeNotes(Request $request, Evaluation $evaluation)
    {
        $enseignant = $this->enseignantConnecte($request);
        abort_if($evaluation->enseignant_id !== $enseignant->id, 403);

        $data = $request->validate([
            'notes' => ['present', 'array'],
            'notes.*.eleve_id' => ['required', 'exists:eleves,id'],
            'notes.*.valeur' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'notes.*.appreciation' => ['nullable', 'string'],
        ]);

        foreach ($data['notes'] as $n) {
            Note::updateOrCreate(
                ['evaluation_id' => $evaluation->id, 'eleve_id' => $n['eleve_id']],
                ['valeur' => $n['valeur'] ?? null, 'appreciation' => $n['appreciation'] ?? null]
            );
        }

        return response()->json(['message' => 'Notes enregistrées.']);
    }

    public function moyennes(Request $request)
    {
        $enseignant = $this->enseignantConnecte($request);

        $data = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'matiere_id' => ['required', 'exists:matieres,id'],
        ]);

        $this->verifierAffectation($enseignant, $data['classe_id'], $data['matiere_id']);

        $evaluations = Evaluation::where('enseignant_id', $enseignant->id)
            ->where('classe_id', $data['classe_id'])
            ->where('matiere_id', $data['matiere_id'])
            ->with('notes')
            ->get();

        $eleves = Eleve::whereHas('inscriptions', function ($q) use ($data) {
            $q->where('classe_id', $data['classe_id'])->where('statut', 'en_cours');
        })->orderBy('nom')->get(['id', 'nom', 'prenom', 'matricule']);

        return $eleves->map(function ($eleve) use ($evaluations) {
            $sommePonderee = 0;
            $sommeCoef = 0;

            foreach ($evaluations as $evaluation) {
                $note = $evaluation->notes->firstWhere('eleve_id', $eleve->id);
                if ($note && $note->valeur !== null) {
                    $sommePonderee += $note->valeur * $evaluation->coefficient;
                    $sommeCoef += $evaluation->coefficient;
                }
            }

            return [
                'eleve_id' => $eleve->id,
                'nom' => $eleve->nom,
                'prenom' => $eleve->prenom,
                'matricule' => $eleve->matricule,
                'moyenne' => $sommeCoef > 0 ? round($sommePonderee / $sommeCoef, 2) : null,
            ];
        });
    }
}