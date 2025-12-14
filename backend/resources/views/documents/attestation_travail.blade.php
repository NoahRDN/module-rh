<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attestation de Travail</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #333;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #2563eb;
        }
        .title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin: 40px 0;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .reference {
            text-align: right;
            margin-bottom: 30px;
            font-size: 10pt;
            color: #666;
        }
        .content {
            text-align: justify;
            margin: 30px 0;
        }
        .content p {
            margin: 15px 0;
        }
        .employee-info {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .employee-info table {
            width: 100%;
        }
        .employee-info td {
            padding: 5px 10px;
        }
        .employee-info .label {
            font-weight: bold;
            width: 40%;
        }
        .signature {
            margin-top: 60px;
            text-align: right;
        }
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #333;
            width: 200px;
            display: inline-block;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .stamp {
            text-align: center;
            margin-top: 30px;
            font-style: italic;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">ENTREPRISE XYZ</div>
        <div>Adresse de l'entreprise</div>
        <div>Téléphone: +261 XX XX XXX XX | Email: contact@entreprise.com</div>
    </div>

    <div class="reference">
        <strong>Référence:</strong> {{ $numero_attestation }}<br>
        <strong>Date:</strong> {{ $date_generation }}
    </div>

    <h1 class="title">Attestation de Travail</h1>

    <div class="content">
        <p>
            Je soussigné(e), <strong>Directeur des Ressources Humaines</strong> de la société 
            <strong>ENTREPRISE XYZ</strong>, atteste par la présente que :
        </p>

        <div class="employee-info">
            <table>
                <tr>
                    <td class="label">Nom et Prénom :</td>
                    <td>{{ $employe->nom }} {{ $employe->prenom }}</td>
                </tr>
                <tr>
                    <td class="label">Matricule :</td>
                    <td>{{ $employe->matricule }}</td>
                </tr>
                <tr>
                    <td class="label">Poste occupé :</td>
                    <td>{{ $employe->poste->nom ?? 'Non défini' }}</td>
                </tr>
                <tr>
                    <td class="label">Département :</td>
                    <td>{{ $employe->departement->nom ?? 'Non défini' }}</td>
                </tr>
                <tr>
                    <td class="label">Date d'embauche :</td>
                    <td>{{ $employe->date_embauche ? $employe->date_embauche->format('d/m/Y') : 'Non défini' }}</td>
                </tr>
                <tr>
                    <td class="label">Ancienneté :</td>
                    <td>{{ $anciennete }}</td>
                </tr>
                @if($contrat)
                <tr>
                    <td class="label">Type de contrat :</td>
                    <td>{{ $contrat->type_contrat }}</td>
                </tr>
                @endif
            </table>
        </div>

        <p>
            Est bien employé(e) au sein de notre entreprise depuis le 
            <strong>{{ $employe->date_embauche ? $employe->date_embauche->format('d/m/Y') : 'N/A' }}</strong>
            et occupe actuellement le poste de <strong>{{ $employe->poste->nom ?? 'Non défini' }}</strong>.
        </p>

        <p>
            Cette attestation est délivrée à l'intéressé(e) pour servir et valoir ce que de droit.
        </p>
    </div>

    <div class="signature">
        <p>Fait à Antananarivo, le {{ $date_generation }}</p>
        <p>Le Directeur des Ressources Humaines</p>
        <div class="signature-line"></div>
    </div>

    <div class="stamp">
        <p>[Cachet de l'entreprise]</p>
    </div>

    <div class="footer">
        Document généré automatiquement le {{ $date_generation }} - Réf: {{ $numero_attestation }}
    </div>
</body>
</html>
