<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\Note;
use App\Models\Periode;
use Illuminate\Http\Request;

class BulletinController extends Controller
{
    private function mentionGenerale(?float $moyenne): ?string
    {
        if ($moyenne === null) return null;
        if ($moyenne >= 20) return 'Tableau d\'honneur';
        if ($moyenne >= 18) return 'Excellent';
        if ($moyenne >= 16) return 'Très Bien';
        if ($moyenne >= 14) return 'Bien';
        if ($moyenne >= 12) return 'Assez Bien';
        if ($moyenne >= 10) return 'Passable';
        return 'Médiocre';
    }

    private function appreciationMatiere(?float $moyenne): ?string
    {
        if ($moyenne === null) return null;
        if ($moyenne >= 14) return 'Bon travail';
        if ($moyenne >= 10) return 'Travail satisfaisant';
        return 'Travail insuffisant';
    }

    public function preparer(Request $request, Classe $classe)
    {
        $data = $request->validate([
            'periode_id' => ['required', 'exists:periodes,id'],
        ]);
        $periode = Periode::findOrFail($data['periode_id']);

        $classe->load('niveau.matieres');

        $eleves = Eleve::whereHas('inscriptions', function ($q) use ($classe) {
            $q->where('classe_id', $classe->id)->where('statut', 'en_cours');
        })->orderBy('nom')->get();

        abort_if($eleves->isEmpty(), 422, "Aucun élève inscrit dans cette classe.");

        // Pré-calcul par élève : détail par matière + moyenne générale (non arrondie pour le classement)
        $donneesEleves = [];

        foreach ($eleves as $eleve) {
            $detailMatieres = [];
            $sommePonderee = 0;
            $sommeCoef = 0;

            foreach ($classe->niveau->matieres as $matiere) {
                $affectation = Affectation::where('classe_id', $classe->id)
                    ->where('matiere_id', $matiere->id)
                    ->with('enseignant')
                    ->first();

                $evaluations = Evaluation::where('classe_id', $classe->id)
                    ->where('matiere_id', $matiere->id)
                    ->where('periode_id', $periode->id)
                    ->get();

                $notesParType = ['devoir' => null, 'interrogation' => null, 'composition' => null];
                $sommeNotes = 0;
                $nbNotes = 0;

                foreach ($evaluations as $evaluation) {
                    $note = Note::where('evaluation_id', $evaluation->id)->where('eleve_id', $eleve->id)->first();
                    if ($note && $note->valeur !== null) {
                        $notesParType[$evaluation->type] = $note->valeur;
                        $sommeNotes += $note->valeur;
                        $nbNotes++;
                    }
                }

                $moyenneMatiere = $nbNotes > 0 ? round($sommeNotes / $nbNotes, 2) : null;
                $coefficient = (float) $matiere->pivot->coefficient;

                if ($moyenneMatiere !== null) {
                    $sommePonderee += $moyenneMatiere * $coefficient;
                    $sommeCoef += $coefficient;
                }

                $detailMatieres[] = [
                    'matiere' => $matiere->nom,
                    'coefficient' => $coefficient,
                    'notes' => $notesParType,
                    'moyenne' => $moyenneMatiere,
                    'appreciation' => $this->appreciationMatiere($moyenneMatiere),
                    'enseignant' => $affectation ? "{$affectation->enseignant->prenom} {$affectation->enseignant->nom}" : null,
                ];
            }

            $moyenneGenerale = $sommeCoef > 0 ? round($sommePonderee / $sommeCoef, 2) : null;

            $donneesEleves[] = [
                'eleve' => $eleve,
                'detail' => $detailMatieres,
                'moyenne_generale' => $moyenneGenerale,
            ];
        }

        // Classement : moyennes non nulles d'abord (triées desc.), puis null en dernier
        $classement = collect($donneesEleves)->sortByDesc(fn ($d) => $d['moyenne_generale'] ?? -1)->values();
        $effectif = $classement->count();

        foreach ($classement as $index => $d) {
            Bulletin::updateOrCreate(
                ['eleve_id' => $d['eleve']->id, 'periode_id' => $periode->id],
                [
                    'classe_id' => $classe->id,
                    'moyenne_generale' => $d['moyenne_generale'],
                    'rang' => $d['moyenne_generale'] !== null ? $index + 1 : null,
                    'effectif_classe' => $effectif,
                    'mention' => $this->mentionGenerale($d['moyenne_generale']),
                    'detail_matieres' => $d['detail'],
                    'statut' => 'prepare',
                    'date_validation' => null,
                ]
            );
        }

        return response()->json(['message' => 'Bulletins préparés.', 'nombre' => $effectif]);
    }

    public function liste(Request $request, Classe $classe)
    {
        $data = $request->validate(['periode_id' => ['required', 'exists:periodes,id']]);

        return Bulletin::where('classe_id', $classe->id)
            ->where('periode_id', $data['periode_id'])
            ->with('eleve:id,nom,prenom,matricule')
            ->orderBy('rang')
            ->get();
    }

    public function valider(Request $request, Classe $classe)
    {
        abort_unless($request->user()->hasRole('direction'), 403, "Seule la direction peut valider les bulletins.");

        $data = $request->validate(['periode_id' => ['required', 'exists:periodes,id']]);

        $nombre = Bulletin::where('classe_id', $classe->id)
            ->where('periode_id', $data['periode_id'])
            ->update(['statut' => 'valide', 'date_validation' => now()]);

        return response()->json(['message' => 'Bulletins validés.', 'nombre' => $nombre]);
    }

    public function telecharger(Bulletin $bulletin)
    {
        abort_if($bulletin->statut !== 'valide', 403, "Ce bulletin n'a pas encore été validé par la direction.");

        $bulletin->load('eleve', 'classe.niveau', 'periode.anneeScolaire');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.bulletin', ['bulletin' => $bulletin]);

        return $pdf->download("bulletin-{$bulletin->eleve->matricule}-{$bulletin->periode->nom}.pdf");
    }
}