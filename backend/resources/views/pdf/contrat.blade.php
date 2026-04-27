<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contrat de travail</title>
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
            <div class="title">CONTRAT DE TRAVAIL</div>
            <div class="subtitle">N° {{ $contrat->numero ?? '—' }}</div>
        </td>
        <td width="30%" class="right muted">
            Généré le {{ now()->format('d/m/Y') }}
        </td>
    </tr>
</table>

<br>

<table>
    <tr class="section-title">
        <th colspan="4">Informations du contrat</th>
    </tr>
    <tr>
        <td class="bold">Type</td>
        <td>{{ $contrat->type_contrat ?? '—' }}</td>
        <td class="bold">Renouvelable</td>
        <td>{{ $contrat->renouvelable ? 'Oui' : 'Non' }}</td>
    </tr>
    <tr>
        <td class="bold">Date de début</td>
        <td>{{ optional($contrat->date_debut)->format('d/m/Y') ?? '—' }}</td>
        <td class="bold">Date de fin</td>
        <td>{{ optional($contrat->date_fin)->format('d/m/Y') ?? '—' }}</td>
    </tr>
    <tr>
        <td class="bold">Début période d'essai</td>
        <td>{{ optional($contrat->periode_essai_debut)->format('d/m/Y') ?? '—' }}</td>
        <td class="bold">Fin période d'essai</td>
        <td>{{ optional($contrat->periode_essai_fin)->format('d/m/Y') ?? '—' }}</td>
    </tr>
    <tr>
        <td class="bold">Salaire de base</td>
        <td colspan="3">{{ number_format((float) $contrat->salaire_base, 0, ',', ' ') }} Ar</td>
    </tr>
</table>

<br>

<table>
    <tr class="section-title">
        <th>Matricule</th>
        <th>Nom complet</th>
        <th>Poste</th>
        <th>Département</th>
    </tr>
    <tr>
        <td>{{ $contrat->employe->matricule ?? '—' }}</td>
        <td>{{ trim(($contrat->employe->nom ?? '') . ' ' . ($contrat->employe->prenom ?? '')) ?: '—' }}</td>
        <td>{{ $contrat->employe->poste->nom ?? '—' }}</td>
        <td>{{ $contrat->employe->departement->nom ?? '—' }}</td>
    </tr>
</table>

<p class="muted" style="margin-top: 18px;">
    Document généré automatiquement par le module RH.
</p>

</body>
</html>
