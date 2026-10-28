<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\FraisScolarite;
use App\Models\Inscription;
use App\Models\Niveau;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class RapportController extends Controller
{
    private function verifierAcces(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyRole(['comptable', 'direction', 'admin']),
            403,
            "Accès réservé à la comptabilité et à la direction."
        );
    }

    private function construire(Request $request, int $limite): array
    {
        $data = $request->validate([
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
            'niveau_id' => ['nullable', 'exists:niveaux,id'],
            'classe_id' => ['nullable', 'exists:classes,id'],
        ]);

        $anneeActive = AnneeScolaire::where('active', true)->first();
        abort_if(! $anneeActive, 422, "Aucune année scolaire active.");

        $debut = Carbon::parse($data['date_debut'] ?? $anneeActive->date_debut)->startOfDay();
        $fin = Carbon::parse($data['date_fin'] ?? now())->endOfDay();
        if ($fin->lt($debut)) {
            $fin = $debut->copy()->endOfDay();
        }

        $inscriptions = Inscription::where('annee_scolaire_id', $anneeActive->id)
            ->where('statut', 'en_cours')
            ->when($data['classe_id'] ?? null, fn ($q, $id) => $q->where('classe_id', $id))
            ->when($data['niveau_id'] ?? null, fn ($q, $id) => $q->whereHas('classe', fn ($qq) => $qq->where('niveau_id', $id)))
            ->with('classe.niveau')
            ->get();

        $eleveIds = $inscriptions->pluck('eleve_id')->unique()->values();
        $fraisParNiveau = FraisScolarite::where('annee_scolaire_id', $anneeActive->id)->pluck('montant', 'niveau_id');
        $payeParEleve = Paiement::where('annee_scolaire_id', $anneeActive->id)
            ->whereIn('eleve_id', $eleveIds)
            ->selectRaw('eleve_id, SUM(montant) as total')
            ->groupBy('eleve_id')
            ->pluck('total', 'eleve_id');
        // --- Situation actuelle, par niveau ---
        $parNiveau = [];
        $niveauxSansFrais = [];

        foreach ($inscriptions as $ins) {
            $niveau = $ins->classe->niveau;
            $frais = (int) ($fraisParNiveau[$niveau->id] ?? 0);
            $paye = min((int) ($payeParEleve[$ins->eleve_id] ?? 0), $frais);

            $parNiveau[$niveau->id] ??= ['niveau' => $niveau->nom, 'eleves' => 0, 'attendu' => 0, 'paye' => 0, 'soldes' => 0];
            $parNiveau[$niveau->id]['eleves']++;
            $parNiveau[$niveau->id]['attendu'] += $frais;
            $parNiveau[$niveau->id]['paye'] += $paye;
            if ($frais > 0 && $paye >= $frais) {
                $parNiveau[$niveau->id]['soldes']++;
            }
            if ($frais === 0) {
                $niveauxSansFrais[$niveau->id] = $niveau->nom;
            }
        }

        $parNiveau = collect($parNiveau)->map(function ($l) {
            $l['restant'] = $l['attendu'] - $l['paye'];
            $l['taux'] = $l['attendu'] > 0 ? round($l['paye'] / $l['attendu'] * 100, 1) : null;
            return $l;
        })->sortBy('niveau')->values();

        $attendu = (int) $parNiveau->sum('attendu');
        $paye = (int) $parNiveau->sum('paye');

        // --- Encaissements de la période ---
        $paiements = Paiement::with('eleve:id,nom,prenom,matricule')
            ->where('annee_scolaire_id', $anneeActive->id)
            ->whereIn('eleve_id', $eleveIds)
            ->whereBetween('date_paiement', [$debut->toDateString(), $fin->toDateString()])
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->get();
        $classeParEleve = $inscriptions->mapWithKeys(fn ($i) => [$i->eleve_id => $i->classe->nom]);
        $nomsTranches = ['mensuel' => 'mois', 'trimestriel' => 'trimestre', 'semestriel' => 'semestre'];

        $parMode = $paiements
            ->groupBy(fn ($p) => $p->mode_paiement === 'bloc'
                ? 'En bloc'
                : 'Par '.($nomsTranches[$p->type_tranche] ?? 'tranche'))
            ->map(fn ($g, $label) => [
                'mode' => $label,
                'nombre' => $g->count(),
                'montant' => (int) $g->sum('montant'),
            ])
            ->values();

        $parMois = $paiements
            ->groupBy(fn ($p) => $p->date_paiement->format('Y-m'))
            ->map(fn ($g, $mois) => [
                'mois' => $mois,
                'nombre' => $g->count(),
                'montant' => (int) $g->sum('montant'),
            ])
            ->sortKeys()
            ->values();

        $journal = $paiements->take($limite)->map(fn ($p) => [
            'id' => $p->id,
            'date' => $p->date_paiement->format('d/m/Y'),
            'numero_recu' => $p->numero_recu,
            'eleve' => $p->eleve ? "{$p->eleve->prenom} {$p->eleve->nom}" : '—',
            'matricule' => $p->eleve?->matricule,
            'classe' => $classeParEleve[$p->eleve_id] ?? '—',
            'mode' => $p->mode_paiement === 'bloc' ? 'En bloc' : 'Par '.($nomsTranches[$p->type_tranche] ?? 'tranche'),
            'montant' => (int) $p->montant,
        ])->values();

        return [
            'annee' => $anneeActive->libelle,
            'periode' => ['debut' => $debut->toDateString(), 'fin' => $fin->toDateString()],
            'filtres' => [
                'niveau' => isset($data['niveau_id']) ? Niveau::find($data['niveau_id'])?->nom : null,
                'classe' => isset($data['classe_id']) ? Classe::find($data['classe_id'])?->nom : null,
            ],
            'situation' => [
                'eleves' => $inscriptions->count(),
                'attendu' => $attendu,
                'paye' => $paye,
                'restant' => $attendu - $paye,
                'taux' => $attendu > 0 ? round($paye / $attendu * 100, 1) : null,
                'soldes' => (int) $parNiveau->sum('soldes'),
            ],
            'encaisse_periode' => [
                'montant' => (int) $paiements->sum('montant'),
                'nombre' => $paiements->count(),
            ],
            'par_niveau' => $parNiveau,
            'par_mode' => $parMode,
            'par_mois' => $parMois,
            'paiements' => $journal,
            'paiements_total' => $paiements->count(),
            'niveaux_sans_frais' => array_values($niveauxSansFrais),
        ];
    }

    public function financier(Request $request)
    {
        $this->verifierAcces($request);

        return response()->json($this->construire($request, 200));
    }

    public function financierPdf(Request $request)
    {
        $this->verifierAcces($request);

        set_time_limit(120);
        $rapport = $this->construire($request, 5000);

        $cheminLogo = public_path('assets/logo.png');
        $logo = file_exists($cheminLogo) ? str_replace('\\', '/', $cheminLogo) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.rapport-financier', [
            'rapport' => $rapport,
            'logo' => $logo,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('rapport-financier-'.Str::slug(now()->format('Y-m-d')).'.pdf');
    }
}