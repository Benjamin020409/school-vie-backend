<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de notes</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1B2A4A;
            margin: 0;
        }

        .bande-haut {
            height: 6px;
            background: #C9A227;
        }

        .contenu {
            padding: 16mm 15mm 10mm 15mm;
        }

        .entete-etablissement {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .entete-etablissement td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .logo {
            height: 48px;
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
            font-size: 20px;
            font-weight: bold;
            color: #1B2A4A;
            padding-left: 10px;
        }
        .tagline-etablissement {
            font-size: 9px;
            color: #C9A227;
            letter-spacing: 0.5px;
            padding-left: 10px;
        }

        .titre-bulletin {
            text-align: right;
            font-size: 15px;
            font-weight: bold;
            color: #1B2A4A;
        }
        .badge-periode {
            display: inline-block;
            margin-top: 4px;
            padding: 3px 10px;
            background: #EEF1F6;
            color: #1B2A4A;
            font-size: 10px;
            font-weight: bold;
            border-radius: 10px;
        }

        .trait-separateur {
            border-top: 2px solid #1B2A4A;
            margin-bottom: 14px;
        }

        .encadre-eleve {
            width: 100%;
            border: 1px solid #E2E4E9;
            background: #FAFAF9;
            border-radius: 6px;
            border-collapse: separate;
            margin-bottom: 16px;
        }
        .encadre-eleve td {
            border: none;
            padding: 10px 16px;
            font-size: 11px;
            line-height: 1.8;
        }
        .encadre-eleve .nom-eleve {
            font-size: 13px;
            font-weight: bold;
            color: #1B2A4A;
        }
        .encadre-eleve .libelle {
            color: #888;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.notes {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        table.notes th {
            background: #1B2A4A;
            color: #ffffff;
            text-align: center;
            font-size: 9.5px;
            font-weight: bold;
            padding: 7px 6px;
            border: 1px solid #1B2A4A;
        }
        table.notes th:first-child { text-align: left; }
        table.notes td {
            border: 1px solid #E2E4E9;
            padding: 6px;
            font-size: 10px;
        }
        table.notes .centre { text-align: center; }
        table.notes .matiere-cell { font-weight: bold; color: #1B2A4A; }
        table.notes .signature-col { width: 155px; }

        .synthese {
            width: 100%;
            border-collapse: separate;
            margin-top: 4px;
            margin-bottom: 30px;
        }
        .synthese td {
            border: 1px solid #E2E4E9;
            border-top: 3px solid #C9A227;
            background: #FAFAF9;
            text-align: center;
            padding: 12px;
            width: 33.33%;
        }
        .synthese .valeur {
            font-size: 22px;
            font-weight: bold;
            color: #1B2A4A;
        }
        .synthese .label {
            margin-top: 4px;
            font-size: 9px;
            color: #888;
            letter-spacing: 1px;
        }

        .fait-a {
            text-align: right;
            font-size: 10px;
            color: #666;
            margin-bottom: 30px;
        }

        .signatures {
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            border: none;
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .signatures .titre-signature {
            font-weight: bold;
            font-size: 11px;
            color: #1B2A4A;
        }
        .signatures .signature-space { height: 55px; }
        .signatures .ligne {
            width: 220px;
            border-top: 1px solid #999;
            margin: 0 auto;
            padding-top: 5px;
            font-size: 9px;
            color: #888;
        }

        .pied-de-page {
            margin-top: 24px;
            text-align: center;
            font-size: 8px;
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
        <table class="entete-etablissement">
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
                <td class="titre-bulletin">
                    Bulletin de notes<br>
                    <span class="badge-periode">{{ $bulletin->periode->nom }} — {{ $bulletin->periode->anneeScolaire->libelle }}</span>
                </td>
            </tr>
        </table>

        <div class="trait-separateur"></div>

        <table class="encadre-eleve">
            <tr>
                <td style="width: 50%;">
                    <div class="libelle">Élève</div>
                    <div class="nom-eleve">{{ $bulletin->eleve->prenom }} {{ $bulletin->eleve->nom }}</div>
                    Matricule : {{ $bulletin->eleve->matricule }}
                </td>
                <td style="width: 50%;">
                    <div class="libelle">Scolarité</div>
                    Classe : <strong>{{ $bulletin->classe->nom }}</strong> ({{ $bulletin->classe->niveau->nom }})<br>
                    Effectif de la classe : {{ $bulletin->effectif_classe }}
                </td>
            </tr>
        </table>

        <table class="notes">
            <thead>
                <tr>
                    <th>Matière</th>
                    <th>Devoir</th>
                    <th>Interro.</th>
                    <th>Compo.</th>
                    <th>Coef.</th>
                    <th>Moyenne</th>
                    <th>Appréciation</th>
                    <th class="signature-col">Enseignant / Signature</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bulletin->detail_matieres as $m)
                    <tr style="background: {{ $loop->even ? '#FAFAF9' : '#FFFFFF' }};">
                        <td class="matiere-cell">{{ $m['matiere'] }}</td>
                        <td class="centre">{{ $m['notes']['devoir'] ?? '—' }}</td>
                        <td class="centre">{{ $m['notes']['interrogation'] ?? '—' }}</td>
                        <td class="centre">{{ $m['notes']['composition'] ?? '—' }}</td>
                        <td class="centre">{{ $m['coefficient'] }}</td>
                        <td class="centre">{{ $m['moyenne'] ?? '—' }}</td>
                        <td>{{ $m['appreciation'] ?? '—' }}</td>
                        <td>{{ $m['enseignant'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="synthese">
            <tr>
                <td>
                    <div class="valeur">{{ $bulletin->moyenne_generale ?? '—' }} / 20</div>
                    <div class="label">MOYENNE GÉNÉRALE</div>
                </td>
                <td>
                    <div class="valeur">{{ $bulletin->rang ?? '—' }}{{ $bulletin->rang ? 'e' : '' }} / {{ $bulletin->effectif_classe }}</div>
                    <div class="label">RANG DANS LA CLASSE</div>
                </td>
                <td>
                    <div class="valeur">{{ $bulletin->mention ?? '—' }}</div>
                    <div class="label">MENTION</div>
                </td>
            </tr>
        </table>

        <div class="fait-a">Fait à _____________________, le {{ now()->format('d/m/Y') }}</div>

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

        <div class="pied-de-page">Document généré automatiquement par School Vie — ne constitue pas une pièce officielle sans signature et cachet de l'établissement.</div>
    </div>
</body>
</html>