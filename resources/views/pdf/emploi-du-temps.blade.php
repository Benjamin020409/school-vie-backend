<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Emploi du temps</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1B2A4A;
            margin: 0;
        }

        .bande-haut {
            height: 6px;
            background: #C9A227;
        }

        .contenu {
            padding: 10mm 15mm 8mm 15mm;
        }

        .entete {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .entete td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .logo {
            height: 44px;
            width: auto;
        }
        .badge {
            width: 34px;
            height: 34px;
            background: #1B2A4A;
            border-radius: 6px;
            color: #D8BE6A;
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            line-height: 34px;
        }

        .nom-etablissement {
            font-size: 18px;
            font-weight: bold;
            color: #1B2A4A;
            padding-left: 10px;
        }
        .tagline-etablissement {
            font-size: 8px;
            color: #C9A227;
            letter-spacing: 0.5px;
            padding-left: 10px;
        }

        .titre-document {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            color: #1B2A4A;
        }
        .badge-titre {
            display: inline-block;
            margin-top: 4px;
            padding: 3px 12px;
            background: #EEF1F6;
            color: #1B2A4A;
            font-size: 10px;
            font-weight: bold;
            border-radius: 10px;
        }

        .trait-separateur {
            border-top: 2px solid #1B2A4A;
            margin-bottom: 10px;
        }

        table.edt {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.edt th {
            background: #1B2A4A;
            color: #ffffff;
            font-size: 10px;
            padding: 6px 4px;
            border: 1px solid #1B2A4A;
            text-align: center;
        }
        table.edt td {
            border: 1px solid #D5D8DE;
            padding: 4px;
            height: 34px;
            text-align: center;
            vertical-align: middle;
        }
        table.edt .heure-col {
            width: 55px;
            background: #EEF1F6;
            color: #1B2A4A;
            font-size: 9px;
            font-weight: bold;
        }
        table.edt th.heure-col {
            background: #1B2A4A;
            color: #ffffff;
        }
        table.edt .hors-cours {
            background: #F0F0F0;
        }
        table.edt .matiere {
            font-size: 10px;
            font-weight: bold;
            color: #1B2A4A;
        }
        table.edt .detail {
            font-size: 8px;
            color: #666;
            margin-top: 2px;
        }
        table.edt .vide {
            color: #CCCCCC;
        }

        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
        }
        .signatures td {
            border: none;
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .signatures .titre-signature {
            font-weight: bold;
            font-size: 10px;
            color: #1B2A4A;
        }
        .signatures .signature-space { height: 38px; }
        .signatures .ligne {
            width: 210px;
            border-top: 1px solid #999;
            margin: 0 auto;
            padding-top: 4px;
            font-size: 8px;
            color: #888;
        }

        .pied-de-page {
            margin-top: 14px;
            text-align: center;
            font-size: 7px;
            color: #aaa;
        }
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
        <table class="entete">
            <tr>
                <td style="width: 60px;">
                    @if ($logoSrc)
                        <img src="{{ $logoSrc }}" class="logo" alt="Logo">
                    @else
                        <div class="badge">SV</div>
                    @endif
                </td>
                <td>
                    <div class="nom-etablissement">SCHOOL VIE</div>
                    <div class="tagline-etablissement">GESTION SCOLAIRE</div>
                </td>
                <td class="titre-document">
                    Emploi du temps<br>
                    <span class="badge-titre">{{ $titre }}</span>
                </td>
            </tr>
        </table>

        <div class="trait-separateur"></div>

        <table class="edt">
            <thead>
                <tr>
                    <th class="heure-col">Heure</th>
                    @foreach ($jours as $jour)
                        <th>{{ ucfirst($jour) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for ($h = 1; $h <= max($heuresParJour); $h++)
                    <tr>
                        <td class="heure-col">{{ $h }}<sup>e</sup> heure</td>
                        @foreach ($jours as $jour)
                            @if ($h > $heuresParJour[$jour])
                                <td class="hors-cours">&nbsp;</td>
                            @else
                                @php
                                    $seance = $seances->first(fn ($s) => $s->jour === $jour && $s->heure === $h);
                                    $lignes = $seance ? explode("\n", $colonne($seance)) : [];
                                @endphp
                                <td>
                                    @if ($seance)
                                        <div class="matiere">{{ $lignes[0] ?? '' }}</div>
                                        @if (!empty($lignes[1]))
                                            <div class="detail">{{ $lignes[1] }}</div>
                                        @endif
                                    @else
                                        <span class="vide">—</span>
                                    @endif
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endfor
            </tbody>
        </table>

        <table class="signatures">
            <tr>
                <td>
                    <div class="titre-signature">Le Directeur</div>
                    <div class="signature-space"></div>
                    <div class="ligne">Signature</div>
                </td>
                <td>
                    <div class="titre-signature">Le Titulaire de classe</div>
                    <div class="signature-space"></div>
                    <div class="ligne">Signature</div>
                </td>
            </tr>
        </table>

        <div class="pied-de-page">Document généré le {{ now()->format('d/m/Y') }} par School Vie.</div>
    </div>
</body>
</html>