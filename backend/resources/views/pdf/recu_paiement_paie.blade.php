<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de paiement</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.45;
        }

        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }

        .kicker {
            color: #4f46e5;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin: 0 0 6px;
        }

        h1 {
            font-size: 24px;
            margin: 0;
        }

        .meta {
            color: #64748b;
            margin-top: 6px;
        }

        .grid {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }

        .col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 12px;
        }

        .box {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
        }

        .label {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin: 0 0 4px;
        }

        .value {
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 8px;
            text-align: left;
        }

        th {
            background: #f8fafc;
            color: #475569;
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .total {
            font-size: 18px;
            font-weight: 800;
            color: #4f46e5;
        }

        .footer {
            margin-top: 34px;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="kicker">Reçu de paiement</p>
        <h1>Fiche de paie {{ $paie->mois }}</h1>
        <p class="meta">Reçu généré le {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="grid">
        <div class="col">
            <div class="box">
                <p class="label">Collaborateur</p>
                <p class="value">{{ trim(($employe->nom ?? '') . ' ' . ($employe->prenom ?? '')) ?: '—' }}</p>
                <p>Matricule : {{ $employe->matricule ?? '—' }}</p>
                <p>Poste : {{ $employe->poste->nom ?? '—' }}</p>
            </div>
        </div>
        <div class="col">
            <div class="box">
                <p class="label">Paiement</p>
                <p class="value">{{ number_format((float) $mouvement->montant, 0, ',', ' ') }} MGA</p>
                <p>Caisse : {{ $caisse->nom ?? '—' }}</p>
                <p>Validé le : {{ optional($mouvement->valide_le)->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Référence</th>
                <th>Source</th>
                <th>Statut</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>#{{ $mouvement->id }}</td>
                <td>{{ $mouvement->source }}</td>
                <td>Validé</td>
                <td class="total">{{ number_format((float) $mouvement->montant, 0, ',', ' ') }} MGA</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Ce reçu confirme la validation du paiement de la fiche de paie et la sortie correspondante de la caisse.
    </div>
</body>
</html>
