<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    protected $fillable = ['eleve_id', 'montant', 'mode_paiement', 'type_tranche', 'numero_recu', 'date_paiement'];

    protected function casts(): array
    {
        return ['date_paiement' => 'date'];
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }
}