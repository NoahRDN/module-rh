<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Fiche de paie</title>

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
        padding: 4px 6px;
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
    .net { font-size: 14px; font-weight: bold; background: #d9ead3; }
    .signature td { border: none; padding-top: 40px; }
</style>
</head>

<body>

@php
    use Carbon\Carbon;

    // Sécurisation des dates
    $periode = $paie->mois
        ? Carbon::createFromFormat('Y-m', $paie->mois)->translatedFormat('F Y')
        : '—';

    $dateEmbauche = $employe->date_embauche
        ? Carbon::parse($employe->date_embauche)->format('d/m/Y')
        : '—';
@endphp

<!-- ================= ENTÊTE ================= -->
<table class="no-border">
<tr>
    <td width="30%"><strong>ITUniversity</strong></td>
    <td width="40%" class="center">
        <div class="title">FICHE DE PAIE</div>
        <div class="subtitle">Période : {{ $periode }}</div>
    </td>
    <td width="30%"></td>
</tr>
</table>

<br>

<!-- ================= IDENTITÉ ================= -->
<table class="no-border">
<tr>
<td width="50%">
    <table class="no-border">
        <tr><td>Nom et Prénoms :</td><td class="bold">{{ $employe->nom }} {{ $employe->prenom }}</td></tr>
        <tr><td>Matricule :</td><td>{{ $employe->matricule }}</td></tr>
        <tr><td>Fonction :</td><td>{{ $employe->poste->libelle ?? '—' }}</td></tr>
        <tr><td>Catégorie :</td><td>{{ $employe->categorie ?? '—' }}</td></tr>
        <tr><td>N° CNAPS :</td><td>{{ $employe->num_cnaps ?? '—' }}</td></tr>
        <tr><td>Date d'embauche :</td><td>{{ $dateEmbauche }}</td></tr>
        <tr><td>Ancienneté :</td><td>{{ $anciennete }}</td></tr>
    </table>
</td>

<td width="50%">
    <table class="no-border">
        <tr><td>Salaire de base :</td><td class="right highlight">{{ number_format($paie->salaire_base, 2, ',', ' ') }}</td></tr>
        <tr><td>Taux journalier :</td><td class="right">{{ number_format($taux_journalier, 2, ',', ' ') }}</td></tr>
        <tr><td>Taux horaire :</td><td class="right">{{ number_format($taux_horaire, 2, ',', ' ') }}</td></tr>
    </table>
</td>
</tr>
</table>

<br>

<!-- ================= REVENUS ================= -->
<table>
<tr class="section-title">
    <th>Désignation</th>
    <th width="15%">Nombre</th>
    <th width="20%">Taux</th>
    <th width="20%">Montant</th>
</tr>

<tr>
    <td>Salaire de base du mois</td>
    <td class="center">1 mois</td>
    <td class="right">—</td>
    <td class="right">{{ number_format($paie->salaire_base, 2, ',', ' ') }}</td>
</tr>

@forelse ($details_revenus as $detail)
<tr>
    <td>{{ $detail['libelle'] }}</td>
    <td class="center">{{ $detail['nombre'] ?? '—' }}</td>
    <td class="right">{{ $detail['taux'] ?? '—' }}</td>
    <td class="right">{{ number_format($detail['montant'], 2, ',', ' ') }}</td>
</tr>
@empty
<tr>
    <td colspan="4" class="center">Aucune donnée additionnelle</td>
</tr>
@endforelse

<tr class="bold">
    <td colspan="3" class="right">SALAIRE BRUT</td>
    <td class="right">{{ number_format($paie->total_brut, 2, ',', ' ') }}</td>
</tr>
</table>

<br>

<!-- ================= RETENUES ================= -->
<table>
<tr class="section-title">
    <th colspan="3">Retenues salariales</th>
    <th>Montant</th>
</tr>

<tr>
    <td colspan="3" class="right">CNAPS salarié</td>
    <td class="right">{{ number_format($paie->retenue_cnaps, 2, ',', ' ') }}</td>
</tr>

<tr>
    <td colspan="3" class="right">OSTIE salarié</td>
    <td class="right">{{ number_format($paie->retenue_ostie, 2, ',', ' ') }}</td>
</tr>
</table>

<br>

<!-- ================= DÉTAIL IRSA ================= -->
<table>
<tr class="section-title">
    <th colspan="4">Détail IRSA</th>
</tr>

<tr>
    <th>Tranche</th>
    <th class="center">Base</th>
    <th class="center">Taux</th>
    <th class="right">Montant</th>
</tr>

@forelse ($details_irsa as $ligne)
<tr>
    <td>{{ $ligne['libelle'] }}</td>
    <td class="center">{{ number_format($ligne['base'], 2, ',', ' ') }}</td>
    <td class="center">{{ $ligne['taux'] }}%</td>
    <td class="right">{{ number_format($ligne['montant'], 2, ',', ' ') }}</td>
</tr>
@empty
<tr>
    <td colspan="4" class="center">Aucun IRSA applicable</td>
</tr>
@endforelse

<tr class="bold">
    <td colspan="3" class="right">IRSA BRUT</td>
    <td class="right">{{ number_format($irsa_brut, 2, ',', ' ') }}</td>
</tr>

<tr>
    <td colspan="3" class="right">Réduction IRSA</td>
    <td class="right">{{ number_format($reduction_irsa, 2, ',', ' ') }}</td>
</tr>

<tr class="bold">
    <td colspan="3" class="right">IRSA NET</td>
    <td class="right">{{ number_format($irsa_brut - $reduction_irsa, 2, ',', ' ') }}</td>
</tr>
</table>

<br>

<!-- ================= TOTAL ================= -->
<table>
<tr class="bold">
    <td colspan="3" class="right">TOTAL DES RETENUES</td>
    <td class="right">{{ number_format($paie->total_retenues, 2, ',', ' ') }}</td>
</tr>

<tr class="net">
    <td colspan="3" class="right">NET À PAYER</td>
    <td class="right">{{ number_format($paie->net_a_payer, 2, ',', ' ') }}</td>
</tr>
</table>

<br>

<!-- ================= SIGNATURE ================= -->
<table class="signature">
<tr>
    <td class="center">L’Employeur</td>
    <td class="center">L’Employé(e)</td>
</tr>
</table>

</body>
</html>
