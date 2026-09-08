<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 15px; }
        body { font-family: sans-serif; font-size: 10px; color: #1B2A4A; }
        h1 { font-size: 16px; margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #999; padding: 4px; text-align: center; vertical-align: middle; }
        th { background: #f0f0f0; font-size: 10px; }
        .heure-col { width: 60px; background: #fafafa; font-size: 9px; color: #666; }
        .case { white-space: pre-line; font-size: 9px; }
        .vide { color: #ccc; }
    </style>
</head>
<body>
    <h1>Emploi du temps — {{ $titre }}</h1>

    <table>
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
                    <td class="heure-col">{{ $h }}</td>
                    @foreach ($jours as $jour)
                        @if ($h > $heuresParJour[$jour])
                            <td>&nbsp;</td>
                        @else
                            @php
                                $seance = $seances->first(fn ($s) => $s->jour === $jour && $s->heure === $h);
                            @endphp
                            <td class="case">
                                {{ $seance ? $colonne($seance) : '' }}
                                @if (! $seance)
                                    <span class="vide">—</span>
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endfor
        </tbody>
    </table>
</body>
</html>