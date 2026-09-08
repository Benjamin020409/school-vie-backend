<?php

namespace App\Http\Controllers;

use App\Models\Niveau;
use Illuminate\Http\Request;

class NiveauController extends Controller
{
    public function index()
    {
        return Niveau::with('matieres:id,nom')->orderBy('nom')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'unique:niveaux,nom'],
        ]);

        return response()->json(Niveau::create($data), 201);
    }

    public function syncMatieres(Request $request, Niveau $niveau)
    {
        $data = $request->validate([
            'matieres' => ['present', 'array'],
            'matieres.*.id' => ['required', 'exists:matieres,id'],
            'matieres.*.coefficient' => ['required', 'numeric', 'min:0.5'],
            'matieres.*.heures_semaine' => ['required', 'integer', 'min:1', 'max:15'],
        ]);

        $syncData = [];
        foreach ($data['matieres'] as $m) {
            $syncData[$m['id']] = [
                'coefficient' => $m['coefficient'],
                'heures_semaine' => $m['heures_semaine'],
            ];
        }

        $niveau->matieres()->sync($syncData);

        return $niveau->load('matieres:id,nom');
    }
}