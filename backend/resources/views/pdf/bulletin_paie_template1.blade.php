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

    .header td {
        border: none;
    }

    .section-title {
        background: #f2f2f2;
        font-weight: bold;
        text-transform: uppercase;
    }

    .highlight {
        background: #00b0f0;
        color: #000;
        font-weight: bold;
    }

    .net {
        font-size: 14px;
        font-weight: bold;
    }

    .signature td {
        border: none;
        padding-top: 40px;
    }
</style>
</head>

<body>

<!-- ================= LOGO & TITRE ================= -->
<table class="header">
    <tr>
        <td width="30%">
            <strong>ITUniversity</strong><br>
            <span style="font-size:10px;">Education is Our Foundation</span>
        </td>
        <td width="40%" class="center">
            <div class="title">FICHE DE PAIE</div>
            <div class="subtitle">ARRÊTÉE AU 31/10/25</div>
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
                <tr><td>Nom et Prénoms :</td><td class="bold">RAZAFIARISON Laza</td></tr>
                <tr><td>Matricule :</td><td>627/TNR</td></tr>
                <tr><td>Fonction :</td><td>DRH</td></tr>
                <tr><td>N° CNaPS :</td><td>345670000</td></tr>
                <tr><td>Date d'embauche :</td><td class="highlight">3/25/2011</td></tr>
                <tr><td>Ancienneté :</td><td>14 an(s) 7 mois et 12 jour(s)</td></tr>
            </table>
        </td>
        <td width="50%">
            <table class="no-border">
                <tr><td>Classification :</td><td>HC</td></tr>
                <tr><td>Salaire de base :</td><td class="highlight right">7,800,000.00</td></tr>
                <tr><td>Taux journaliers :</td><td class="right">260,000.00</td></tr>
                <tr><td>Taux horaires :</td><td class="right">45,001.00</td></tr>
                <tr><td>Indice :</td><td class="right">33,734.00</td></tr>
            </table>
        </td>
    </tr>
</table>

<br>

<!-- ================= DESIGNATIONS ================= -->
<table>
    <tr class="section-title">
        <th>Désignations</th>
        <th width="12%">Nombre</th>
        <th width="18%">Taux</th>
        <th width="20%">Montant</th>
    </tr>

    <tr>
        <td>Salaire du 01/10/25 au 31/10/25</td>
        <td class="center">1 mois</td>
        <td class="right">260,000.00</td>
        <td class="right">7,800,000.00</td>
    </tr>

    <tr><td>Absences déductibles</td><td></td><td class="right">260,000.00</td><td></td></tr>
    <tr><td>Primes de rendement</td><td></td><td></td><td></td></tr>
    <tr><td>Primes d'ancienneté</td><td></td><td></td><td></td></tr>

    <tr><td>Heures supplémentaires majorées de 30%</td><td></td><td></td><td class="right">58,501.00</td></tr>
    <tr><td>Heures supplémentaires majorées de 40%</td><td></td><td></td><td class="right">63,001.00</td></tr>
    <tr><td>Heures supplémentaires majorées de 50%</td><td></td><td></td><td class="right">67,502.00</td></tr>
    <tr><td>Heures supplémentaires majorées de 100%</td><td></td><td></td><td class="right">90,002.00</td></tr>
    <tr><td>Majoration pour heures de nuit</td><td></td><td></td><td class="right">13,500.00</td></tr>

    <tr><td>Primes diverses</td><td></td><td></td><td></td></tr>
    <tr><td>Rappels sur période antérieure</td><td></td><td></td><td></td></tr>
    <tr><td>Droits de congés</td><td></td><td></td><td class="right">260,000.00</td></tr>
    <tr><td>Droits de préavis</td><td></td><td></td><td class="right">260,000.00</td></tr>
    <tr><td>Indemnités de licenciement</td><td></td><td></td><td class="right">260,000.00</td></tr>

    <tr class="bold">
        <td colspan="3" class="right">Salaire brut</td>
        <td class="right">7,800,000.00</td>
    </tr>
</table>

<br>

<!-- ================= RETENUES ================= -->
<table>
    <tr>
        <td colspan="3" class="right">Retenue CNaPS 1%</td>
        <td class="right">28,000.00</td>
    </tr>
    <tr>
        <td colspan="3" class="right">Retenue sanitaire</td>
        <td class="right">78,000.00</td>
    </tr>
    <tr>
        <td colspan="3" class="right">Tranche IRSA INF 350 000</td>
        <td class="right"></td>
    </tr>
    <tr>
        <td colspan="2" class="right">Tranche IRSA DE 350 001 à 400 000</td>
        <td class="center">5%</td>
        <td class="right">2,500.00</td>
    </tr>
    <tr>
        <td colspan="2" class="right">Tranche IRSA DE 400 001 à 500 000</td>
        <td class="center">10%</td>
        <td class="right">10,000.00</td>
    </tr>
    <tr>
        <td colspan="2" class="right">Tranche IRSA DE 500 001 à 600 000</td>
        <td class="center">15%</td>
        <td class="right">15,000.00</td>
    </tr>
    <tr>
        <td colspan="2" class="right">Tranche IRSA DE 600 001 à 4 000 000</td>
        <td class="center">20%</td>
        <td class="right">20,000.00</td>
    </tr>
    <tr>
        <td colspan="2" class="right">Tranche IRSA PLUS DE 4 000 000</td>
        <td class="center">25%</td>
        <td class="right">923,500.00</td>
    </tr>

    <tr class="bold">
        <td colspan="3" class="right">TOTAL IRSA</td>
        <td class="right">971,000.00</td>
    </tr>

    <tr class="bold">
        <td colspan="3" class="right">Total des retenues</td>
        <td class="right">1,077,000.00</td>
    </tr>

    <tr class="bold net">
        <td colspan="3" class="right">Net à payer</td>
        <td class="right">6,723,000.00</td>
    </tr>
</table>

<br>

<!-- ================= FOOTER ================= -->
<table class="no-border">
    <tr>
        <td>Avantages en nature :</td>
        <td></td>
    </tr>
    <tr>
        <td>Déductions IRSA :</td>
        <td></td>
    </tr>
    <tr>
        <td>Montant imposable :</td>
        <td class="right">7,694,000.00</td>
    </tr>
</table>

<br>

<table class="no-border">
    <tr>
        <td>Mode de paiement :</td>
        <td class="bold">Virement / chèque</td>
    </tr>
</table>

<br><br>

<table class="signature">
    <tr>
        <td class="center">Le Responsable</td>
        <td class="center">L’employé(e)</td>
    </tr>
</table>

</body>
