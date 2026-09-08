<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    protected $fillable = ['classe_id', 'matiere_id', 'enseignant_id', 'titre', 'type', 'date_evaluation', 'coefficient', 'periode_id'];

    protected function casts(): array
    {
        return ['date_evaluation' => 'date'];
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
    public function periode(): BelongsTo
{
    return $this->belongsTo(Periode::class);
}
}