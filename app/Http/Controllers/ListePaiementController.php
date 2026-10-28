<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\FraisScolarite;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ListePaiementController extends Controller
{
    private function verifierAcces(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyRole(['comptable', 'direction', 'admin']),
            403,
            "Accès réservé à la comptabilité et à la direction."
        );
    }

    private function cheminLogo(): ?string
    {
        $chemin = public_path('assets/logo.png');

        return file_exists($chemin) ? str_replace('\\', '/', $chemin) : null;
    }

    private function anneeEtFrais(Classe $classe): array
    {
        $annee = AnneeScolaire::find($classe->annee_scolaire_id);
        abort_if(! $annee, 422, "Année scolaire introuvable pour cette classe.");

        $frais = (int) (FraisScolarite::where('niveau_id', $classe->niveau_id)
            ->where('annee_scolaire_id', $annee->id)
            ->value('montant') ?? 0);
        abort_if($frais <= 0, 422, "Les frais de scolarité de ce niveau ne sont pas définis pour {$annee->libelle}.");

        return [$annee, $frais];
    }

    private function base(Classe $classe): array
    {
        $classe->load('niveau');
        [$annee, $frais] = $this->anneeEtFrais($classe);

        $eleves = Eleve::whereHas('inscriptions', function ($q) use ($classe) {
                $q->where('classe_id', $classe->id)->where('statut', 'en_cours');
            })
            ->orderBy('nom')->orderBy('prenom')
            ->get(['id', 'nom', 'prenom', 'matricule']);
        abort_if($eleves->isEmpty(), 422, "Aucun élève inscrit dans cette classe.");

        $paiements = Paiement::where('annee_scolaire_id', $annee->id)
            ->whereIn('eleve_id', $eleves->pluck('id'))
            ->orderBy('date_paiement')->orderBy('id')
            ->get()
            ->groupBy('eleve_id');

        return compact('classe', 'annee', 'frais', 'eleves', 'paiements');
    }

    /**
     * Découpe automatiquement l'année scolaire en 3 tranches égales (dates et montants).
     * Le reliquat de la division de l'écolage est ajouté à la 3e tranche.
     */
    private function tranches(AnneeScolaire $annee, int $frais): array
    {
        $debut = Carbon::parse($annee->date_debut)->startOfDay();
        $fin = Carbon::parse($annee->date_fin)->startOfDay();
        $jours = (int) abs($debut->diffInDays($fin));

        $noms = [1 => 'Première tranche', 2 => 'Deuxième tranche', 3 => 'Troisième tranche'];
        $base = intdiv($frais, 3);
        $reliquat = $frais - $base * 3;

        $tranches = [];
        $debutTranche = $debut->copy();

        foreach ([1, 2, 3] as $i) {
            $echeance = $i === 3
                ? $fin->copy()
                : $debut->copy()->addDays((int) round($jours * $i / 3));

            $tranches[] = [
                'numero' => $i,
                'nom' => $noms[$i],
                'debut' => $debutTranche->copy(),
                'echeance' => $echeance,
                'montant' => $base + ($i === 3 ? $reliquat : 0),
            ];

            $debutTranche = $echeance->copy()->addDay();
        }

        return $tranches;
    }

    public function tranchesClasse(Request $request, Classe $classe)
    {
        $this->verifierAcces($request);
        [$annee, $frais] = $this->anneeEtFrais($classe);
        $maintenant = now();

        return response()->json(collect($this->tranches($annee, $frais))->map(fn ($t) => [
            'numero' => $t['numero'],
            'nom' => $t['nom'],
            'montant' => $t['montant'],
            'debut' => $t['debut']->format('d/m/Y'),
            'echeance' => $t['echeance']->format('d/m/Y'),
            'echue' => $t['echeance']->copy()->endOfDay()->lt($maintenant),
        ])->values());
    }

    public function listePaiements(Request $request, Classe $classe)
    {
        $this->verifierAcces($request);
        ['annee' => $annee, 'frais' => $frais, 'eleves' => $eleves, 'paiements' => $paiements] = $this->base($classe);

        $lignes = $eleves->map(function ($e) use ($paiements, $frais) {
            $liste = $paiements->get($e->id, collect());
            $paye = (int) $liste->sum('montant');

            return [
                'matricule' => $e->matricule,
                'nom' => mb_strtoupper($e->nom).' '.$e->prenom,
                'paye' => $paye,
                'reste' => max($frais - $paye, 0),
                'taux' => (int) round(min($paye / $frais, 1) * 100),
                'statut' => $paye >= $frais ? 'Soldé' : ($paye > 0 ? 'Partiel' : 'Aucun versement'),
                'dernier' => $liste->last()?->date_paiement?->format('d/m/Y'),
            ];
        })->values();

        $attendu = $frais * $lignes->count();
        $totalPaye = (int) $lignes->sum('paye');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.liste-paiements', [
            'classe' => $classe,
            'annee' => $annee,
            'frais' => $frais,
            'lignes' => $lignes,
            'totaux' => [
                'attendu' => $attendu,
                'paye' => $totalPaye,
                'reste' => max($attendu - $totalPaye, 0),
                'taux' => $attendu > 0 ? round($totalPaye / $attendu * 100, 1) : 0,
                'soldes' => $lignes->where('statut', 'Soldé')->count(),
            ],
            'logo' => $this->cheminLogo(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('liste-paiements-'.Str::slug($classe->nom).'.pdf');
    }

    public function listeRenvoi(Request $request, Classe $classe)
    {
        $this->verifierAcces($request);

        $data = $request->validate([
            'tranche' => ['required', 'integer', 'in:1,2,3'],
        ], [
            'tranche.required' => "Choisis d'abord la tranche (première, deuxième ou troisième).",
        ]);
        $numero = (int) $data['tranche'];

        ['annee' => $annee, 'frais' => $frais, 'eleves' => $eleves, 'paiements' => $paiements] = $this->base($classe);

        $tranches = $this->tranches($annee, $frais);
        $tranche = $tranches[$numero - 1];

        // Ce qui devait être payé avant cette tranche, puis à la fin de cette tranche
        $cumulPrecedent = (int) collect($tranches)->take($numero - 1)->sum('montant');
        $montantTranche = (int) $tranche['montant'];

        $tous = $eleves->map(function ($e) use ($paiements, $frais, $cumulPrecedent, $montantTranche) {
            $liste = $paiements->get($e->id, collect());
            $paye = (int) $liste->sum('montant');

            $arriere = max($cumulPrecedent - $paye, 0);
            $payeTranche = max(min($paye - $cumulPrecedent, $montantTranche), 0);
            $resteTranche = $montantTranche - $payeTranche;

            return [
                'matricule' => $e->matricule,
                'nom' => mb_strtoupper($e->nom).' '.$e->prenom,
                'arriere' => $arriere,
                'paye_tranche' => $payeTranche,
                'reste_tranche' => $resteTranche,
                'a_regler' => $arriere + $resteTranche,
                'paye' => $paye,
                'reste_global' => max($frais - $paye, 0),
                'dernier' => $liste->last()?->date_paiement?->format('d/m/Y'),
            ];
        });

        // Liste de renvoi : uniquement les élèves qui ne sont pas à jour à la fin de cette tranche
        $lignes = $tous->filter(fn ($l) => $l['a_regler'] > 0)->values();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.liste-renvoi', [
            'classe' => $classe,
            'annee' => $annee,
            'frais' => $frais,
            'tranche' => $tranche,
            'echue' => $tranche['echeance']->copy()->endOfDay()->lt(now()),
            'lignes' => $lignes,
            'effectif' => $tous->count(),
            'aJour' => $tous->count() - $lignes->count(),
            'totaux' => [
                'arriere' => (int) $lignes->sum('arriere'),
                'paye_tranche' => (int) $lignes->sum('paye_tranche'),
                'reste_tranche' => (int) $lignes->sum('reste_tranche'),
                'a_regler' => (int) $lignes->sum('a_regler'),
                'paye' => (int) $lignes->sum('paye'),
                'reste_global' => (int) $lignes->sum('reste_global'),
            ],
            'logo' => $this->cheminLogo(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('liste-renvoi-'.Str::slug($classe->nom).'-tranche-'.$numero.'.pdf');
    }
}