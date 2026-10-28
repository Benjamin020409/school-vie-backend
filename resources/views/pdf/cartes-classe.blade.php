<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cartes scolaires</title>
    <style>
        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #1B2A4A; }

        .feuille { position: relative; width: 595pt; height: 830pt; }
        .abs { position: absolute; }

        /* 237 x 147 + bordure 2pt de chaque côté = 241 x 151 */
        .face {
            position: absolute;
            width: 237pt;
            height: 147pt;
            border: 2pt solid #2A8AD4;
            background: #FFFFFF;
            overflow: hidden;
        }

        .flag { width: 32pt; height: 20pt; }
        .stripe { width: 32pt; height: 4pt; }
        .vert { background: #006A4E; }
        .jaune { background: #FFCE00; }
        .canton {
            position: absolute; left: 0; top: 0;
            width: 12pt; height: 12pt;
            background: #D21034; color: #FFFFFF;
            text-align: center; font-size: 8pt; line-height: 12pt;
        }

        .republique { font-size: 6.5pt; font-weight: bold; color: #006A4E; }
        .devise { font-size: 5.3pt; color: #333333; }
        .logo { height: 24pt; width: auto; }
        .nom-ecole {
            font-size: 9pt; font-weight: bold; color: #1F5FA8;
            letter-spacing: 0.3pt; text-align: center;
        }
        .contact-entete {
            font-size: 5pt; color: #444444; text-align: center;
            white-space: nowrap;
        }
        .ligne-verte { height: 1.5pt; background: #006A4E; }
        .titre-carte {
            font-size: 6.5pt; font-weight: bold; color: #D21034;
            text-decoration: underline; white-space: nowrap;
        }

        table.champs { border-collapse: collapse; }
        table.champs td {
            padding: 0; height: 9pt;
            font-size: 6.3pt; font-weight: bold; color: #111111;
            white-space: nowrap;
        }
        table.champs td.lib { width: 54pt; }

        .annee {
            font-size: 6.3pt; font-weight: bold; color: #D21034;
            text-decoration: underline; white-space: nowrap;
        }

        .photo-box {
            width: 50pt; height: 62pt;
            border: 1pt solid #999999; background: #F2F2F2;
            overflow: hidden; text-align: center;
        }
        .photo-box img { width: 50pt; height: 62pt; }

        .signature-titulaire {
            width: 58pt; text-align: center;
            font-size: 5pt; color: #D21034; text-decoration: underline;
        }

        .texte-certif { font-size: 7pt; line-height: 1.7; text-align: justify; color: #111111; }
        .fait-a { font-size: 6.5pt; text-align: right; color: #333333; }
        .cachet {
            width: 46pt; height: 46pt;
            border: 1pt dashed #AAAAAA; border-radius: 23pt;
            text-align: center; font-size: 5.5pt; color: #AAAAAA;
            line-height: 46pt;
        }
        .titre-signature { font-size: 6.5pt; font-weight: bold; text-align: center; }
        .trait-signature { border-top: 0.7pt solid #777777; }
        .nom-directeur { font-size: 6pt; text-align: center; color: #1F5FA8; font-weight: bold; }
    </style>
</head>
<body>
    @php
        // --- À personnaliser (mêmes valeurs que la carte individuelle) ---
        $nomEcole = 'SCHOOL VIE';
        $ville = 'Lomé';
        $bp = '';
        $tel = '';
        $nomDirecteur = '';
        // ------------------------------------------------------------------

        // Géométrie de la planche (en points)
        $cw = 241;      // largeur d'une carte, bordure comprise
        $ch = 151;      // hauteur d'une carte, bordure comprise
        $ecart = 10;    // espace entre les cartes
        $margeHaut = 25;
        $margeGauche = (595.28 - ($colonnes * $cw + ($colonnes - 1) * $ecart)) / 2;

        $couper = fn ($texte) => mb_strimwidth((string) $texte, 0, 28, '…');
        $dateDuJour = now()->format('d/m/Y');
    @endphp

    @foreach ($feuilles as $feuille)
        <div class="feuille" @if (! $loop->last) style="page-break-after: always;" @endif>
            @foreach ($feuille['slots'] as $i => $carte)
                @php
                    $col = $i % $colonnes;
                    $ligne = intdiv($i, $colonnes);
                    $x = number_format($margeGauche + $col * ($cw + $ecart), 2, '.', '');
                    $y = number_format($margeHaut + $ligne * ($ch + $ecart), 2, '.', '');
                    $eleve = $carte['eleve'];
                    $naissance = $eleve->date_naissance
                        ? \Illuminate\Support\Carbon::parse($eleve->date_naissance)->format('d-m-Y')
                        : '—';
                @endphp

                @if ($feuille['type'] === 'recto')
                    <div class="face" style="left: {{ $x }}pt; top: {{ $y }}pt;">

                        <div class="abs flag" style="left: 7pt; top: 5pt;">
                            <div class="stripe vert"></div>
                            <div class="stripe jaune"></div>
                            <div class="stripe vert"></div>
                            <div class="stripe jaune"></div>
                            <div class="stripe vert"></div>
                            <div class="canton">★</div>
                        </div>

                        <div class="abs" style="left: 44pt; top: 6pt;">
                            <div class="republique">République Togolaise</div>
                            <div class="devise">Travail - Liberté - Patrie</div>
                        </div>

                        @if ($logo)
                            <div class="abs" style="right: 8pt; top: 3pt;">
                                <img src="{{ $logo }}" class="logo" alt="">
                            </div>
                        @endif

                        <div class="abs nom-ecole" style="left: 8pt; top: 27pt; width: 221pt;">{{ $nomEcole }}</div>

                        <div class="abs contact-entete" style="left: 8pt; top: 38pt; width: 221pt;">
                            BP : {{ $bp !== '' ? $bp : '..........' }}
                            &nbsp;&nbsp;Tél : {{ $tel !== '' ? $tel : '..........' }}
                            &nbsp;&nbsp;•&nbsp;&nbsp;{{ $ville }} - TOGO
                        </div>

                        <div class="abs ligne-verte" style="left: 8pt; top: 46pt; width: 221pt;"></div>

                        <div class="abs titre-carte" style="left: 8pt; top: 49pt;">CARTE D'IDENTITÉ SCOLAIRE</div>

                        <table class="abs champs" style="left: 8pt; top: 58pt; width: 165pt;">
                            <tr><td class="lib">Nom</td><td>: {{ $couper(mb_strtoupper($eleve->nom)) }}</td></tr>
                            <tr><td class="lib">Prénoms</td><td>: {{ $couper($eleve->prenom) }}</td></tr>
                            <tr><td class="lib">Né(e) le</td><td>: {{ $naissance }}</td></tr>
                            <tr><td class="lib">Lieu de Naiss.</td><td>: {{ $couper($eleve->lieu_naissance ?? '—') }}</td></tr>
                            <tr><td class="lib">Classe</td><td>: {{ $couper($carte['classe']) }}</td></tr>
                            <tr><td class="lib">N° Mle</td><td>: {{ $eleve->matricule }}</td></tr>
                            <tr><td class="lib">Sexe</td><td>: {{ $eleve->sexe ?? '—' }}</td></tr>
                        </table>

                        <div class="abs annee" style="left: 8pt; top: 126pt;">ANNÉE SCOLAIRE : {{ $carte['annee'] }}</div>

                        <div class="abs photo-box" style="right: 10pt; top: 52pt;">
                            <img src="{{ $carte['photo'] }}" alt="Photo">
                        </div>

                        <div class="abs signature-titulaire" style="right: 6pt; top: 118pt;">Signature du Titulaire</div>
                    </div>
                @else
                    <div class="face" style="left: {{ $x }}pt; top: {{ $y }}pt;">

                        <div class="abs texte-certif" style="left: 10pt; top: 12pt; width: 217pt;">
                            Je soussigné
                            <strong>{{ $nomDirecteur !== '' ? $nomDirecteur : '.............................................' }}</strong>,
                            Directeur de l'établissement <strong>{{ $nomEcole }}</strong>,
                            certifie que le/la titulaire de cette carte est élève dans mon établissement
                            pour l'année scolaire <strong>{{ $carte['annee'] }}</strong>.
                        </div>

                        <div class="abs fait-a" style="right: 10pt; top: 70pt; width: 217pt;">
                            Fait à {{ $ville }}, le {{ $dateDuJour }}
                        </div>

                        <div class="abs cachet" style="left: 18pt; top: 90pt;">Cachet</div>

                        <div class="abs" style="right: 14pt; top: 88pt; width: 100pt;">
                            <div class="titre-signature">Le Directeur</div>
                            <div style="height: 28pt;"></div>
                            <div class="trait-signature"></div>
                            <div class="nom-directeur">{{ $nomDirecteur }}</div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endforeach
</body>
</html>