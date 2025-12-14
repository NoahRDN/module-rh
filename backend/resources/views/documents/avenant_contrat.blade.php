<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Avenant au Contrat</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
            margin: 40px;
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
        .reference {
            text-align: right;
            margin-bottom: 20px;
            font-size: 10pt;
            color: #666;
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
        }
        .modifications {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .modifications h4 {
            margin: 0 0 15px 0;
            color: #b45309;
        }
        .modification-item {
            margin: 10px 0;
            padding: 10px;
            background-color: white;
            border-radius: 3px;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">ENTREPRISE XYZ</div>
        <div>Adresse de l'entreprise</div>
    </div>

    <div class="reference">
        <strong>Réf. Avenant:</strong> {{ $numero_avenant }}<br>
        <strong>Contrat initial:</strong> {{ $contrat->numero }}<br>
        <strong>Date:</strong> {{ $date_generation }}
    </div>

    <h1 class="title">Avenant au Contrat de Travail</h1>

    <div class="parties">
        <p><strong>Entre les soussignés :</strong></p>
        <p>
            <strong>L'Employeur :</strong> ENTREPRISE XYZ, représentée par son Directeur Général,
            ci-après dénommée « L'Employeur »,
        </p>
        <p><strong>Et :</strong></p>
        <p>
            <strong>{{ $employe->nom }} {{ $employe->prenom }}</strong>, 
            matricule {{ $employe->matricule }},
            ci-après dénommé(e) « Le Salarié »,
        </p>
    </div>

    <div class="article">
        <div class="article-title">Préambule</div>
        <p>
            Le présent avenant modifie le contrat de travail référencé <strong>{{ $contrat->numero }}</strong>
            conclu le {{ $contrat->date_debut->format('d/m/Y') }}.
        </p>
        <p>
            Les parties ont convenu de modifier les conditions du contrat initial comme suit :
        </p>
    </div>

    <div class="modifications">
        <h4>📝 Modifications apportées</h4>
        
        @if(isset($modifications['motif']))
        <div class="modification-item">
            <strong>Motif de la modification :</strong><br>
            {{ $modifications['motif'] }}
        </div>
        @endif

        @if(isset($modifications['nouveau_poste']))
        <div class="modification-item">
            <strong>Changement de poste :</strong><br>
            Nouveau poste : {{ $modifications['nouveau_poste'] }}
        </div>
        @endif

        @if(isset($modifications['nouveau_salaire']))
        <div class="modification-item">
            <strong>Modification de la rémunération :</strong><br>
            Nouveau salaire : {{ number_format($modifications['nouveau_salaire'], 0, ',', ' ') }} Ariary
        </div>
        @endif

        @if(isset($modifications['nouveau_departement']))
        <div class="modification-item">
            <strong>Changement de département :</strong><br>
            Nouveau département : {{ $modifications['nouveau_departement'] }}
        </div>
        @endif

        @if(isset($modifications['autres']))
        <div class="modification-item">
            <strong>Autres modifications :</strong><br>
            {{ $modifications['autres'] }}
        </div>
        @endif
    </div>

    <div class="article">
        <div class="article-title">Article 1 - Date d'effet</div>
        <p>
            Les présentes modifications prendront effet à compter du 
            <strong>{{ $date_effet }}</strong>.
        </p>
    </div>

    <div class="article">
        <div class="article-title">Article 2 - Dispositions maintenues</div>
        <p>
            Toutes les autres dispositions du contrat initial non modifiées par le présent avenant 
            demeurent inchangées et restent en vigueur.
        </p>
    </div>

    <div class="article">
        <div class="article-title">Article 3 - Acceptation</div>
        <p>
            Le Salarié reconnaît avoir pris connaissance du présent avenant et l'accepte sans réserve.
        </p>
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
        Avenant {{ $numero_avenant }} au contrat {{ $contrat->numero }} - Généré le {{ $date_generation }}
    </div>
</body>
</html>
