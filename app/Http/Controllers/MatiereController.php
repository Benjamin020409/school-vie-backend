<?php

namespace App\Http\Controllers;

use App\Models\Matiere;
use Illuminate\Http\Request;

class MatiereController extends Controller
{
    public function index()
    {
        return Matiere::with('niveaux:id,nom')->orderBy('nom')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'unique:matieres,nom'],
        ]);

        return response()->json(Matiere::create($data), 201);
    }
}