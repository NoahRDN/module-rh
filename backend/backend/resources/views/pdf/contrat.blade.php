<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        h2 { margin: 0 0 10px; }
        .section { margin-top: 12px; }
        .section h3 { margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table th, table td { padding: 6px; border: 1px solid #ccc; }
    </style>
</head>
<body>
<h2>Contrat {{ $contrat->numero ?? $contrat->id }}</h2>
<div class="section">
    <h3>Employé</h3>
    <table>
        <tr><td>Nom</td><td>{{ $contrat->employe->nom ?? '' }} {{ $contrat->employe->prenom ?? '' }}</td></tr>
        <tr><td>Matricule</td><td>{{ $contrat->employe->matricule ?? '' }}</td></tr>
        <tr><td>Poste</td><td>{{ $contrat->employe->poste->nom ?? 'N/A' }}</td></tr>
        <tr><td>Département</td><td>{{ $contrat->employe->departement->nom ?? 'N/A' }}</td></tr>
    </table>
</div>

<div class="section">
    <h3>Détails contrat</h3>
    <table>
        <tr><td>Type</td><td>{{ $contrat->type_contrat }}</td></tr>
        <tr><td>Date début</td><td>{{ $contrat->date_debut }}</td></tr>
        <tr><td>Date fin</td><td>{{ $contrat->date_fin ?? '—' }}</td></tr>
        <tr><td>Période d'essai</td><td>{{ $contrat->periode_essai_debut ?? '—' }} → {{ $contrat->periode_essai_fin ?? '—' }}</td></tr>
        <tr><td>Salaire de base</td><td>{{ number_format($contrat->salaire_base, 0, ',', ' ') }} Ar</td></tr>
        <tr><td>Renouvelable</td><td>{{ $contrat->renouvelable ? 'Oui' : 'Non' }}</td></tr>
    </table>
</div>
</body>
</html>
