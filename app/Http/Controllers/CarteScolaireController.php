<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CarteScolaireController extends Controller
{
    private const COLONNES = 2;
    private const PAR_PAGE_AUTORISES = [4, 6, 8, 10];

    private function verifierAcces(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyRole(['secretaire', 'direction', 'admin']),
            403,
            "Accès réservé au secrétariat et à la direction."
        );
    }

    private function elevesDeLaClasse(Classe $classe)
    {
        return Eleve::whereHas('inscriptions', function ($q) use ($classe) {
                $q->where('classe_id', $classe->id)->where('statut', 'en_cours');
            })
            ->with(['inscriptions' => function ($q) use ($classe) {
                $q->where('classe_id', $classe->id)->with('anneeScolaire');
            }])
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
    }

    private function aUnePhoto(Eleve $eleve): bool
    {
        return $eleve->photo_path && Storage::disk('local')->exists($eleve->photo_path);
    }

    public function verification(Request $request, Classe $classe)
    {
        $this->verifierAcces($request);

        $eleves = $this->elevesDeLaClasse($classe);
        $sansPhoto = $eleves->reject(fn ($e) => $this->aUnePhoto($e))->values();

        return response()->json([
            'classe' => $classe->nom,
            'total' => $eleves->count(),
            'avec_photo' => $eleves->count() - $sansPhoto->count(),
            'sans_photo' => $sansPhoto->map(fn ($e) => [
                'id' => $e->id,
                'nom' => $e->nom,
                'prenom' => $e->prenom,
                'matricule' => $e->matricule,
            ]),
            'pret' => $eleves->count() > 0 && $sansPhoto->isEmpty(),
        ]);
    }

    public function pdf(Request $request, Classe $classe)
    {
        $this->verifierAcces($request);

        $data = $request->validate([
            'par_page' => ['nullable', 'integer', Rule::in(self::PAR_PAGE_AUTORISES)],
            'verso' => ['nullable', 'boolean'],
        ]);

        $parPage = (int) ($data['par_page'] ?? 8);
        $avecVerso = $request->boolean('verso', true);

        $eleves = $this->elevesDeLaClasse($classe);
        abort_if($eleves->isEmpty(), 422, "Aucun élève inscrit dans cette classe.");

        $sansPhoto = $eleves->reject(fn ($e) => $this->aUnePhoto($e));
        if ($sansPhoto->isNotEmpty()) {
            return response()->json([
                'message' => "Impression impossible : {$sansPhoto->count()} élève(s) sans photo.",
            ], 422);
        }

        set_time_limit(180);
        ini_set('memory_limit', '512M');

        $cartes = $eleves->map(function ($eleve) use ($classe) {
            $inscription = $eleve->inscriptions->first();

            return [
                'eleve' => $eleve,
                'classe' => $classe->nom,
                'annee' => $inscription?->anneeScolaire?->libelle ?? '—',
                'photo' => str_replace('\\', '/', Storage::disk('local')->path($eleve->photo_path)),
            ];
        })->values();

        $cheminLogo = public_path('assets/logo.png');
        $logo = file_exists($cheminLogo) ? str_replace('\\', '/', $cheminLogo) : null;

        // Pagination : une feuille recto par groupe de cartes, suivie (si demandé) de sa feuille verso
        $feuilles = [];
        foreach ($cartes->chunk($parPage) as $groupe) {
            $slots = $groupe->values()->all();
            $feuilles[] = ['type' => 'recto', 'slots' => $slots];

            if ($avecVerso) {
                // Verso en miroir : chaque carte retombe exactement derrière son recto
                $miroir = [];
                foreach ($slots as $i => $carte) {
                    $ligne = intdiv($i, self::COLONNES);
                    $colonne = $i % self::COLONNES;
                    $miroir[$ligne * self::COLONNES + (self::COLONNES - 1 - $colonne)] = $carte;
                }
                ksort($miroir);
                $feuilles[] = ['type' => 'verso', 'slots' => $miroir];
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.cartes-classe', [
            'feuilles' => $feuilles,
            'colonnes' => self::COLONNES,
            'logo' => $logo,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('cartes-'.Str::slug($classe->nom).'.pdf');
    }
}