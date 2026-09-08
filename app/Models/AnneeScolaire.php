<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnneeScolaire extends Model
{
    protected $table = 'annees_scolaires';

    protected $fillable = ['libelle', 'date_debut', 'date_fin', 'active'];

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }
    public function periodes(): HasMany
{
    return $this->hasMany(Periode::class);
}
}