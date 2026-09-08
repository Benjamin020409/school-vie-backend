<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Niveau extends Model
{

    protected $table = 'niveaux';

    protected $fillable = ['nom'];

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }
    
 public function fraisScolarite(): HasMany
{
    return $this->hasMany(FraisScolarite::class);
}
    
public function matieres(): BelongsToMany
{
    return $this->belongsToMany(Matiere::class, 'matiere_niveau')->withPivot('coefficient', 'heures_semaine');
}
}