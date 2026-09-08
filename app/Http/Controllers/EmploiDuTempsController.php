<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Enseignant;
use App\Models\Niveau;
use App\Models\Seance;
use Illuminate\Http\Request;

class EmploiDuTempsController extends Controller
{
    // Jour => nombre d'heures de cours ce jour-là (7h/jour sauf mercredi et vendredi à 5h)
    private const JOURS = [
        'lundi' => 7,
        'mardi' => 7,
        'mercredi' => 5,
        'jeudi' => 7,
        'vendredi' => 5,
    ];

    public function genererPourNiveau(Request $request, Niveau $niveau)
    {
        $anneeActive = AnneeScolaire::where('active', true)->first();
        abort_if(! $anneeActive, 422, "Aucune année scolaire active — impossible de générer.");

        $classes = Classe::where('niveau_id', $niveau->id)
            ->where('annee_scolaire_id', $anneeActive->id)
            ->get();

        abort_if($classes->isEmpty(), 422, "Aucune classe pour ce niveau sur l'année active.");

        $matieresNiveau = $niveau->matieres()->get();

        // On régénère à neuf : on efface les anciennes séances de CE niveau pour cette année
        Seance::whereIn('classe_id', $classes->pluck('id'))
            ->where('annee_scolaire_id', $anneeActive->id)
            ->delete();

        // Occupation des enseignants sur TOUTE l'école (pour ne pas créer de conflit avec d'autres niveaux)
        $occupationEnseignants = [];
        foreach (Seance::where('annee_scolaire_id', $anneeActive->id)->get() as $s) {
            $occupationEnseignants["{$s->enseignant_id}-{$s->jour}-{$s->heure}"] = true;
        }

        $avertissements = [];

        foreach ($classes as $classe) {
            // Liste de tous les créneaux de la semaine, mélangée pour répartir les matières
            $tousLesCreneaux = [];
            foreach (self::JOURS as $jour => $nbHeures) {
                for ($h = 1; $h <= $nbHeures; $h++) {
                    $tousLesCreneaux[] = [$jour, $h];
                }
            }
            shuffle($tousLesCreneaux);

            $creneauxOccupesClasse = [];

            foreach ($matieresNiveau as $matiere) {
                $affectation = Affectation::where('classe_id', $classe->id)
                    ->where('matiere_id', $matiere->id)
                    ->first();

                if (! $affectation) {
                    $avertissements[] = "{$classe->nom} : aucun enseignant affecté pour {$matiere->nom}, matière ignorée.";
                    continue;
                }

                $heuresRequises = (int) $matiere->pivot->heures_semaine;
                $heuresPlacees = 0;

                foreach ($tousLesCreneaux as [$jour, $heure]) {
                    if ($heuresPlacees >= $heuresRequises) {
                        break;
                    }

                    $cleClasse = "{$jour}-{$heure}";
                    $cleEnseignant = "{$affectation->enseignant_id}-{$jour}-{$heure}";

                    if (isset($creneauxOccupesClasse[$cleClasse]) || isset($occupationEnseignants[$cleEnseignant])) {
                        continue;
                    }

                    Seance::create([
                        'classe_id' => $classe->id,
                        'matiere_id' => $matiere->id,
                        'enseignant_id' => $affectation->enseignant_id,
                        'annee_scolaire_id' => $anneeActive->id,
                        'jour' => $jour,
                        'heure' => $heure,
                    ]);

                    $creneauxOccupesClasse[$cleClasse] = true;
                    $occupationEnseignants[$cleEnseignant] = true;
                    $heuresPlacees++;
                }

                if ($heuresPlacees < $heuresRequises) {
                    $avertissements[] = "{$classe->nom} — {$matiere->nom} : seulement {$heuresPlacees}h/{$heuresRequises}h placées (créneaux insuffisants ou enseignant déjà occupé ailleurs).";
                }
            }
        }

        return response()->json([
            'message' => 'Génération terminée.',
            'avertissements' => $avertissements,
        ]);
    }

    public function classePdf(Classe $classe)
    {
        $anneeActive = AnneeScolaire::where('active', true)->first();

        $seances = Seance::where('classe_id', $classe->id)
            ->when($anneeActive, fn ($q) => $q->where('annee_scolaire_id', $anneeActive->id))
            ->with(['matiere', 'enseignant'])
            ->get();

        $classe->load('niveau');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.emploi-du-temps', [
            'titre' => "Classe {$classe->nom}",
            'seances' => $seances,
            'jours' => array_keys(self::JOURS),
            'heuresParJour' => self::JOURS,
            'colonne' => fn ($s) => "{$s->matiere->nom}\n{$s->enseignant->prenom} {$s->enseignant->nom}",
        ])->setPaper('a4', 'landscape');

        return $pdf->download('emploi-du-temps-'.$classe->nom.'.pdf');
    }

    public function monEmploiDuTemps(Request $request)
    {
        $enseignant = Enseignant::where('user_id', $request->user()->id)->first();
        abort_if(! $enseignant, 404, "Aucun profil enseignant lié à ce compte.");

        $anneeActive = AnneeScolaire::where('active', true)->first();

        return Seance::where('enseignant_id', $enseignant->id)
            ->when($anneeActive, fn ($q) => $q->where('annee_scolaire_id', $anneeActive->id))
            ->with(['classe.niveau', 'matiere'])
            ->orderBy('jour')
            ->orderBy('heure')
            ->get();
    }

    public function monEmploiDuTempsPdf(Request $request)
    {
        $enseignant = Enseignant::where('user_id', $request->user()->id)->first();
        abort_if(! $enseignant, 404, "Aucun profil enseignant lié à ce compte.");

        $anneeActive = AnneeScolaire::where('active', true)->first();

        $seances = Seance::where('enseignant_id', $enseignant->id)
            ->when($anneeActive, fn ($q) => $q->where('annee_scolaire_id', $anneeActive->id))
            ->with(['classe', 'matiere'])
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.emploi-du-temps', [
            'titre' => "{$enseignant->prenom} {$enseignant->nom}",
            'seances' => $seances,
            'jours' => array_keys(self::JOURS),
            'heuresParJour' => self::JOURS,
            'colonne' => fn ($s) => "{$s->matiere->nom}\n{$s->classe->nom}",
        ])->setPaper('a4', 'landscape');

        return $pdf->download('emploi-du-temps-'.$enseignant->nom.'.pdf');
    }
}