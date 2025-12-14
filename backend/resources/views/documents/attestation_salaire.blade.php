<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation de Salaire</title>
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
        .employee-info, .salary-info {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .salary-info {
            background-color: #eff6ff;
            border: 1px solid #3b82f6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td, th {
            padding: 8px 10px;
        }
        .label {
            font-weight: bold;
            width: 40%;
        }
        .paie-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        .paie-table th, .paie-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        .paie-table th {
            background-color: #2563eb;
            color: white;
        }
        .paie-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .moyenne {
            font-size: 14pt;
            font-weight: bold;
            color: #16a34a;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background-color: #f0fdf4;
            border-radius: 5px;
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
        .confidential {
            text-align: center;
            color: #dc2626;
            font-weight: bold;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">ENTREPRISE XYZ</div>
        <div>Adresse de l'entreprise</div>
        <div>Téléphone: +261 XX XX XXX XX | Email: contact@entreprise.com</div>
    </div>

    <div class="confidential">⚠️ DOCUMENT CONFIDENTIEL ⚠️</div>

    <div class="reference">
        <strong>Référence:</strong> {{ $numero_attestation }}<br>
        <strong>Date:</strong> {{ $date_generation }}
    </div>

    <h1 class="title">Attestation de Salaire</h1>

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
                    <td class="label">Poste :</td>
                    <td>{{ $employe->poste->nom ?? 'Non défini' }}</td>
                </tr>
                @if($contrat)
                <tr>
                    <td class="label">Type de contrat :</td>
                    <td>{{ $contrat->type_contrat }}</td>
                </tr>
                <tr>
                    <td class="label">Salaire de base :</td>
                    <td>{{ number_format($contrat->salaire_base, 0, ',', ' ') }} Ar</td>
                </tr>
                @endif
            </table>
        </div>

        <p>
            Perçoit une rémunération régulière au sein de notre entreprise. 
            Voici le détail des rémunérations sur la période <strong>{{ $periode }}</strong> :
        </p>

        @if($paies && $paies->count() > 0)
        <table class="paie-table">
            <thead>
                <tr>
                    <th>Mois</th>
                    <th>Salaire Brut</th>
                    <th>Retenues</th>
                    <th>Salaire Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach($paies as $paie)
                <tr>
                    <td>{{ $paie->mois }}</td>
                    <td>{{ number_format($paie->total_brut, 0, ',', ' ') }} Ar</td>
                    <td>{{ number_format($paie->total_retenues, 0, ',', ' ') }} Ar</td>
                    <td>{{ number_format($paie->net_a_payer, 0, ',', ' ') }} Ar</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="moyenne">
            Salaire Net Moyen : {{ $moyenne_salaire }} Ar
        </div>
        @else
        <p><em>Aucune fiche de paie disponible pour la période demandée.</em></p>
        @endif

        <p>
            Cette attestation est délivrée à l'intéressé(e) pour servir et valoir ce que de droit.
        </p>
    </div>

    <div class="signature">
        <p>Fait à Antananarivo, le {{ $date_generation }}</p>
        <p>Le Directeur des Ressources Humaines</p>
        <div class="signature-line"></div>
    </div>

    <div class="footer">
        Document confidentiel généré le {{ $date_generation }} - Réf: {{ $numero_attestation }}
    </div>
</body>
</html>
