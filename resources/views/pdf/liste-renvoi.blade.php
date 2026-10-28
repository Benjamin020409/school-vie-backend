<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste de renvoi</title>
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
        .infos { font-size: 9px; color: #555; margin-bottom: 8px; line-height: 1.7; }
        .alerte { background: #FFF6E0; border: 1px solid #E8D28A; padding: 5px 8px; margin-bottom: 8px; font-size: 8.5px; }

        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        table.liste th { background: #1B2A4A; color: #FFFFFF; font-size: 8.5px; padding: 5px; border: 1px solid #1B2A4A; text-align: center; }
        table.liste td { border: 1px solid #E2E4E9; padding: 4px 5px; font-size: 8.5px; }
        table.liste tr:nth-child(even) td { background: #FAFAF9; }
        .gauche { text-align: left !important; }
        .droite { text-align: right; }
        .centre { text-align: center; }
        .total td { font-weight: bold; background: #EEF1F6 !important; }
        .rouge { color: #C0392B; font-weight: bold; }
        .vide { text-align: center; padding: 14px; color: #1E7B4F; font-weight: bold; }

        .note { font-size: 8px; color: #888; margin-top: 6px; }
        .signatures { margin-top: 22px; }
        .signatures td { border: none; width: 50%; text-align: center; vertical-align: top; }
        .signatures .titre-signature { font-weight: bold; font-size: 10px; }
        .signatures .espace { height: 34px; }
        .signatures .ligne { width: 200px; border-top: 1px solid #999; margin: 0 auto; padding-top: 4px; font-size: 8px; color: #888; }
        .pied { margin-top: 12px; text-align: center; font-size: 7.5px; color: #AAA; }
    </style>
</head>
<body>
@php
    $f = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $logoSrc = !empty($logo) && file_exists($logo)
        ? 'data:'.mime_content_type($logo).';base64,'.base64_encode(file_get_contents($logo))
        : null;
    $avecArriere = $tranche['numero'] > 1;
    $colonnesNom = 3;
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
            Liste de renvoi<br>
            <span class="badge-classe">{{ $classe->nom }} — {{ $tranche['nom'] }}</span>
        </td>
    </tr>
</table>
<div class="trait"></div>

<div class="infos">
    Niveau : <strong>{{ $classe->niveau->nom }}</strong> — Année scolaire : <strong>{{ $annee->libelle }}</strong> —
    Écolage complet : <strong>{{ $f($frais) }} FCFA</strong><br>
    {{ $tranche['nom'] }} : <strong>{{ $f($tranche['montant']) }} FCFA</strong>, du {{ $tranche['debut']->format('d/m/Y') }}
    au {{ $tranche['echeance']->format('d/m/Y') }} (échéance) —
    Effectif : <strong>{{ $effectif }}</strong> — À jour : <strong>{{ $aJour }}</strong> —
    <span class="rouge">À renvoyer : {{ $lignes->count() }}</span> — Édité le {{ now()->format('d/m/Y') }}
</div>

@if (! $echue)
    <div class="alerte">L'échéance de cette tranche n'est pas encore dépassée : cette liste est <strong>prévisionnelle</strong>.</div>
@endif

<table class="liste">
    <thead>
        <tr>
            <th style="width: 22px;">N°</th>
            <th style="width: 70px;">Matricule</th>
            <th>Nom &amp; Prénom</th>
            @if ($avecArriere)<th>Arriéré (tranches précédentes)</th>@endif
            <th>Payé sur la tranche</th>
            <th>Reste de la tranche</th>
            <th>Total à régler</th>
            <th>Total payé</th>
            <th>Reste écolage complet</th>
            <th>Dernier versement</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($lignes as $i => $l)
            <tr>
                <td class="centre">{{ $i + 1 }}</td>
                <td>{{ $l['matricule'] }}</td>
                <td class="gauche">{{ $l['nom'] }}</td>
                @if ($avecArriere)<td class="droite">{{ $f($l['arriere']) }}</td>@endif
                <td class="droite">{{ $f($l['paye_tranche']) }}</td>
                <td class="droite">{{ $f($l['reste_tranche']) }}</td>
                <td class="droite rouge">{{ $f($l['a_regler']) }}</td>
                <td class="droite">{{ $f($l['paye']) }}</td>
                <td class="droite">{{ $f($l['reste_global']) }}</td>
                <td class="centre">{{ $l['dernier'] ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ $avecArriere ? 10 : 9 }}" class="vide">Tous les élèves de la classe sont à jour pour cette tranche.</td></tr>
        @endforelse

        @if ($lignes->count() > 0)
            <tr class="total">
                <td colspan="{{ $colonnesNom }}">Total ({{ $lignes->count() }} élève(s))</td>
                @if ($avecArriere)<td class="droite">{{ $f($totaux['arriere']) }}</td>@endif
                <td class="droite">{{ $f($totaux['paye_tranche']) }}</td>
                <td class="droite">{{ $f($totaux['reste_tranche']) }}</td>
                <td class="droite">{{ $f($totaux['a_regler']) }}</td>
                <td class="droite">{{ $f($totaux['paye']) }}</td>
                <td class="droite">{{ $f($totaux['reste_global']) }}</td>
                <td></td>
            </tr>
        @endif
    </tbody>
</table>

<div class="note">
    Les versements couvrent les tranches dans l'ordre. « Total à régler » = arriéré des tranches précédentes + reste de cette tranche : c'est la somme à verser pour être à jour à l'échéance indiquée.
</div>

<table class="signatures">
    <tr>
        <td><div class="titre-signature">Le Comptable</div><div class="espace"></div><div class="ligne">Signature</div></td>
        <td><div class="titre-signature">Le Directeur</div><div class="espace"></div><div class="ligne">Signature</div></td>
    </tr>
</table>

<div class="pied">Document généré automatiquement par School Vie.</div>
</body>
</html>