<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Matiere extends Model
{
    protected $table = 'matieres';

    protected $fillable = ['nom', 'coefficient'];

    public function niveaux(): BelongsToMany
    {
        return $this->belongsToMany(Niveau::class, 'matiere_niveau')->withPivot('coefficient', 'heures_semaine');
    }
}