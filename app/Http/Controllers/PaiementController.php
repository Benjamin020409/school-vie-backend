<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\FraisScolarite;
use App\Models\Paiement;
use App\Models\Periode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PaiementController extends Controller
{
    private function anneeActive()
    {
        return AnneeScolaire::where('active', true)->first();
    }

    private function fraisDuNiveau(int $niveauId): int
    {
        $anneeActive = $this->anneeActive();
        $frais = FraisScolarite::where('niveau_id', $niveauId)
            ->where('annee_scolaire_id', $anneeActive?->id)
            ->first();

        return $frais?->montant ?? 0;
    }

    public function vueEnsemble(Request $request)
    {
        $encaisseAujourdhui = Paiement::whereDate('date_paiement', now()->toDateString())->sum('montant');

        $eleves = Eleve::whereHas('inscriptions', fn ($q) => $q->where('statut', 'en_cours'))
            ->with('inscriptions.classe.niveau')
            ->get();

        $impayesTotaux = 0;
        $retardsListe = [];

        foreach ($eleves as $eleve) {
            $inscription = $eleve->inscriptions->sortByDesc('date_inscription')->first();
            if (! $inscription) {
                continue;
            }

            $fraisTotal = $this->fraisDuNiveau($inscription->classe->niveau_id);
            $totalPaye = Paiement::where('eleve_id', $eleve->id)->sum('montant');
            $restant = max($fraisTotal - $totalPaye, 0);

            if ($restant > 0) {
                $impayesTotaux += $restant;
                $retardsListe[] = [
                    'eleve_id' => $eleve->id,
                    'nom' => $eleve->nom,
                    'prenom' => $eleve->prenom,
                    'classe' => $inscription->classe->nom,
                    'montant_restant' => $restant,
                ];
            }
        }

        usort($retardsListe, fn ($a, $b) => $b['montant_restant'] <=> $a['montant_restant']);

        return response()->json([
            'encaisse_aujourdhui' => $encaisseAujourdhui,
            'impayes_totaux' => $impayesTotaux,
            'eleves_avec_solde' => count($retardsListe),
            'retards' => array_slice($retardsListe, 0, 10),
        ]);
    }

    public function eleves(Classe $classe)
    {
        $classe->load('niveau');
        $fraisTotal = $this->fraisDuNiveau($classe->niveau_id);

        $eleves = Eleve::whereHas('inscriptions', function ($q) use ($classe) {
            $q->where('classe_id', $classe->id)->where('statut', 'en_cours');
        })->orderBy('nom')->get(['id', 'nom', 'prenom', 'matricule']);

        return response()->json([
            'frais_scolarite' => $fraisTotal,
            'eleves' => $eleves,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'eleve_id' => ['required', 'exists:eleves,id'],
            'mode_paiement' => ['required', 'in:bloc,tranche'],
            'type_tranche' => ['required_if:mode_paiement,tranche', 'nullable', 'in:mensuel,trimestriel,semestriel'],
            'montant' => ['required', 'integer', 'min:1'],
        ]);

        $eleve = Eleve::with('inscriptions.classe.niveau')->findOrFail($data['eleve_id']);
        $inscription = $eleve->inscriptions->sortByDesc('date_inscription')->first();
        abort_if(! $inscription, 422, "Cet élève n'a pas d'inscription active.");

        $fraisTotal = $this->fraisDuNiveau($inscription->classe->niveau_id);
        $dejaPayeAvant = Paiement::where('eleve_id', $eleve->id)->sum('montant');
        $montantRestantAvant = max($fraisTotal - $dejaPayeAvant, 0);

        if ($data['mode_paiement'] === 'bloc' && $data['montant'] !== $montantRestantAvant) {
            return response()->json([
                'message' => "Pour un paiement en bloc, le montant doit être exactement égal au solde restant ({$montantRestantAvant}).",
            ], 422);
        }

        if ($data['montant'] > $montantRestantAvant) {
            return response()->json([
                'message' => "Le montant saisi ({$data['montant']}) dépasse le solde restant ({$montantRestantAvant}).",
            ], 422);
        }

        $paiement = Paiement::create([
            'eleve_id' => $eleve->id,
            'montant' => $data['montant'],
            'mode_paiement' => $data['mode_paiement'],
            'type_tranche' => $data['mode_paiement'] === 'tranche' ? $data['type_tranche'] : null,
            'numero_recu' => 'REC-'.now()->format('Ymd').'-'.strtoupper(Str::random(5)),
            'date_paiement' => now(),
        ]);

        return response()->json($paiement, 201);
    }

    private function calculerDelai(?Paiement $dernierPaiement, int $montantRestant, $anneeActive)
    {
        if ($montantRestant <= 0) {
            return 'Soldé';
        }
        if (! $dernierPaiement || $dernierPaiement->mode_paiement === 'bloc') {
            return $anneeActive?->date_fin ? Carbon::parse($anneeActive->date_fin)->format('d/m/Y') : null;
        }

        $nombreTranches = Paiement::where('eleve_id', $dernierPaiement->eleve_id)
            ->where('mode_paiement', 'tranche')
            ->where('type_tranche', $dernierPaiement->type_tranche)
            ->count();

        $debut = Carbon::parse($anneeActive->date_debut);
        $fin = Carbon::parse($anneeActive->date_fin);

        if ($dernierPaiement->type_tranche === 'mensuel') {
            return $debut->copy()->addMonths($nombreTranches)->endOfMonth()->format('d/m/Y');
        }

        if ($dernierPaiement->type_tranche === 'trimestriel') {
            $periodes = Periode::where('annee_scolaire_id', $anneeActive->id)->orderBy('date_debut')->get();
            $prochaine = $periodes->get($nombreTranches);
            return $prochaine ? Carbon::parse($prochaine->date_fin)->format('d/m/Y') : $fin->format('d/m/Y');
        }

        // semestriel : 2 échéances, milieu et fin d'année
        $milieu = $debut->copy()->addDays($debut->diffInDays($fin) / 2);
        return $nombreTranches === 0 ? $milieu->format('d/m/Y') : $fin->format('d/m/Y');
    }

    public function tableau(Classe $classe)
    {
        $classe->load('niveau');
        $anneeActive = $this->anneeActive();
        $fraisTotal = $this->fraisDuNiveau($classe->niveau_id);

        $eleves = Eleve::whereHas('inscriptions', function ($q) use ($classe) {
            $q->where('classe_id', $classe->id)->where('statut', 'en_cours');
        })->orderBy('nom')->get();

        $resultat = $eleves->map(function ($eleve) use ($fraisTotal, $anneeActive) {
            $paiements = Paiement::where('eleve_id', $eleve->id)->orderByDesc('date_paiement')->get();
            $totalPaye = $paiements->sum('montant');
            $montantRestant = max($fraisTotal - $totalPaye, 0);
            $dernier = $paiements->first();

            return [
                'eleve_id' => $eleve->id,
                'nom' => $eleve->nom,
                'prenom' => $eleve->prenom,
                'matricule' => $eleve->matricule,
                'montant_scolarite' => $fraisTotal,
                'montant_restant' => $montantRestant,
                'mode_paiement' => $dernier?->mode_paiement,
                'type_tranche' => $dernier?->type_tranche,
                'delai_paiement' => $this->calculerDelai($dernier, $montantRestant, $anneeActive),
                'dernier_paiement_montant' => $dernier?->montant,
                'dernier_paiement_date' => $dernier?->date_paiement?->format('d/m/Y'),
                'dernier_paiement_id' => $dernier?->id,
            ];
        });

        return response()->json($resultat);
    }

    public function recuPdf(Paiement $paiement)
    {
        $paiement->load('eleve.inscriptions.classe.niveau');
        $inscription = $paiement->eleve->inscriptions->sortByDesc('date_inscription')->first();

        $fraisTotal = $inscription ? $this->fraisDuNiveau($inscription->classe->niveau_id) : 0;
        $dejaPaye = Paiement::where('eleve_id', $paiement->eleve_id)
            ->where('date_paiement', '<=', $paiement->date_paiement)
            ->sum('montant');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.recu-paiement', [
            'paiement' => $paiement,
            'classe' => $inscription?->classe,
            'fraisTotal' => $fraisTotal,
            'montantRestant' => max($fraisTotal - $dejaPaye, 0),
        ]);

        return $pdf->download("recu-{$paiement->numero_recu}.pdf");
    }
}