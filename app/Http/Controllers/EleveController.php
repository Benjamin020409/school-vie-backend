<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Tuteur;
use Illuminate\Http\Request;

class EleveController extends Controller
{
    public function index(Request $request)
    {
        $query = Eleve::with(['tuteur', 'inscriptions.classe']);

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('nom', 'like', "%{$q}%")
                    ->orWhere('prenom', 'like', "%{$q}%")
                    ->orWhere('matricule', 'like', "%{$q}%");
            });
        }

        return $query->orderBy('nom')->limit(50)->get();
    }

    public function show(Eleve $eleve)
    {
        return $eleve->load(['tuteur', 'inscriptions.classe']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'matricule' => ['required', 'string', 'unique:eleves,matricule'],
            'nom' => ['required', 'string'],
            'prenom' => ['required', 'string'],
            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string'],
            'sexe' => ['nullable', 'in:M,F'],
            'tuteur_nom' => ['nullable', 'string'],
            'tuteur_telephone' => ['nullable', 'string'],
            'tuteur_email' => ['nullable', 'email'],
        ]);

        $tuteurId = null;
        if (! empty($data['tuteur_nom'])) {
            $tuteur = Tuteur::create([
                'nom' => $data['tuteur_nom'],
                'telephone' => $data['tuteur_telephone'] ?? null,
                'email' => $data['tuteur_email'] ?? null,
            ]);
            $tuteurId = $tuteur->id;
        }

        $eleve = Eleve::create([
            ...collect($data)->only(['matricule', 'nom', 'prenom', 'date_naissance', 'lieu_naissance', 'sexe'])->toArray(),
            'tuteur_id' => $tuteurId,
        ]);

        return response()->json($eleve->load(['tuteur', 'inscriptions.classe']), 201);
    }

    public function update(Request $request, Eleve $eleve)
    {
        $data = $request->validate([
            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string'],
            'sexe' => ['nullable', 'in:M,F'],
            'tuteur_email' => ['nullable', 'email'],
            'tuteur_adresse' => ['nullable', 'string'],
        ]);

        $eleve->update([
            'date_naissance' => $data['date_naissance'] ?? $eleve->date_naissance,
            'lieu_naissance' => $data['lieu_naissance'] ?? $eleve->lieu_naissance,
            'sexe' => $data['sexe'] ?? $eleve->sexe,
        ]);

        if ($eleve->tuteur_id) {
            $tuteurData = array_filter([
                'email' => $data['tuteur_email'] ?? null,
                'adresse' => $data['tuteur_adresse'] ?? null,
            ], fn ($v) => $v !== null);

            if ($tuteurData) {
                $eleve->tuteur()->update($tuteurData);
                // si l'email vient d'être renseigné ici, on tente aussi la création du compte
                $eleve->tuteur->fresh()->creerCompteUtilisateurSiBesoin();
            }
        }

        return $eleve->load(['tuteur', 'inscriptions.classe']);
    }

    public function ficheInscription(Eleve $eleve)
    {
        $eleve->load(['tuteur', 'inscriptions.classe.niveau', 'inscriptions.anneeScolaire']);
        $inscription = $eleve->inscriptions->sortByDesc('date_inscription')->first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.fiche-inscription', [
            'eleve' => $eleve,
            'inscription' => $inscription,
        ]);

        return $pdf->download("fiche-inscription-{$eleve->matricule}.pdf");
    }
}