<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 15px; }
        body { font-family: sans-serif; font-size: 10px; color: #1B2A4A; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .sous-titre { color: #C9A227; font-size: 10px; margin-bottom: 6px; }
        .info { font-size: 11px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 3px; text-align: center; }
        th { background: #f0f0f0; font-size: 9px; font-weight: normal; }
        .nom-col { text-align: left; width: 150px; }
        .matricule-col { width: 70px; }
        .case { display: inline-block; width: 9px; height: 9px; border: 1px solid #333; }
        .legende { margin-top: 10px; font-size: 10px; color: #444; }
    </style>
</head>
<body>
    <h1>SCHOOL VIE</h1>
    <div class="sous-titre">Fiche de présence journalière</div>
    <div class="info">
        Classe : <strong>{{ $classe->nom }}</strong> ({{ $classe->niveau->nom }}) &nbsp;&nbsp;&nbsp;
        Date : {{ $date }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="matricule-col" rowspan="2">Matricule</th>
                <th class="nom-col" rowspan="2">Nom &amp; Prénom</th>
                @for ($h = 1; $h <= 8; $h++)
                    <th colspan="2">H{{ $h }}</th>
                @endfor
            </tr>
            <tr>
                @for ($h = 1; $h <= 8; $h++)
                    <th>A</th>
                    <th>R</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @forelse ($eleves as $eleve)
                <tr>
                    <td>{{ $eleve->matricule }}</td>
                    <td class="nom-col"> {{ $eleve->nom }} {{ $eleve->prenom }}</td>
                    @for ($h = 1; $h <= 8; $h++)
                        <td><span class="case"></span></td>
                        <td><span class="case"></span></td>
                    @endfor
                </tr>
            @empty
                <tr>
                    <td colspan="18">Aucun élève inscrit dans cette classe.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="legende">A = Absent · R = Retard — cocher la case correspondante à l'heure de cours concernée.</div>
</body>
</html>