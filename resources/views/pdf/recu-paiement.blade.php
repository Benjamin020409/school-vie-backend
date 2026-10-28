<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1B2A4A; }
        .bande-haut { height: 6px; background: #C9A227; }
        .contenu { padding: 20mm; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .sous-titre { color: #C9A227; font-size: 11px; margin-bottom: 20px; font-weight: bold; }
        .numero { text-align: right; font-size: 11px; color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        td { padding: 7px 4px; border-bottom: 1px solid #E2E4E9; }
        .label { color: #6b7280; width: 45%; }
        .montant-box { background: #FAFAF9; border: 1px solid #E2E4E9; border-top: 3px solid #C9A227; border-radius: 6px; padding: 16px; text-align: center; margin-bottom: 20px; }
        .montant-valeur { font-size: 24px; font-weight: bold; color: #1B2A4A; }
        .montant-label { font-size: 9px; color: #888; letter-spacing: 1px; margin-top: 4px; }
        .signature { margin-top: 50px; text-align: right; }
        .ligne-signature { width: 220px; border-top: 1px solid #999; margin: 40px 0 4px auto; }
    </style>
</head>
<body>

@php
        $logoPath = public_path('assets/logo.png');
        $logoSrc = file_exists($logoPath)
            ? 'data:'.mime_content_type($logoPath).';base64,'.base64_encode(file_get_contents($logoPath))
            : null;
    @endphp

    <div class="bande-haut"></div>
    <div class="contenu">
        <h1>SCHOOL VIE</h1>
        <div class="sous-titre">Reçu de paiement</div>
        <div class="numero">N° {{ $paiement->numero_recu }} — {{ $paiement->date_paiement->format('d/m/Y') }}</div>

        <table>
            <tr><td class="label">Élève</td><td>{{ $paiement->eleve->prenom }} {{ $paiement->eleve->nom }}</td></tr>
            <tr><td class="label">Matricule</td><td>{{ $paiement->eleve->matricule }}</td></tr>
            <tr><td class="label">Classe</td><td>{{ $classe?->nom }} ({{ $classe?->niveau?->nom }})</td></tr>
            <tr><td class="label">Année scolaire</td><td>{{ $annee?->libelle ?? '—' }}</td></tr>
            <tr><td class="label">Mode de paiement</td>
            <tr><td class="label">Moyen de paiement</td><td>{{ $paiement->moyen_paiement === 'banque' ? 'Banque' : 'Espèces' }}</td></tr>
                <td>
                    {{ $paiement->mode_paiement === 'bloc' ? 'En bloc' : 'Par tranche' }}
                    @if ($paiement->type_tranche)
                        ({{ ucfirst($paiement->type_tranche) }})
                    @endif
                </td>
            </tr>
        </table>

        <div class="montant-box">
            <div class="montant-valeur">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</div>
            <div class="montant-label">MONTANT VERSÉ</div>
        </div>

        <table>
            <tr><td class="label">Frais de scolarité total</td><td>{{ number_format($fraisTotal, 0, ',', ' ') }} FCFA</td></tr>
            <tr><td class="label">Solde restant après ce paiement</td><td>{{ number_format($montantRestant, 0, ',', ' ') }} FCFA</td></tr>
        </table>

        <div class="signature">
            <div class="ligne-signature"></div>
            Le comptable
        </div>
    </div>
</body>
</html>