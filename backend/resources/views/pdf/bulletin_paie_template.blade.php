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
        vertical-align: middle;
    }

    .no-border td {
        border: none;
    }

    .center { text-align: center; }
    .right { text-align: right; }
    .bold { font-weight: bold; }

    .title {
        font-size: 16px;
        font-weight: bold;
        text-align: center;
    }

    .subtitle {
        text-align: center;
        margin-bottom: 20px;
        font-weight: bold;
    }

    .section-title {
        background: #f2f2f2;
        font-weight: bold;
        text-transform: uppercase;
    }

    .highlight {
        background: #e6f3ff;
        font-weight: bold;
    }

    .net {
        font-size: 14px;
        font-weight: bold;
        background: #d9ead3;
    }

    .signature td {
        border: none;
        padding-top: 40px;
    }
</style>
</head>

<body>

<!-- ================= ENTÊTE ================= -->
<table class="no-border">
    <tr>
        <td width="30%">
            <strong>ITUniversity</strong><br>
            <span style="font-size:10px;">Education is Our Foundation</span>
        </td>
        <td width="40%" class="center">
            <div class="title">FICHE DE PAIE</div>
            <div class="subtitle">Période : Octobre 2025</div>
        </td>
        <td width="30%"></td>
    </tr>
</table>

<br>

<!-- ================= IDENTITÉ EMPLOYÉ ================= -->
<table class="no-border">
<tr>
<td width="50%">
    <table class="no-border">
        <tr><td>Nom et Prénoms :</td><td class="bold">RAZAFIARISON Laza</td></tr>
        <tr><td>Matricule :</td><td>627/TNR</td></tr>
        <tr><td>Fonction :</td><td>Directeur RH</td></tr>
        <tr><td>Catégorie :</td><td>HC</td></tr>
        <tr><td>N° CNAPS :</td><td>345670000</td></tr>
        <tr><td>Date d'embauche :</td><td>25/03/2011</td></tr>
        <tr><td>Ancienneté :</td><td>14 ans 7 mois</td></tr>
    </table>
</td>

<td width="50%">
    <table class="no-border">
        <tr><td>Salaire de base :</td><td class="right highlight">7,800,000.00</td></tr>
        <tr><td>Taux journalier :</td><td class="right">260,000.00</td></tr>
        <tr><td>Taux horaire :</td><td class="right">45,001.00</td></tr>
        <tr><td>Mode de paiement :</td><td>Virement bancaire</td></tr>
    </table>
</td>
</tr>
</table>

<br>

<!-- ================= REVENUS ================= -->
<table>
<tr class="section-title">
    <th>Désignation</th>
    <th width="15%">Quantité</th>
    <th width="20%">Taux</th>
    <th width="20%">Montant</th>
</tr>

<tr>
    <td>Salaire de base du mois</td>
    <td class="center">1 mois</td>
    <td class="right">7,800,000.00</td>
    <td class="right">7,800,000.00</td>
</tr>

<tr>
    <td>Heures supplémentaires (30%)</td>
    <td class="center">8 h</td>
    <td class="right">1.30</td>
    <td class="right">58,501.00</td>
</tr>

<tr>
    <td>Heures supplémentaires (50%)</td>
    <td class="center">4 h</td>
    <td class="right">1.50</td>
    <td class="right">67,502.00</td>
</tr>

<tr>
    <td>Majoration heures de nuit</td>
    <td class="center">—</td>
    <td class="right">—</td>
    <td class="right">13,500.00</td>
</tr>

<tr>
    <td>Prime d’ancienneté</td>
    <td class="center">—</td>
    <td class="right">—</td>
    <td class="right">0.00</td>
</tr>

<tr>
    <td>Prime de rendement</td>
    <td class="center">—</td>
    <td class="right">—</td>
    <td class="right">0.00</td>
</tr>

<tr>
    <td>Avantage en nature (logement)</td>
    <td class="center">—</td>
    <td class="right">—</td>
    <td class="right">150,000.00</td>
</tr>

<tr class="bold">
    <td colspan="3" class="right">SALAIRE BRUT</td>
    <td class="right">8,089,503.00</td>
</tr>
</table>

<br>

<!-- ================= RETENUES ================= -->
<table>
<tr class="section-title">
    <th colspan="3">Retenues salariales</th>
    <th width="20%">Montant</th>
</tr>

<tr>
    <td colspan="3" class="right">CNAPS salarié (1% plafonné)</td>
    <td class="right">5,680.00</td>
</tr>

<tr>
    <td colspan="3" class="right">OSTIE salarié (1%)</td>
    <td class="right">7,800.00</td>
</tr>

<tr>
    <td colspan="3" class="right">IRSA (IGR net)</td>
    <td class="right">971,000.00</td>
</tr>

<tr class="bold">
    <td colspan="3" class="right">TOTAL RETENUES</td>
    <td class="right">984,480.00</td>
</tr>

<tr class="net">
    <td colspan="3" class="right">NET À PAYER</td>
    <td class="right">7,105,023.00</td>
</tr>
</table>

<br>

<!-- ================= INFORMATIONS FISCALES ================= -->
<table class="no-border">
<tr><td>Revenu imposable :</td><td class="right">7,939,503.00</td></tr>
<tr><td>Enfants à charge :</td><td class="right">2</td></tr>
<tr><td>IRSA brut :</td><td class="right">973,000.00</td></tr>
<tr><td>Réduction IRSA :</td><td class="right">2,000.00</td></tr>
<tr class="bold"><td>IGR net :</td><td class="right">971,000.00</td></tr>
</table>

<br><br>

<!-- ================= SIGNATURE ================= -->
<table class="signature">
<tr>
    <td class="center">L’Employeur</td>
    <td class="center">L’Employé(e)</td>
</tr>
</table>

</body>
</html>
