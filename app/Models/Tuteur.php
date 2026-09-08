<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tuteur extends Model
{
    protected $fillable = ['nom', 'telephone', 'email', 'adresse', 'user_id'];

    protected static function booted(): void
    {
        static::created(function (Tuteur $tuteur) {
            $tuteur->creerCompteUtilisateurSiBesoin();
        });
    }

    public function eleves(): HasMany
    {
        return $this->hasMany(Eleve::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Crée automatiquement un compte utilisateur (rôle "parent") pour ce tuteur,
     * si un email est renseigné et qu'aucun compte n'existe déjà.
     * Mot de passe par défaut : "password" (à faire changer au tuteur à sa première connexion).
     */
    public function creerCompteUtilisateurSiBesoin(): void
    {
        if ($this->user_id || empty($this->email)) {
            return;
        }

        $user = User::firstOrCreate(
            ['email' => $this->email],
            [
                'name' => $this->nom,
                'password' => bcrypt('password'),
            ]
        );

        $user->syncRoles(['parent']);

        $this->update(['user_id' => $user->id]);
    }
}