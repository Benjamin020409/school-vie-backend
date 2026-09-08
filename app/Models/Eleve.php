<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eleve extends Model
{
    protected $fillable = [
        'matricule', 'nom', 'prenom', 'date_naissance',
        'lieu_naissance', 'sexe', 'tuteur_id', 'user_id',
    ];

    protected function casts(): array
    {
        return ['date_naissance' => 'date'];
    }

    public function tuteur(): BelongsTo
    {
        return $this->belongsTo(Tuteur::class);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function documents(): HasMany
    {
    return $this->hasMany(Document::class);
    }
}