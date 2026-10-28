<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport financier</title>
    <style>
        @page { margin: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1B2A4A; margin: 0; }
        .bande-haut { height: 6px; background: #C9A227; }
        .contenu { padding: 10mm 12mm 10mm 12mm; }

        table { width: 100%; border-collapse: collapse; }
        .entete td { border: none; padding: 0; vertical-align: middle; }
        .logo { height: 40px; width: auto; }
        .badge { width: 32px; height: 32px; background: #1B2A4A; border-radius: 6px; color: #D8BE6A; text-align: center; font-size: 14px; font-weight: bold; line-height: 32px; }
        .nom-etab { font-size: 17px; font-weight: bold; padding-left: 10px; }
        .tagline { font-size: 8px; color: #C9A227; letter-spacing: 0.5px; padding-left: 10px; }
        .titre { text-align: right; font-size: 14px; font-weight: bold; }
        .badge-periode { display: inline-block; margin-top: 4px; padding: 3px 10px; background: #EEF1F6; font-size: 9px; font-weight: bold; border-radius: 10px; }
        .trait { border-top: 2px solid #1B2A4A; margin: 10px 0 12px; }

        .kpis td { width: 25%; border: 1px solid #E2E4E9; border-top: 3px solid #C9A227; background: #FAFAF9; text-align: center; padding: 9px 6px; }
        .kpis .valeur { font-size: 13px; font-weight: bold; }
        .kpis .label { font-size: 8px; color: #888; margin-top: 3px; letter-spacing: 0.5px; }

        h2 { font-size: 11px; margin: 16px 0 6px; color: #1B2A4A; }
        table.liste th { background: #1B2A4A; color: #FFFFFF; font-size: 8.5px; padding: 5px 5px; border: 1px solid #1B2A4A; text-align: left; }
        table.liste td { border: 1px solid #E2E4E9; padding: 4px 5px; font-size: 8.5px; }
        table.liste tr:nth-child(even) td { background: #FAFAF9; }
        .droite { text-align: right; }
        .total td { font-weight: bold; background: #EEF1F6 !important; }
        .rouge { color: #C0392B; }
        .note { font-size: 8px; color: #888; margin-top: 4px; }
        .alerte { background: #FFF6E0; border: 1px solid #E8D28A; padding: 6px 8px; margin-top: 10px; font-size: 8.5px; }
        .pied { margin-top: 18px; text-align: center; font-size: 7.5px; color: #AAA; }
    </style>
</head>
<body>
@php
    $f = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $moisFr = [1=>'Janvier',2=>'Février',3=>'Mars',4=>'Avril',5=>'Mai',6=>'Juin',7=>'Juillet',8=>'Août',9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Décembre'];
    $logoSrc = !empty($logo) && file_exists($logo)
        ? 'data:'.mime_content_type($logo).';base64,'.base64_encode(file_get_contents($logo))
        : null;
    $s = $rapport['situation'];
    $p = $rapport['periode'];
    $debut = \Illuminate\Support\Carbon::parse($p['debut'])->format('d/m/Y');
    $fin = \Illuminate\Support\Carbon::parse($p['fin'])->format('d/m/Y');
    $perimetre = collect([$rapport['filtres']['niveau'], $rapport['filtres']['classe']])->filter()->implode(' — ') ?: 'Tous les niveaux';
@endphp

<div class="bande-haut"></div>
<div class="contenu">

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
                Rapport financier<br>
                <span class="badge-periode">{{ $debut }} → {{ $fin }}</span>
            </td>
        </tr>
    </table>
    <div class="trait"></div>

    <div style="margin-bottom: 8px; font-size: 9px; color: #555;">
        Année scolaire {{ $rapport['annee'] }} — Périmètre : {{ $perimetre }} — Édité le {{ now()->format('d/m/Y') }}
    </div>

    <table class="kpis">
        <tr>
            <td><div class="valeur">{{ $f($rapport['encaisse_periode']['montant']) }} FCFA</div><div class="label">ENCAISSÉ SUR LA PÉRIODE ({{ $rapport['encaisse_periode']['nombre'] }} paiement(s))</div></td>
            <td><div class="valeur">{{ $f($s['attendu']) }} FCFA</div><div class="label">TOTAL ATTENDU ({{ $s['eleves'] }} élève(s))</div></td>
            <td><div class="valeur rouge">{{ $f($s['restant']) }} FCFA</div><div class="label">RESTANT DÛ</div></td>
            <td><div class="valeur">{{ $s['taux'] !== null ? $s['taux'].' %' : '—' }}</div><div class="label">TAUX DE RECOUVREMENT</div></td>
        </tr>
    </table>

    @if (count($rapport['niveaux_sans_frais']))
        <div class="alerte">Frais de scolarité non définis pour : {{ implode(', ', $rapport['niveaux_sans_frais']) }}. Ces élèves ne sont pas comptés dans le total attendu.</div>
    @endif

    <h2>Situation par niveau (à ce jour)</h2>
    <table class="liste">
        <thead><tr><th>Niveau</th><th class="droite">Élèves</th><th class="droite">Attendu</th><th class="droite">Payé</th><th class="droite">Restant</th><th class="droite">Soldés</th><th class="droite">Taux</th></tr></thead>
        <tbody>
            @forelse ($rapport['par_niveau'] as $l)
                <tr>
                    <td>{{ $l['niveau'] }}</td>
                    <td class="droite">{{ $l['eleves'] }}</td>
                    <td class="droite">{{ $f($l['attendu']) }}</td>
                    <td class="droite">{{ $f($l['paye']) }}</td>
                    <td class="droite">{{ $f($l['restant']) }}</td>
                    <td class="droite">{{ $l['soldes'] }}</td>
                    <td class="droite">{{ $l['taux'] !== null ? $l['taux'].' %' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Aucun élève dans ce périmètre.</td></tr>
            @endforelse
            @if (count($rapport['par_niveau']) > 1)
                <tr class="total">
                    <td>Total</td><td class="droite">{{ $s['eleves'] }}</td><td class="droite">{{ $f($s['attendu']) }}</td>
                    <td class="droite">{{ $f($s['paye']) }}</td><td class="droite">{{ $f($s['restant']) }}</td>
                    <td class="droite">{{ $s['soldes'] }}</td><td class="droite">{{ $s['taux'] !== null ? $s['taux'].' %' : '—' }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <h2>Encaissements par mode de paiement</h2>
    <table class="liste">
        <thead><tr><th>Mode</th><th class="droite">Paiements</th><th class="droite">Montant</th></tr></thead>
        <tbody>
            @forelse ($rapport['par_mode'] as $l)
                <tr><td>{{ $l['mode'] }}</td><td class="droite">{{ $l['nombre'] }}</td><td class="droite">{{ $f($l['montant']) }} FCFA</td></tr>
            @empty
                <tr><td colspan="3">Aucun paiement sur la période.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Encaissements par mois</h2>
    <table class="liste">
        <thead><tr><th>Mois</th><th class="droite">Paiements</th><th class="droite">Montant</th></tr></thead>
        <tbody>
            @forelse ($rapport['par_mois'] as $l)
                @php [$a, $m] = explode('-', $l['mois']); @endphp
                <tr><td>{{ $moisFr[(int) $m] }} {{ $a }}</td><td class="droite">{{ $l['nombre'] }}</td><td class="droite">{{ $f($l['montant']) }} FCFA</td></tr>
            @empty
                <tr><td colspan="3">Aucun paiement sur la période.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Journal des paiements ({{ $rapport['paiements_total'] }})</h2>
    <table class="liste">
        <thead><tr><th>Date</th><th>N° reçu</th><th>Élève</th><th>Classe</th><th>Mode</th><th class="droite">Montant</th></tr></thead>
        <tbody>
            @forelse ($rapport['paiements'] as $l)
                <tr>
                    <td>{{ $l['date'] }}</td><td>{{ $l['numero_recu'] }}</td><td>{{ $l['eleve'] }}</td>
                    <td>{{ $l['classe'] }}</td><td>{{ $l['mode'] }}</td><td class="droite">{{ $f($l['montant']) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Aucun paiement sur la période.</td></tr>
            @endforelse
            @if ($rapport['paiements_total'] > 0)
                <tr class="total"><td colspan="5">Total encaissé</td><td class="droite">{{ $f($rapport['encaisse_periode']['montant']) }}</td></tr>
            @endif
        </tbody>
    </table>
    <div class="note">Montants en FCFA. « Payé », « Restant » et « Taux » correspondent à la situation à la date d'édition ; les encaissements correspondent à la période indiquée.</div>

    <div class="pied">Document généré automatiquement par School Vie.</div>
</div>
</body>
</html>