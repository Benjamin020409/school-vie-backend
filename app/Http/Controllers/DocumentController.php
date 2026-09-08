<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Eleve $eleve)
    {
        return $eleve->documents()->latest()->get();
    }

    public function store(Request $request, Eleve $eleve)
    {
        $request->validate([
            'fichiers' => ['required', 'array', 'min:1'],
            'fichiers.*' => ['file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $documents = [];

        foreach ($request->file('fichiers') as $fichier) {
            $chemin = $fichier->store("documents/{$eleve->id}", 'local');

            $documents[] = Document::create([
                'eleve_id' => $eleve->id,
                'nom_original' => $fichier->getClientOriginalName(),
                'chemin' => $chemin,
                'type_mime' => $fichier->getClientMimeType(),
                'taille' => $fichier->getSize(),
            ]);
        }

        return response()->json($documents, 201);
    }

    public function telecharger(Document $document)
    {
        if (! Storage::disk('local')->exists($document->chemin)) {
            abort(404, 'Fichier introuvable sur le serveur.');
        }

        return Storage::disk('local')->download($document->chemin, $document->nom_original);
    }

    public function destroy(Document $document)
    {
        Storage::disk('local')->delete($document->chemin);
        $document->delete();

        return response()->noContent();
    }
}