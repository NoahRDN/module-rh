<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de paiement</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 30px;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td, th {
            border: 1px solid #000;
            padding: 6px 8px;
        }
        .no-border td { border: none; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .title { font-size: 16px; font-weight: bold; text-align: center; }
        .subtitle { text-align: center; font-weight: bold; }
        .section-title {
            background: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
        }
        .highlight { background: #e6f3ff; font-weight: bold; }
        .muted {
            color: #555;
            font-size: 11px;
        }
        .company-brand {
            display: table;
            width: 100%;
        }
        .company-brand-cell {
            display: table-cell;
            vertical-align: middle;
        }
        .company-brand-cell.logo {
            width: 74px;
        }
        .company-brand-name {
            font-size: 18px;
            font-weight: 700;
            line-height: 1.2;
        }
        .company-logo {
            max-height: 52px;
            max-width: 62px;
            display: block;
            margin-right: 10px;
        }
    </style>
</head>
<body>

<table class="no-border">
    <tr>
        <td width="30%">
            <div class="company-brand">
                @if(!empty($entreprise_logo_path))
                    <div class="company-brand-cell logo">
                        <img src="{{ $entreprise_logo_path }}" alt="Logo entreprise" class="company-logo">
                    </div>
                @endif
                <div class="company-brand-cell">
                    <div class="company-brand-name">{{ $entreprise_nom ?? config('app.name', 'Module RH') }}</div>
                </div>
            </div>
        </td>
        <td width="40%" class="center">
            <div class="title">REÇU DE PAIEMENT</div>
            <div class="subtitle">Fiche de paie {{ $paie->mois }}</div>
        </td>
        <td width="30%" class="right muted">
            Généré le {{ now()->format('d/m/Y H:i') }}
        </td>
    </tr>
</table>

<br>

<table>
    <tr class="section-title">
        <th colspan="4">Détails du paiement</th>
    </tr>
    <tr>
        <td class="bold">Référence</td>
        <td>#{{ $mouvement->id }}</td>
        <td class="bold">Source</td>
        <td>{{ $mouvement->source ?? '—' }}</td>
    </tr>
    <tr>
        <td class="bold">Caisse</td>
        <td>{{ $caisse->nom ?? '—' }}</td>
        <td class="bold">Validé le</td>
        <td>{{ optional($mouvement->valide_le)->format('d/m/Y H:i') ?? '—' }}</td>
    </tr>
    <tr>
        <td class="bold">Montant</td>
        <td colspan="3" class="highlight right">{{ number_format((float) $mouvement->montant, 0, ',', ' ') }} MGA</td>
    </tr>
</table>

<br>

<table>
    <tr class="section-title">
        <th>Matricule</th>
        <th>Employé</th>
        <th>Poste</th>
        <th>Statut</th>
    </tr>
    <tr>
        <td>{{ $employe->matricule ?? '—' }}</td>
        <td>{{ trim(($employe->nom ?? '') . ' ' . ($employe->prenom ?? '')) ?: '—' }}</td>
        <td>{{ $employe->poste->nom ?? '—' }}</td>
        <td>Validé</td>
    </tr>
</table>

<p class="muted" style="margin-top: 18px;">
    Ce reçu confirme la validation du paiement de la fiche de paie et la sortie correspondante de la caisse.
</p>

</body>
</html>
