<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1B2A4A; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .sous-titre { color: #C9A227; font-size: 11px; margin-bottom: 4px; }
        .info { font-size: 11px; margin-bottom: 14px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px 8px; text-align: left; }
        th { background: #f0f0f0; font-size: 10px; font-weight: normal; }
        .num-col { width: 30px; text-align: center; }
        .matricule-col { width: 90px; }
        .signature-col { width: 160px; }
    </style>
</head>
<body>

@php
        $logoPath = public_path('assets/logo.png');
        $logoSrc = file_exists($logoPath)
            ? 'data:'.mime_content_type($logoPath).';base64,'.base64_encode(file_get_contents($logoPath))
            : null;
    @endphp

    <h1>SCHOOL VIE</h1>
    <div class="sous-titre">Liste des élèves</div>
    <div class="info">
        Classe : <strong>{{ $classe->nom }}</strong> ({{ $classe->niveau->nom }}) &nbsp;&nbsp;
        Enseignant : {{ $enseignant->prenom }} {{ $enseignant->nom }} &nbsp;&nbsp;
        Effectif : {{ $eleves->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="num-col">#</th>
                <th class="matricule-col">Matricule</th>
                <th>Nom &amp; Prénom</th>
                <th class="signature-col">Signature / Observations</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($eleves as $i => $eleve)
                <tr>
                    <td class="num-col">{{ $i + 1 }}</td>
                    <td>{{ $eleve->matricule }}</td>
                    <td> {{ $eleve->nom }} {{ $eleve->prenom }}</td>
                    <td></td>
                </tr>
            @empty
                <tr><td colspan="4">Aucun élève inscrit dans cette classe.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>