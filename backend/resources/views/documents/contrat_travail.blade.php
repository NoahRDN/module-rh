<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contrat de Travail</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
            margin: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
        }
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #2563eb;
        }
        .title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 30px 0;
            text-transform: uppercase;
        }
        .subtitle {
            text-align: center;
            font-size: 12pt;
            margin-bottom: 20px;
        }
        .parties {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8fafc;
            border-radius: 5px;
        }
        .article {
            margin: 20px 0;
        }
        .article-title {
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .article-content {
            text-align: justify;
            padding-left: 20px;
        }
        table.info {
            width: 100%;
            margin: 10px 0;
        }
        table.info td {
            padding: 5px 10px;
        }
        table.info .label {
            font-weight: bold;
            width: 35%;
        }
        .signature-section {
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 20px;
        }
        .signature-line {
            margin-top: 60px;
            border-top: 1px solid #333;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .page-break {
            page-break-before: always;
        }
        .highlight {
            background-color: #fef3c7;
            padding: 2px 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        @include('documents.partials.company_header')
    </div>

    <h1 class="title">Contrat de Travail</h1>
    <p class="subtitle">{{ $contrat->type_contrat }}</p>

    <div class="parties">
        <p><strong>Entre les soussignés :</strong></p>
        <p>
            <strong>L'Employeur :</strong> {{ $entreprise->nom ?? 'Module RH' }}, représentée par son Directeur Général,
            dont le siège social est situé à Antananarivo, Madagascar.
        </p>
        <p>Ci-après dénommée « L'Employeur »,</p>
        <p><strong>D'une part,</strong></p>
        <br>
        <p><strong>Et :</strong></p>
        <p>
            <strong>{{ $employe->nom }} {{ $employe->prenom }}</strong><br>
            Né(e) le {{ $employe->date_naissance ? $employe->date_naissance->format('d/m/Y') : '___________' }}<br>
            Demeurant à {{ $employe->adresse ?? '___________' }}
        </p>
        <p>Ci-après dénommé(e) « Le Salarié »,</p>
        <p><strong>D'autre part,</strong></p>
    </div>

    <p><strong>Il a été convenu ce qui suit :</strong></p>

    <div class="article">
        <div class="article-title">Article 1 - Engagement</div>
        <div class="article-content">
            L'Employeur engage le Salarié qui accepte, dans les conditions définies ci-après.
            Le présent contrat est soumis aux dispositions du Code du Travail malgache et 
            aux conventions collectives applicables à l'entreprise.
        </div>
    </div>

    <div class="article">
        <div class="article-title">Article 2 - Fonctions</div>
        <div class="article-content">
            <p>Le Salarié exercera les fonctions de <strong class="highlight">{{ $poste->nom ?? 'Non défini' }}</strong>
            au sein du département <strong>{{ $departement->nom ?? 'Non défini' }}</strong>.</p>
            <p>Le Salarié s'engage à effectuer toutes les tâches inhérentes à ses fonctions ainsi que 
            celles qui pourraient lui être confiées par sa hiérarchie dans le cadre de ses compétences.</p>
        </div>
    </div>

    <div class="article">
        <div class="article-title">Article 3 - Durée du contrat</div>
        <div class="article-content">
            <table class="info">
                <tr>
                    <td class="label">Type de contrat :</td>
                    <td>{{ $contrat->type_contrat }}</td>
                </tr>
                <tr>
                    <td class="label">Date de début :</td>
                    <td>{{ $contrat->date_debut->format('d/m/Y') }}</td>
                </tr>
                @if($contrat->date_fin)
                <tr>
                    <td class="label">Date de fin :</td>
                    <td>{{ $contrat->date_fin->format('d/m/Y') }}</td>
                </tr>
                @endif
            </table>
            @if($contrat->type_contrat == 'CDI')
            <p>Le présent contrat est conclu pour une durée indéterminée.</p>
            @else
            <p>Le présent contrat est conclu pour une durée déterminée du 
            {{ $contrat->date_debut->format('d/m/Y') }} au {{ $contrat->date_fin ? $contrat->date_fin->format('d/m/Y') : '___________' }}.</p>
            @endif
        </div>
    </div>

    <div class="article">
        <div class="article-title">Article 4 - Période d'essai</div>
        <div class="article-content">
            @if($contrat->periode_essai_debut && $contrat->periode_essai_fin)
            <p>Le présent contrat est soumis à une période d'essai allant du 
            <strong>{{ $contrat->periode_essai_debut->format('d/m/Y') }}</strong> au 
            <strong>{{ $contrat->periode_essai_fin->format('d/m/Y') }}</strong>.</p>
            <p>Durant cette période, chacune des parties pourra mettre fin au contrat sans préavis ni indemnité.</p>
            @else
            <p>La période d'essai sera définie conformément aux dispositions légales et conventionnelles en vigueur.</p>
            @endif
        </div>
    </div>

    <div class="article">
        <div class="article-title">Article 5 - Rémunération</div>
        <div class="article-content">
            <p>En contrepartie de son travail, le Salarié percevra une rémunération mensuelle brute de :</p>
            <p style="text-align: center; font-size: 14pt; font-weight: bold; color: #16a34a;">
                {{ number_format($contrat->salaire_base, 0, ',', ' ') }} Ariary
            </p>
            <p style="text-align: center; font-style: italic;">
                ({{ $salaire_lettres }})
            </p>
            <p>Ce salaire sera versé mensuellement par virement bancaire ou tout autre moyen convenu entre les parties.</p>
        </div>
    </div>

    <div class="article">
        <div class="article-title">Article 6 - Lieu de travail</div>
        <div class="article-content">
            <p>Le Salarié exercera ses fonctions au siège de l'entreprise ou en tout autre lieu désigné par l'Employeur.</p>
        </div>
    </div>

    <div class="article">
        <div class="article-title">Article 7 - Durée du travail</div>
        <div class="article-content">
            <p>La durée hebdomadaire de travail est fixée à 40 heures, réparties du lundi au vendredi.</p>
            <p>Les heures supplémentaires éventuelles seront rémunérées conformément à la législation en vigueur.</p>
        </div>
    </div>

    <div class="article">
        <div class="article-title">Article 8 - Congés payés</div>
        <div class="article-content">
            <p>Le Salarié bénéficiera des congés payés conformément aux dispositions légales et conventionnelles, 
            soit 2,5 jours ouvrables par mois de travail effectif.</p>
        </div>
    </div>

    <div class="article">
        <div class="article-title">Article 9 - Obligations du Salarié</div>
        <div class="article-content">
            <p>Le Salarié s'engage à :</p>
            <ul>
                <li>Exécuter consciencieusement les tâches qui lui sont confiées</li>
                <li>Respecter le règlement intérieur de l'entreprise</li>
                <li>Observer une discrétion absolue sur les informations confidentielles</li>
                <li>Ne pas exercer d'activité concurrente pendant la durée du contrat</li>
            </ul>
        </div>
    </div>

    <div class="article">
        <div class="article-title">Article 10 - Rupture du contrat</div>
        <div class="article-content">
            <p>Le présent contrat pourra être rompu par l'une ou l'autre des parties dans le respect 
            des dispositions légales relatives au préavis et aux indemnités de licenciement.</p>
        </div>
    </div>

    <p style="margin-top: 30px;">
        <strong>Fait en deux exemplaires originaux à Antananarivo, le {{ $date_generation }}</strong>
    </p>

    <div class="signature-section">
        <div class="signature-box">
            <p><strong>L'Employeur</strong></p>
            <p>(Lu et approuvé)</p>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <p><strong>Le Salarié</strong></p>
            <p>(Lu et approuvé)</p>
            <div class="signature-line"></div>
            <p>{{ $employe->nom }} {{ $employe->prenom }}</p>
        </div>
    </div>

    <div class="footer">
        Contrat N° {{ $contrat->numero }} - Généré le {{ $date_generation }}
    </div>
</body>
</html>
