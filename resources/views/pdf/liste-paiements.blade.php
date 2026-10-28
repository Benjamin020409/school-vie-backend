<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste de paiement</title>
    <style>
        @page { margin: 12mm 12mm 14mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1B2A4A; margin: 0; }
        .bande-haut { height: 5px; background: #C9A227; margin-bottom: 10px; }

        table { width: 100%; border-collapse: collapse; }
        .entete td { border: none; padding: 0; vertical-align: middle; }
        .logo { height: 40px; width: auto; }
        .badge { width: 32px; height: 32px; background: #1B2A4A; border-radius: 6px; color: #D8BE6A; text-align: center; font-size: 14px; font-weight: bold; line-height: 32px; }
        .nom-etab { font-size: 17px; font-weight: bold; padding-left: 10px; }
        .tagline { font-size: 8px; color: #C9A227; letter-spacing: 0.5px; padding-left: 10px; }
        .titre { text-align: right; font-size: 14px; font-weight: bold; }
        .badge-classe { display: inline-block; margin-top: 4px; padding: 3px 10px; background: #EEF1F6; font-size: 9px; font-weight: bold; border-radius: 10px; }
        .trait { border-top: 2px solid #1B2A4A; margin: 10px 0; }
        .infos { font-size: 9px; color: #555; margin-bottom: 10px; line-height: 1.6; }

        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        table.liste th { background: #1B2A4A; color: #FFFFFF; font-size: 8.5px; padding: 5px; border: 1px solid #1B2A4A; text-align: left; }
        table.liste td { border: 1px solid #E2E4E9; padding: 4px 5px; font-size: 8.5px; }
        table.liste tr:nth-child(even) td { background: #FAFAF9; }
        .droite { text-align: right; }
        .centre { text-align: center; }
        .total td { font-weight: bold; background: #EEF1F6 !important; }
        .vert { color: #1E7B4F; font-weight: bold; }
        .orange { color: #B7791F; font-weight: bold; }
        .rouge { color: #C0392B; font-weight: bold; }

        .signatures { margin-top: 26px; }
        .signatures td { border: none; width: 50%; text-align: center; vertical-align: top; }
        .signatures .titre-signature { font-weight: bold; font-size: 10px; }
        .signatures .espace { height: 36px; }
        .signatures .ligne { width: 200px; border-top: 1px solid #999; margin: 0 auto; padding-top: 4px; font-size: 8px; color: #888; }
        .pied { margin-top: 14px; text-align: center; font-size: 7.5px; color: #AAA; }
    </style>
</head>
<body>
@php
    $f = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $logoSrc = !empty($logo) && file_exists($logo)
        ? 'data:'.mime_content_type($logo).';base64,'.base64_encode(file_get_contents($logo))
        : null;
@endphp

<div class="bande-haut"></div>

<table class="entete">
    <tr>
        <td style="width: 55px;">
            @if ($logoSrc)<img src="{{ $logoSrc }}" class="logo" alt="">@else<div class="badge">SV</div>@endif
        </td>
        <td>
            <div class="nom-etab">SCHOOL VIE</div>
            <div class="tagline">GESTION SCOLAIRE</div>
        </td>
        <td class="titre">
            Liste de paiement<br>
            <span class="badge-classe">{{ $classe->nom }}</span>
        </td>
    </tr>
</table>
<div class="trait"></div>

<div class="infos">
    Niveau : <strong>{{ $classe->niveau->nom }}</strong> — Année scolaire : <strong>{{ $annee->libelle }}</strong><br>
    Écolage complet : <strong>{{ $f($frais) }} FCFA</strong> — Effectif : <strong>{{ $lignes->count() }}</strong> — Édité le {{ now()->format('d/m/Y') }}
</div>

<table class="liste">
    <thead>
        <tr>
            <th class="centre" style="width: 22px;">N°</th>
            <th style="width: 70px;">Matricule</th>
            <th>Nom &amp; Prénom</th>
            <th class="droite">Payé (FCFA)</th>
            <th class="droite">Reste (FCFA)</th>
            <th class="centre" style="width: 30px;">%</th>
            <th class="centre">Dernier versement</th>
            <th class="centre">Situation</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lignes as $i => $l)
            <tr>
                <td class="centre">{{ $i + 1 }}</td>
                <td>{{ $l['matricule'] }}</td>
                <td>{{ $l['nom'] }}</td>
                <td class="droite">{{ $f($l['paye']) }}</td>
                <td class="droite {{ $l['reste'] > 0 ? 'rouge' : 'vert' }}">{{ $f($l['reste']) }}</td>
                <td class="centre">{{ $l['taux'] }}</td>
                <td class="centre">{{ $l['dernier'] ?? '—' }}</td>
                <td class="centre {{ $l['statut'] === 'Soldé' ? 'vert' : ($l['statut'] === 'Partiel' ? 'orange' : 'rouge') }}">{{ $l['statut'] }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td colspan="3">Total classe (attendu : {{ $f($totaux['attendu']) }} FCFA)</td>
            <td class="droite">{{ $f($totaux['paye']) }}</td>
            <td class="droite">{{ $f($totaux['reste']) }}</td>
            <td class="centre">{{ round($totaux['taux']) }}</td>
            <td class="centre" colspan="2">{{ $totaux['soldes'] }} soldé(s)</td>
        </tr>
    </tbody>
</table>

<table class="signatures">
    <tr>
        <td><div class="titre-signature">Le Comptable</div><div class="espace"></div><div class="ligne">Signature</div></td>
        <td><div class="titre-signature">Le Directeur</div><div class="espace"></div><div class="ligne">Signature</div></td>
    </tr>
</table>

<div class="pied">Document généré automatiquement par School Vie.</div>
</body>
</html>