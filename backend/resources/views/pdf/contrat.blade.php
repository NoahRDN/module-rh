<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 20px; margin-bottom: 6px; }
        .muted { color: #555; }
        .section-title { margin-top: 14px; font-weight: 700; border-left: 4px solid #10b981; padding-left: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        td, th { border: 1px solid #ddd; padding: 6px 8px; }
        th { background: #f6f6f6; text-align: left; }
        .grid { display: table; width: 100%; }
        .row { display: table-row; }
        .cell { display: table-cell; padding: 4px 0; }
    </style>
</head>
<body>
    <h1>Contrat de travail</h1>
    <p class="muted">Numéro : {{ $contrat->numero ?? '—' }} · Type : {{ $contrat->type_contrat }}</p>

    <div class="section-title">Employé</div>
    <table>
        <tr>
            <th>Matricule</th>
            <th>Nom complet</th>
            <th>Poste</th>
            <th>Département</th>
        </tr>
        <tr>
            <td>{{ $contrat->employe->matricule ?? '—' }}</td>
            <td>{{ $contrat->employe->nom ?? '' }} {{ $contrat->employe->prenom ?? '' }}</td>
            <td>{{ $contrat->employe->poste->nom ?? '—' }}</td>
            <td>{{ $contrat->employe->departement->nom ?? '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Dates</div>
    <table>
        <tr>
            <th>Début contrat</th>
            <th>Fin contrat</th>
            <th>Début période d'essai</th>
            <th>Fin période d'essai</th>
            <th>Renouvelable</th>
        </tr>
        <tr>
            <td>{{ optional($contrat->date_debut)->format('Y-m-d') }}</td>
            <td>{{ optional($contrat->date_fin)->format('Y-m-d') }}</td>
            <td>{{ optional($contrat->periode_essai_debut)->format('Y-m-d') ?? '—' }}</td>
            <td>{{ optional($contrat->periode_essai_fin)->format('Y-m-d') ?? '—' }}</td>
            <td>{{ $contrat->renouvelable ? 'Oui' : 'Non' }}</td>
        </tr>
    </table>

    <div class="section-title">Rémunération</div>
    <table>
        <tr>
            <th>Salaire de base</th>
            <th>Date d'émission</th>
        </tr>
        <tr>
            <td>{{ number_format($contrat->salaire_base, 0, ',', ' ') }} Ar</td>
            <td>{{ now()->format('Y-m-d') }}</td>
        </tr>
    </table>

    <p class="muted" style="margin-top:18px;">Document généré automatiquement par le module RH.</p>
</body>
</html>
