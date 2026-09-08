<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 13px; color: #1B2A4A; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        .sous-titre { color: #C9A227; font-size: 11px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        td { padding: 6px 4px; border-bottom: 1px solid #e5e7eb; }
        .label { color: #6b7280; width: 40%; }
        .section-titre { font-size: 13px; font-weight: bold; margin: 18px 0 8px; border-bottom: 2px solid #1B2A4A; padding-bottom: 4px; }
        .signature { margin-top: 60px; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <h1>SCHOOL VIE</h1>
    <div class="sous-titre">Fiche d'inscription</div>

    <div class="section-titre">Informations de l'élève</div>
    <table>
        <tr><td class="label">Matricule</td><td>{{ $eleve->matricule }}</td></tr>
        <tr><td class="label">Nom</td><td>{{ $eleve->nom }}</td></tr>
        <tr><td class="label">Prénom</td><td>{{ $eleve->prenom }}</td></tr>
        <tr><td class="label">Date de naissance</td><td>{{ $eleve->date_naissance ?? '—' }}</td></tr>
        <tr><td class="label">Lieu de naissance</td><td>{{ $eleve->lieu_naissance ?? '—' }}</td></tr>
        <tr><td class="label">Sexe</td><td>{{ $eleve->sexe ?? '—' }}</td></tr>
    </table>

    <div class="section-titre">Scolarité</div>
    <table>
        <tr><td class="label">Classe</td><td>{{ $inscription?->classe?->nom ?? '—' }}</td></tr>
        <tr><td class="label">Année scolaire</td><td>{{ $inscription?->anneeScolaire?->libelle ?? '—' }}</td></tr>
        <tr><td class="label">Date d'inscription</td><td>{{ $inscription?->date_inscription ?? '—' }}</td></tr>
        <tr><td class="label">Statut</td><td>{{ $inscription?->statut ?? '—' }}</td></tr>
    </table>

    <div class="section-titre">Tuteur</div>
    <table>
        <tr><td class="label">Nom</td><td>{{ $eleve->tuteur?->nom ?? '—' }}</td></tr>
        <tr><td class="label">Téléphone</td><td>{{ $eleve->tuteur?->telephone ?? '—' }}</td></tr>
        <tr><td class="label">Email</td><td>{{ $eleve->tuteur?->email ?? '—' }}</td></tr>
        <tr><td class="label">Adresse</td><td>{{ $eleve->tuteur?->adresse ?? '—' }}</td></tr>
    </table>

    <div class="signature">
        <div>Signature du tuteur</div>
        <div>Signature de la direction</div>
    </div>
</body>
</html>