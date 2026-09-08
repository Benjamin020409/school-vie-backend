<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #1B2A4A; line-height: 1.6;">
    <h2 style="color: #1B2A4A; margin-bottom: 4px;">CSPL Enfants de L'espoir</h2>
    <p style="color: #C9A227; font-size: 12px; font-weight: bold; margin-top: 0;">GESTION SCOLAIRE</p>

    <p>Bonjour,</p>
    <p>
        Nous vous informons que <strong>{{ $eleveNom }}</strong> ({{ $classeNom }}) a été enregistré(e)
        comme <strong>{{ $statut === 'absent' ? 'absent(e)' : 'en retard' }}</strong> le {{ $date }}.
    </p>

    @if ($motif)
        <p>Motif indiqué : {{ $motif }}</p>
    @endif

    <p>Merci de contacter le secrétariat de l'établissement pour toute précision.</p>

    <p style="color: #888; font-size: 11px; margin-top: 24px;">
        Ce message est généré automatiquement par CSPL Enfants de L'espoir — merci de ne pas y répondre directement.
    </p>
</body>
</html>