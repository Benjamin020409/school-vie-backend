<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bulletin extends Model
{
    protected $fillable = [
        'eleve_id', 'classe_id', 'periode_id', 'moyenne_generale',
        'rang', 'effectif_classe', 'mention', 'detail_matieres', 'statut', 'date_validation',
    ];

    protected function casts(): array
    {
        return [
            'detail_matieres' => 'array',
            'date_validation' => 'datetime',
        ];
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class);
    }
}