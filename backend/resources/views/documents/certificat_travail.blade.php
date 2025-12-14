<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Certificat de Travail</title>
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
        .postes-list {
            margin: 20px 0;
            padding: 15px;
            background-color: #fefce8;
            border-left: 4px solid #eab308;
        }
        .postes-list h4 {
            margin: 0 0 10px 0;
        }
        .postes-list ul {
            margin: 0;
            padding-left: 20px;
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
        .mention-legale {
            margin-top: 30px;
            padding: 10px;
            background-color: #f0fdf4;
            border: 1px solid #22c55e;
            font-size: 10pt;
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
        <strong>Référence:</strong> {{ $numero_certificat }}<br>
        <strong>Date:</strong> {{ $date_generation }}
    </div>

    <h1 class="title">Certificat de Travail</h1>

    <div class="content">
        <p>
            Je soussigné(e), <strong>Directeur des Ressources Humaines</strong> de la société 
            <strong>ENTREPRISE XYZ</strong>, certifie par la présente que :
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
                    <td class="label">Date de naissance :</td>
                    <td>{{ $employe->date_naissance ? $employe->date_naissance->format('d/m/Y') : 'Non défini' }}</td>
                </tr>
                <tr>
                    <td class="label">Période d'emploi :</td>
                    <td>Du {{ $date_debut }} au {{ $date_fin }}</td>
                </tr>
                <tr>
                    <td class="label">Dernier poste occupé :</td>
                    <td>{{ $dernier_poste }}</td>
                </tr>
            </table>
        </div>

        <p>
            A été employé(e) au sein de notre entreprise du <strong>{{ $date_debut }}</strong> 
            au <strong>{{ $date_fin }}</strong>.
        </p>

        @if($postes_occupes && $postes_occupes->count() > 0)
        <div class="postes-list">
            <h4>Postes occupés durant son parcours :</h4>
            <ul>
                @foreach($postes_occupes as $historique)
                <li>
                    {{ $historique->poste->nom ?? 'Poste inconnu' }} 
                    (depuis le {{ \Carbon\Carbon::parse($historique->date_changement)->format('d/m/Y') }})
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <p>
            {{ $employe->prenom }} {{ $employe->nom }} a fait preuve de sérieux et de professionnalisme 
            tout au long de son parcours au sein de notre entreprise.
        </p>

        <div class="mention-legale">
            <strong>Mention légale :</strong> En application de l'article L1234-19 du Code du travail, 
            ce certificat de travail est délivré à l'intéressé(e) à la fin de son contrat de travail.
        </div>
    </div>

    <div class="signature">
        <p>Fait à Antananarivo, le {{ $date_generation }}</p>
        <p>Le Directeur des Ressources Humaines</p>
        <div class="signature-line"></div>
    </div>

    <div class="footer">
        Document généré automatiquement le {{ $date_generation }} - Réf: {{ $numero_certificat }}
    </div>
</body>
</html>
