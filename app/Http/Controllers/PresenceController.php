<?php

namespace App\Http\Controllers;

use App\Mail\AbsenceNotificationMail;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Presence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PresenceController extends Controller
{
    public function elevesDuJour(Request $request, Classe $classe)
    {
        $date = $request->query('date', now()->toDateString());

        $eleves = Eleve::whereHas('inscriptions', function ($q) use ($classe) {
            $q->where('classe_id', $classe->id)->where('statut', 'en_cours');
        })->orderBy('nom')->get(['id', 'nom', 'prenom', 'matricule']);

        $presences = Presence::where('classe_id', $classe->id)->where('date', $date)->get()->keyBy('eleve_id');

        return $eleves->map(function ($e) use ($presences) {
            $p = $presences->get($e->id);
            return [
                'eleve_id' => $e->id,
                'nom' => $e->nom,
                'prenom' => $e->prenom,
                'matricule' => $e->matricule,
                'statut' => $p?->statut ?? 'present',
                'motif' => $p?->motif,
                'justifie' => $p?->justifie ?? false,
                'presence_id' => $p?->id,
            ];
        });
    }

    public function aujourdhui()
    {
        return Presence::where('date', now()->toDateString())
            ->with(['eleve:id,nom,prenom,matricule', 'classe:id,nom'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'eleve_id' => ['required', 'exists:eleves,id'],
            'classe_id' => ['required', 'exists:classes,id'],
            'statut' => ['required', 'in:absent,retard'],
            'motif' => ['nullable', 'string'],
        ]);

        $presence = Presence::updateOrCreate(
            ['eleve_id' => $data['eleve_id'], 'date' => now()->toDateString()],
            [
                'classe_id' => $data['classe_id'],
                'statut' => $data['statut'],
                'motif' => $data['motif'] ?? null,
                'justifie' => false,
            ]
        );

        $eleve = Eleve::with('tuteur')->find($data['eleve_id']);
        $classe = Classe::find($data['classe_id']);

        $dateAffichee = $presence->date->format('d/m/Y');
        $messageTexte = $data['statut'] === 'absent'
            ? "Bonjour, nous vous informons que {$eleve->prenom} {$eleve->nom} ({$classe->nom}) est absent(e) aujourd'hui ({$dateAffichee})."
            : "Bonjour, nous vous informons que {$eleve->prenom} {$eleve->nom} ({$classe->nom}) est arrivé(e) en retard aujourd'hui ({$dateAffichee}).";

        $emailEnvoye = false;

        if ($eleve->tuteur?->email) {
            try {
                Mail::to($eleve->tuteur->email)->send(new AbsenceNotificationMail(
                    "{$eleve->prenom} {$eleve->nom}",
                    $classe->nom,
                    $dateAffichee,
                    $data['statut'],
                    $data['motif'] ?? null,
                ));
                $emailEnvoye = true;
            } catch (\Throwable $e) {
                Log::warning('Échec envoi email absence : '.$e->getMessage());
            }
        }

        $whatsappLien = null;
        if ($eleve->tuteur?->telephone) {
            $telephone = preg_replace('/[^0-9]/', '', $eleve->tuteur->telephone);
            $whatsappLien = "https://wa.me/{$telephone}?text=".urlencode($messageTexte);
        }

        return response()->json([
            'presence' => $presence,
            'email_envoye' => $emailEnvoye,
            'whatsapp_lien' => $whatsappLien,
            'tuteur_email' => $eleve->tuteur?->email,
            'tuteur_telephone' => $eleve->tuteur?->telephone,
        ], 201);
    }

    public function justifier(Presence $presence)
    {
        $presence->update(['justifie' => true]);
        return $presence;
    }

    public function remettrePresent(Presence $presence)
    {
        $presence->delete();
        return response()->noContent();
    }
}