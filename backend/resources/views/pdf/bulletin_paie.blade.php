<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            margin-top: 5px;
            margin-bottom: 15px;
        }
        .section-title {
            background: #f0f0f0;
            padding: 5px;
            font-weight: bold;
            margin-top: 10px;
            border-left: 4px solid #3490dc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        table th, table td {
            padding: 6px;
            border: 1px solid #ccc;
        }
        .totaux td {
            font-weight: bold;
            background: #fafafa;
        }
        .footer {
            margin-top: 40px;
            font-size: 11px;
            text-align: center;
            color: #777;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Entreprise XYZ</h2>
    <div class="title">Bulletin de Paie - {{ $paie->mois }}</div>
</div>

<div class="section-title">Informations Employé</div>
<table>
    <tr>
        <td><strong>Nom :</strong> {{ $employe->nom }} {{ $employe->prenom }}</td>
        <td><strong>Matricule :</strong> {{ $employe->matricule }}</td>
    </tr>
    <tr>
        <td><strong>Poste :</strong> {{ $employe->poste->nom ?? 'N/A' }}</td>
        <td><strong>Département :</strong> {{ $employe->departement->nom ?? 'N/A' }}</td>
    </tr>
</table>

<div class="section-title">Détails Paie</div>
<table>
    <tr>
        <th>Description</th>
        <th>Valeur</th>
    </tr>
    <tr>
        <td>Salaire de base</td>
        <td>{{ number_format($paie->salaire_base, 0, ',', ' ') }} Ar</td>
    </tr>
    <tr>
        <td>Heures travaillées</td>
        <td>{{ $paie->heures_travaillees }} h</td>
    </tr>
    <tr>
        <td>Heures supplémentaires</td>
        <td>{{ $paie->heures_supplementaires }} h</td>
    </tr>
    <tr>
        <td>Montant heures sup</td>
        <td>{{ number_format($paie->montant_hs, 0, ',', ' ') }} Ar</td>
    </tr>
    <tr>
        <td>Total retards (minutes)</td>
        <td>{{ $paie->details->sum('retard_minutes') }}</td>
    </tr>
    <tr>
        <td>Absences (jours)</td>
        <td>{{ $paie->details->where('absent', true)->count() }}</td>
    </tr>
</table>

@if ($primes->count() > 0)
<div class="section-title">Primes</div>
<table>
    @foreach ($primes as $prime)
    <tr>
        <td>{{ $prime->libelle }}</td>
        <td>{{ number_format($prime->montant, 0, ',', ' ') }} Ar</td>
    </tr>
    @endforeach
</table>
@endif

<div class="section-title">Retenues</div>
<table>
    <tr><td>CNAPS</td><td>{{ number_format($paie->retenue_cnaps, 0, ',', ' ') }} Ar</td></tr>
    <tr><td>OSTIE</td><td>{{ number_format($paie->retenue_ostie, 0, ',', ' ') }} Ar</td></tr>
    <tr><td>IRSA</td><td>{{ number_format($paie->retenue_irsa, 0, ',', ' ') }} Ar</td></tr>
    @php
        $tauxHoraire = $paie->salaire_base > 0 ? $paie->salaire_base / 173.33 : 0;
        $retardMinutes = $paie->details->sum('retard_minutes');
        $absences = $paie->details->where('absent', true)->count();
        $dedRetards = ($retardMinutes / 60) * $tauxHoraire;
        $dedAbs = $absences * ($paie->salaire_base / 30);
    @endphp
    <tr><td>Déduction retards</td><td>{{ number_format($dedRetards, 0, ',', ' ') }} Ar</td></tr>
    <tr><td>Déduction absences</td><td>{{ number_format($dedAbs, 0, ',', ' ') }} Ar</td></tr>
</table>

<div class="section-title">Synthèse</div>
<table class="totaux">
    <tr>
        <td>Total Brut</td>
        <td>{{ number_format($paie->total_brut, 0, ',', ' ') }} Ar</td>
    </tr>
    <tr>
        <td>Total Retenues</td>
        <td>{{ number_format($paie->total_retenues, 0, ',', ' ') }} Ar</td>
    </tr>
    <tr>
        <td>Net à Payer</td>
        <td>{{ number_format($paie->net_a_payer, 0, ',', ' ') }} Ar</td>
    </tr>
</table>

<div class="footer">
    Bulletin généré automatiquement par Module RH.
</div>

</body>
</html>
