<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\FraisScolarite;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\Periode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PaiementController extends Controller
{
    private function anneeActive(): ?AnneeScolaire
    {
        return AnneeScolaire::where('active', true)->first();
    }

    private function fraisDuNiveau(int $niveauId, ?int $anneeId): int
    {
        if (! $anneeId) {
            return 0;
        }

        return (int) (FraisScolarite::where('niveau_id', $niveauId)
            ->where('annee_scolaire_id', $anneeId)
            ->value('montant') ?? 0);
    }

    private function verifierAcces(Request $request): void
{
    abort_unless(
        $request->user()->hasAnyRole(['comptable', 'direction', 'admin']),
        403,
        "Accès réservé à la comptabilité et à la direction."
    );
}

public function justificatif(Request $request, Paiement $paiement)
{
    $this->verifierAcces($request);

    abort_if(
        ! $paiement->justificatif_path || ! Storage::disk('local')->exists($paiement->justificatif_path),
        404,
        "Aucun justificatif pour ce paiement."
    );

    return Storage::disk('local')->response($paiement->justificatif_path);
}

    public function vueEnsemble(Request $request)
    {
        $encaisseAujourdhui = (int) Paiement::whereDate('date_paiement', now()->toDateString())->sum('montant');
        $annee = $this->anneeActive();

        $impayesTotaux = 0;
        $retards = [];

        if ($annee) {
            $frais = FraisScolarite::where('annee_scolaire_id', $annee->id)->pluck('montant', 'niveau_id');
            $payes = Paiement::where('annee_scolaire_id', $annee->id)
                ->selectRaw('eleve_id, SUM(montant) as total')
                ->groupBy('eleve_id')
                ->pluck('total', 'eleve_id');

            $inscriptions = Inscription::where('annee_scolaire_id', $annee->id)
                ->where('statut', 'en_cours')
                ->with(['eleve:id,nom,prenom', 'classe:id,nom,niveau_id'])
                ->get();

            foreach ($inscriptions as $ins) {
                $fraisTotal = (int) ($frais[$ins->classe->niveau_id] ?? 0);
                $restant = max($fraisTotal - (int) ($payes[$ins->eleve_id] ?? 0), 0);

                if ($restant > 0) {
                    $impayesTotaux += $restant;
                    $retards[] = [
                        'eleve_id' => $ins->eleve_id,
                        'nom' => $ins->eleve->nom,
                        'prenom' => $ins->eleve->prenom,
                        'classe' => $ins->classe->nom,
                        'montant_restant' => $restant,
                    ];
                }
            }

            usort($retards, fn ($a, $b) => $b['montant_restant'] <=> $a['montant_restant']);
        }

        return response()->json([
            'encaisse_aujourdhui' => $encaisseAujourdhui,
            'impayes_totaux' => $impayesTotaux,
            'eleves_avec_solde' => count($retards),
            'retards' => array_slice($retards, 0, 10),
        ]);
    }

    public function eleves(Classe $classe)
    {
        $eleves = Eleve::whereHas('inscriptions', function ($q) use ($classe) {
            $q->where('classe_id', $classe->id)->where('statut', 'en_cours');
        })->orderBy('nom')->get(['id', 'nom', 'prenom', 'matricule']);

        return response()->json([
            'frais_scolarite' => $this->fraisDuNiveau($classe->niveau_id, $classe->annee_scolaire_id),
            'eleves' => $eleves,
        ]);
    }

    public function store(Request $request)
{
    $this->verifierAcces($request);

    $data = $request->validate([
        'eleve_id' => ['required', 'exists:eleves,id'],
        'mode_paiement' => ['required', 'in:bloc,tranche'],
        'type_tranche' => ['required_if:mode_paiement,tranche', 'nullable', 'in:mensuel,trimestriel,semestriel'],
        'moyen_paiement' => ['required', 'in:especes,banque'],
        'justificatif' => ['required_if:moyen_paiement,banque', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        'montant' => ['required', 'integer', 'min:1'],
    ], [
        'justificatif.required_if' => "Pour un paiement par banque, ajoute la photo du reçu bancaire.",
        'justificatif.mimes' => "Le justificatif doit être une image (JPG, PNG, WEBP) ou un PDF.",
        'justificatif.max' => "Le justificatif ne doit pas dépasser 4 Mo.",
        'justificatif.uploaded' => "L'envoi du justificatif a échoué (fichier trop lourd pour le serveur).",
    ]);

    // Le formulaire est envoyé en multipart : le montant arrive en texte, on le convertit
    $montant = (int) $data['montant'];

    $annee = $this->anneeActive();
    abort_if(! $annee, 422, "Aucune année scolaire active — impossible d'enregistrer un paiement.");

    $inscription = Inscription::where('eleve_id', $data['eleve_id'])
        ->where('annee_scolaire_id', $annee->id)
        ->where('statut', 'en_cours')
        ->with('classe')
        ->first();
    abort_if(! $inscription, 422, "Cet élève n'a pas d'inscription en cours pour l'année scolaire active ({$annee->libelle}).");

    $fraisTotal = $this->fraisDuNiveau($inscription->classe->niveau_id, $annee->id);
    abort_if($fraisTotal <= 0, 422, "Les frais de scolarité de ce niveau ne sont pas définis pour {$annee->libelle}.");

    $dejaPaye = (int) Paiement::where('eleve_id', $data['eleve_id'])
        ->where('annee_scolaire_id', $annee->id)
        ->sum('montant');
    $montantRestant = max($fraisTotal - $dejaPaye, 0);

    if ($data['mode_paiement'] === 'bloc' && $montant !== $montantRestant) {
        return response()->json([
            'message' => "Pour un paiement en bloc, le montant doit être exactement égal au solde restant ({$montantRestant}).",
        ], 422);
    }

    if ($montant > $montantRestant) {
        return response()->json([
            'message' => "Le montant saisi ({$montant}) dépasse le solde restant ({$montantRestant}).",
        ], 422);
    }

    // Le fichier n'est enregistré qu'une fois toutes les vérifications passées
    $chemin = null;
    if ($data['moyen_paiement'] === 'banque') {
        $chemin = $request->file('justificatif')->store("justificatifs/{$data['eleve_id']}", 'local');
    }

    $paiement = Paiement::create([
        'eleve_id' => $data['eleve_id'],
        'annee_scolaire_id' => $annee->id,
        'montant' => $montant,
        'mode_paiement' => $data['mode_paiement'],
        'type_tranche' => $data['mode_paiement'] === 'tranche' ? $data['type_tranche'] : null,
        'moyen_paiement' => $data['moyen_paiement'],
        'justificatif_path' => $chemin,
        'numero_recu' => 'REC-'.now()->format('Ymd').'-'.strtoupper(Str::random(5)),
        'date_paiement' => now(),
    ]);

    return response()->json($paiement, 201);
}

    private function calculerDelai(?Paiement $dernier, Collection $paiementsEleve, ?AnneeScolaire $annee): ?string
    {
        if (! $annee) {
            return null;
        }

        $debut = Carbon::parse($annee->date_debut);
        $fin = Carbon::parse($annee->date_fin);

        if (! $dernier || $dernier->mode_paiement === 'bloc') {
            return $fin->format('d/m/Y');
        }

        $nombreTranches = $paiementsEleve
            ->where('mode_paiement', 'tranche')
            ->where('type_tranche', $dernier->type_tranche)
            ->count();

        if ($dernier->type_tranche === 'mensuel') {
            return $debut->copy()->addMonths($nombreTranches)->endOfMonth()->format('d/m/Y');
        }

        if ($dernier->type_tranche === 'trimestriel') {
            $periodes = Periode::where('annee_scolaire_id', $annee->id)->orderBy('date_debut')->get();
            $prochaine = $periodes->get($nombreTranches);

            return ($prochaine ? Carbon::parse($prochaine->date_fin) : $fin)->format('d/m/Y');
        }

        // Semestriel : après le premier versement, la dernière échéance est la fin d'année
        return $fin->format('d/m/Y');
    }

    public function tableau(Classe $classe)
    {
        $annee = AnneeScolaire::find($classe->annee_scolaire_id);
        $fraisTotal = $this->fraisDuNiveau($classe->niveau_id, $annee?->id);

        $eleves = Eleve::whereHas('inscriptions', function ($q) use ($classe) {
            $q->where('classe_id', $classe->id)->where('statut', 'en_cours');
        })->orderBy('nom')->get();

        $paiementsParEleve = Paiement::whereIn('eleve_id', $eleves->pluck('id'))
            ->where('annee_scolaire_id', $annee?->id)
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->get()
            ->groupBy('eleve_id');

        $resultat = $eleves->map(function ($eleve) use ($fraisTotal, $annee, $paiementsParEleve) {
            $paiements = $paiementsParEleve->get($eleve->id, collect());
            $restant = max($fraisTotal - (int) $paiements->sum('montant'), 0);
            $dernier = $paiements->first();

            if ($fraisTotal <= 0) {
                $delai = null;
            } elseif ($restant <= 0) {
                $delai = 'Soldé';
            } else {
                $delai = $this->calculerDelai($dernier, $paiements, $annee);
            }

            return [
                'eleve_id' => $eleve->id,
                'nom' => $eleve->nom,
                'prenom' => $eleve->prenom,
                'matricule' => $eleve->matricule,
                'montant_scolarite' => $fraisTotal,
                'montant_restant' => $restant,
                'mode_paiement' => $dernier?->mode_paiement,
                'type_tranche' => $dernier?->type_tranche,
                'delai_paiement' => $delai,
                'dernier_paiement_montant' => $dernier?->montant,
                'dernier_paiement_date' => $dernier?->date_paiement?->format('d/m/Y'),
                'dernier_paiement_id' => $dernier?->id,
                'dernier_paiement_moyen' => $dernier?->moyen_paiement,
                'dernier_paiement_justificatif' => (bool) $dernier?->justificatif_path,
            ];
        });

        return response()->json($resultat);
    }

    public function recuPdf(Paiement $paiement)
    {
        $paiement->load('eleve');
        $annee = AnneeScolaire::find($paiement->annee_scolaire_id);

        $inscription = Inscription::where('eleve_id', $paiement->eleve_id)
            ->where('annee_scolaire_id', $paiement->annee_scolaire_id)
            ->with('classe.niveau')
            ->first();

        $fraisTotal = $inscription
            ? $this->fraisDuNiveau($inscription->classe->niveau_id, $paiement->annee_scolaire_id)
            : 0;

        // Solde juste après CE paiement (les versements antérieurs + celui-ci)
        $dejaPaye = (int) Paiement::where('eleve_id', $paiement->eleve_id)
            ->where('annee_scolaire_id', $paiement->annee_scolaire_id)
            ->where('id', '<=', $paiement->id)
            ->sum('montant');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.recu-paiement', [
            'paiement' => $paiement,
            'classe' => $inscription?->classe,
            'annee' => $annee,
            'fraisTotal' => $fraisTotal,
            'montantRestant' => max($fraisTotal - $dejaPaye, 0),
        ]);

        return $pdf->download("recu-{$paiement->numero_recu}.pdf");
    }
}