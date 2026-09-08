<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Enseignant extends Model
{
    protected $fillable = ['nom', 'prenom', 'email', 'telephone', 'specialite', 'user_id'];

    protected static function booted(): void
    {
        static::created(function (Enseignant $enseignant) {
            $enseignant->creerCompteUtilisateurSiBesoin();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function creerCompteUtilisateurSiBesoin(): void
    {
        if ($this->user_id || empty($this->email)) {
            return;
        }

        $user = User::firstOrCreate(
            ['email' => $this->email],
            [
                'name' => trim("{$this->prenom} {$this->nom}"),
                'password' => bcrypt('password'),
            ]
        );

        $user->syncRoles(['enseignant']);

        $this->update(['user_id' => $user->id]);
    }
    public function specialites(): BelongsToMany
{
    return $this->belongsToMany(Matiere::class, 'enseignant_matiere');
}
}