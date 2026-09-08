<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = ['eleve_id', 'nom_original', 'chemin', 'type_mime', 'taille'];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }
}