<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation de Congé</title>
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
        .info-box {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .employee-info {
            background-color: #f8fafc;
        }
        .conge-info {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
        }
        table {
            width: 100%;
        }
        td {
            padding: 8px 10px;
        }
        .label {
            font-weight: bold;
            width: 40%;
        }
        .highlight {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            color: #16a34a;
            padding: 15px;
            background-color: #f0fdf4;
            border-radius: 5px;
            margin: 20px 0;
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
    </style>
</head>
<body>
    <div class="header">
        @include('documents.partials.company_header')
    </div>

    <div class="reference">
        <strong>Référence:</strong> {{ $numero_attestation }}<br>
        <strong>Date:</strong> {{ $date_generation }}
    </div>

    <h1 class="title">Attestation de Congé</h1>

    <div class="content">
        <p>
            Je soussigné(e), <strong>Directeur des Ressources Humaines</strong> de la société 
            <strong>{{ $entreprise->nom ?? 'Module RH' }}</strong>, atteste par la présente que :
        </p>

        <div class="info-box employee-info">
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
                <tr>
                    <td class="label">Département :</td>
                    <td>{{ $employe->departement->nom ?? 'Non défini' }}</td>
                </tr>
            </table>
        </div>

        <p>
            Bénéficie d'une période de congé dûment approuvée par la direction.
        </p>

        <div class="info-box conge-info">
            <table>
                <tr>
                    <td class="label">Type de congé :</td>
                    <td>{{ $type_conge }}</td>
                </tr>
                <tr>
                    <td class="label">Date de début :</td>
                    <td>{{ $date_debut }}</td>
                </tr>
                <tr>
                    <td class="label">Date de fin :</td>
                    <td>{{ $date_fin }}</td>
                </tr>
                <tr>
                    <td class="label">Nombre de jours :</td>
                    <td>{{ $jours }} jour(s)</td>
                </tr>
            </table>
        </div>

        <div class="highlight">
            Congé approuvé du {{ $date_debut }} au {{ $date_fin }}<br>
            ({{ $jours }} jour(s) ouvrable(s))
        </div>

        @if($demande->motif)
        <p>
            <strong>Motif :</strong> {{ $demande->motif }}
        </p>
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
        Document généré automatiquement le {{ $date_generation }} - Réf: {{ $numero_attestation }}
    </div>
</body>
</html>
