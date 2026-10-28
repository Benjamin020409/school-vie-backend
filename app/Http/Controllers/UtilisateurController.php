<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UtilisateurController extends Controller
{
    private const ROLES = ['secretaire', 'surveillant', 'comptable'];

    private function verifierDirection(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyRole(['direction', 'admin']),
            403,
            "Seule la direction peut gérer ces comptes."
        );
    }

    public function index(Request $request)
    {
        $this->verifierDirection($request);

        return User::role(self::ROLES)
            ->with('roles:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'created_at']);
    }

    public function store(Request $request)
    {
        $this->verifierDirection($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(self::ROLES)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt('password'),
        ]);

        $user->assignRole($data['role']);

        return response()->json($user->load('roles:id,name'), 201);
    }

    public function reinitialiserMotDePasse(Request $request, User $user)
    {
        $this->verifierDirection($request);
        abort_unless($user->hasAnyRole(self::ROLES), 403, "Ce compte ne peut pas être géré ici.");

        $user->update(['password' => bcrypt('password')]);

        return response()->json(['message' => 'Mot de passe réinitialisé.']);
    }

    public function destroy(Request $request, User $user)
    {
        $this->verifierDirection($request);
        abort_unless($user->hasAnyRole(self::ROLES), 403, "Ce compte ne peut pas être supprimé ici.");
        abort_if($user->id === $request->user()->id, 422, "Tu ne peux pas supprimer ton propre compte.");

        $user->delete();

        return response()->noContent();
    }
}