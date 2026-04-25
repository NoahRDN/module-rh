<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Lettre de Recommandation</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.8;
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
        }
        .destinataire {
            margin-bottom: 30px;
        }
        .content {
            text-align: justify;
            margin: 30px 0;
        }
        .content p {
            margin: 20px 0;
            text-indent: 40px;
        }
        .competences {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .competences h4 {
            margin: 0 0 10px 0;
            color: #2563eb;
        }
        .competences ul {
            margin: 0;
            padding-left: 20px;
        }
        .competences li {
            margin: 5px 0;
        }
        .note {
            display: inline-block;
            background-color: #16a34a;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10pt;
        }
        .evaluation {
            text-align: center;
            margin: 30px 0;
            padding: 15px;
            background-color: #eff6ff;
            border-radius: 5px;
        }
        .evaluation .score {
            font-size: 24pt;
            font-weight: bold;
            color: #2563eb;
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

    <div class="destinataire">
        <strong>{{ $destinataire }}</strong>
    </div>

    <h1 class="title">Lettre de Recommandation</h1>

    <div class="content">
        <p>
            J'ai le plaisir de recommander <strong>{{ $employe->prenom }} {{ $employe->nom }}</strong>, 
            qui a travaillé au sein de notre entreprise pendant <strong>{{ $duree_emploi }}</strong> 
            en qualité de <strong>{{ $employe->poste->nom ?? 'collaborateur' }}</strong>.
        </p>

        <p>
            Durant cette période, {{ $employe->prenom }} a fait preuve d'un grand professionnalisme 
            et d'une réelle implication dans ses missions. Sa rigueur, son sens des responsabilités 
            et sa capacité à travailler en équipe ont été particulièrement appréciés par l'ensemble 
            de ses collègues et sa hiérarchie.
        </p>

        @if($competences_principales && $competences_principales->count() > 0)
        <div class="competences">
            <h4>Compétences clés développées :</h4>
            <ul>
                @foreach($competences_principales as $competence)
                <li>
                    {{ $competence->nom }} 
                    <span class="note">Niveau {{ $competence->pivot->niveau }}/5</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($evaluations && $evaluations->count() > 0 && $moyenne_note > 0)
        <div class="evaluation">
            <p>Performance moyenne sur les dernières évaluations :</p>
            <div class="score">{{ $moyenne_note }}/5</div>
        </div>
        @endif

        <p>
            {{ $employe->prenom }} possède d'excellentes qualités relationnelles et s'adapte 
            rapidement à de nouveaux environnements de travail. Sa motivation et son dynamisme 
            sont des atouts indéniables pour toute organisation qui souhaiterait l'accueillir.
        </p>

        <p>
            C'est donc sans réserve que je recommande {{ $employe->prenom }} {{ $employe->nom }} 
            pour tout poste correspondant à ses compétences et aspirations professionnelles. 
            Je suis convaincu(e) qu'il/elle saura apporter une réelle valeur ajoutée à votre équipe.
        </p>

        <p>
            Je reste à votre disposition pour tout renseignement complémentaire.
        </p>
    </div>

    <div class="signature">
        <p>Fait à Antananarivo, le {{ $date_generation }}</p>
        <p>Cordialement,</p>
        <p><strong>Le Directeur des Ressources Humaines</strong></p>
        <div class="signature-line"></div>
    </div>

    <div class="footer">
        Lettre de recommandation - {{ $employe->nom }} {{ $employe->prenom }} - {{ $date_generation }}
    </div>
</body>
</html>
