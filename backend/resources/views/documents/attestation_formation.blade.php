<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation de Formation</title>
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
            color: #1e40af;
        }
        .reference {
            text-align: right;
            margin-bottom: 20px;
            font-size: 10pt;
            color: #666;
        }
        .formation-box {
            background-color: #dbeafe;
            border: 2px solid #2563eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .formation-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
        }
        .participant-info {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 40%;
            padding: 5px 10px;
            font-weight: bold;
        }
        .info-value {
            display: table-cell;
            padding: 5px 10px;
        }
        .details-section {
            margin: 25px 0;
        }
        .details-section h3 {
            color: #2563eb;
            border-bottom: 1px solid #2563eb;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .competences-list {
            margin: 10px 0;
            padding-left: 20px;
        }
        .competences-list li {
            margin: 8px 0;
        }
        .validation-box {
            background-color: #dcfce7;
            border: 2px solid #22c55e;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .validation-box.success {
            background-color: #dcfce7;
            border-color: #22c55e;
        }
        .validation-box.partial {
            background-color: #fef9c3;
            border-color: #eab308;
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
            margin-top: 50px;
            border-top: 1px solid #333;
            width: 70%;
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
        .stamp-area {
            text-align: center;
            margin-top: 30px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        @include('documents.partials.company_header')
        <div>Département des Ressources Humaines</div>
        <div>Service Formation</div>
    </div>

    <div class="reference">
        <strong>Réf:</strong> ATT-FORM-{{ $formation->id }}-{{ date('Ymd') }}<br>
        <strong>Date:</strong> {{ $date_generation }}
    </div>

    <h1 class="title">🎓 Attestation de Formation</h1>

    <p style="text-align: center; margin: 20px 0;">
        Nous soussignés, Direction des Ressources Humaines de {{ $entreprise->nom ?? 'Module RH' }},<br>
        attestons par la présente que :
    </p>

    <div class="participant-info">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nom et Prénom :</div>
                <div class="info-value"><strong>{{ $employe->nom }} {{ $employe->prenom }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Matricule :</div>
                <div class="info-value">{{ $employe->matricule }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Fonction :</div>
                <div class="info-value">{{ $employe->poste ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Département :</div>
                <div class="info-value">{{ $employe->departement->nom ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <p style="text-align: center;">a suivi avec succès la formation suivante :</p>

    <div class="formation-box">
        <div class="formation-title">{{ $formation->titre }}</div>
        @if(isset($formation->description))
        <p style="margin: 10px 0; font-style: italic;">{{ $formation->description }}</p>
        @endif
    </div>

    <div class="details-section">
        <h3>📅 Informations sur la formation</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Date de début :</div>
                <div class="info-value">{{ $formation->date_debut->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date de fin :</div>
                <div class="info-value">{{ $formation->date_fin->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Durée :</div>
                <div class="info-value">{{ $formation->duree_heures ?? $formation->date_debut->diffInDays($formation->date_fin) + 1 }} {{ isset($formation->duree_heures) ? 'heures' : 'jours' }}</div>
            </div>
            @if(isset($formation->formateur))
            <div class="info-row">
                <div class="info-label">Formateur :</div>
                <div class="info-value">{{ $formation->formateur }}</div>
            </div>
            @endif
            @if(isset($formation->lieu))
            <div class="info-row">
                <div class="info-label">Lieu :</div>
                <div class="info-value">{{ $formation->lieu }}</div>
            </div>
            @endif
        </div>
    </div>

    @if(isset($competences_acquises) && count($competences_acquises) > 0)
    <div class="details-section">
        <h3>💡 Compétences acquises</h3>
        <ul class="competences-list">
            @foreach($competences_acquises as $competence)
            <li>{{ $competence }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="validation-box {{ $formation->statut === 'terminee' ? 'success' : 'partial' }}">
        <strong>
            @if($formation->statut === 'terminee')
            ✅ Formation validée avec succès
            @else
            📋 Formation suivie
            @endif
        </strong>
        @if(isset($note_evaluation))
        <br><br>
        Note d'évaluation : <strong>{{ $note_evaluation }}/20</strong>
        @endif
    </div>

    <p style="margin-top: 30px;">
        Cette attestation est délivrée pour servir et valoir ce que de droit.
    </p>

    <div class="signature-section">
        <div class="signature-box">
            <p><strong>Responsable Formation</strong></p>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <p><strong>Direction RH</strong></p>
            <div class="signature-line"></div>
        </div>
    </div>

    <div class="stamp-area">
        [Cachet de {{ $entreprise->nom ?? 'l’entreprise' }}]
    </div>

    <div class="footer">
        Attestation de formation - {{ $formation->titre }} - {{ $employe->nom }} {{ $employe->prenom }} - {{ $date_generation }}
    </div>
</body>
</html>
