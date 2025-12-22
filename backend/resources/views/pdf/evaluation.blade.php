<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport d'évaluation - {{ $evaluation->employe->nom }} {{ $evaluation->employe->prenom }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #22c55e;
        }
        .header h1 {
            font-size: 24px;
            color: #1a1a1a;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 14px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #22c55e;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e5e5;
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
            width: 150px;
            font-weight: bold;
            padding: 5px 10px 5px 0;
            color: #666;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
        }
        .score-container {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f8f8;
            border-radius: 10px;
        }
        .score-global {
            font-size: 48px;
            font-weight: bold;
            color: #22c55e;
        }
        .score-label {
            font-size: 16px;
            color: #666;
            margin-top: 5px;
        }
        .niveau-performance {
            display: inline-block;
            padding: 5px 15px;
            background: #22c55e;
            color: white;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 10px;
        }
        .criteres-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .criteres-table th,
        .criteres-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e5e5;
        }
        .criteres-table th {
            background: #f5f5f5;
            font-weight: bold;
            color: #333;
        }
        .criteres-table tr:last-child td {
            border-bottom: none;
        }
        .note-cell {
            text-align: center;
            font-weight: bold;
        }
        .note-excellent { color: #22c55e; }
        .note-bien { color: #3b82f6; }
        .note-moyen { color: #f59e0b; }
        .note-faible { color: #ef4444; }
        .progress-bar {
            width: 100px;
            height: 8px;
            background: #e5e5e5;
            border-radius: 4px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
            margin-left: 10px;
        }
        .progress-fill {
            height: 100%;
            background: #22c55e;
            border-radius: 4px;
        }
        .commentary-box {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .commentary-box p {
            margin: 0;
            color: #444;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            text-align: center;
            color: #999;
            font-size: 10px;
        }
        .signature-section {
            margin-top: 40px;
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
            border-top: 1px solid #333;
            width: 200px;
            margin: 50px auto 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport d'Évaluation de Performance</h1>
        <p>Période : {{ \Carbon\Carbon::createFromFormat('Y-m', $evaluation->periode)->translatedFormat('F Y') }}</p>
    </div>

    <div class="section">
        <div class="section-title">Informations de l'employé</div>
        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Nom complet :</span>
                <span class="info-value">{{ $evaluation->employe->nom }} {{ $evaluation->employe->prenom }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Matricule :</span>
                <span class="info-value">{{ $evaluation->employe->matricule }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Poste :</span>
                <span class="info-value">{{ $evaluation->employe->poste?->nom ?? 'Non défini' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Département :</span>
                <span class="info-value">{{ $evaluation->employe->departement?->nom ?? 'Non défini' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Date d'évaluation :</span>
                <span class="info-value">{{ $evaluation->date_evaluation->format('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Évaluateur :</span>
                <span class="info-value">{{ $evaluation->evaluateur?->name ?? 'Non renseigné' }}</span>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="score-container">
            <div class="score-global">{{ number_format($evaluation->score_global, 1) }}%</div>
            <div class="score-label">Score Global</div>
            <div class="niveau-performance">{{ $evaluation->niveau_performance }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Détail par critère</div>
        <table class="criteres-table">
            <thead>
                <tr>
                    <th>Critère</th>
                    <th>Poids</th>
                    <th style="text-align: center;">Note</th>
                    <th>Commentaire</th>
                </tr>
            </thead>
            <tbody>
                @foreach($evaluation->details as $detail)
                <tr>
                    <td>{{ $detail->critere->libelle }}</td>
                    <td>{{ $detail->critere->poids }}%</td>
                    <td class="note-cell">
                        <span class="@if($detail->note >= 75) note-excellent @elseif($detail->note >= 60) note-bien @elseif($detail->note >= 50) note-moyen @else note-faible @endif">
                            {{ number_format($detail->note, 1) }}%
                        </span>
                    </td>
                    <td>{{ $detail->commentaire ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($evaluation->points_forts)
    <div class="section">
        <div class="section-title">Points forts</div>
        <div class="commentary-box">
            <p>{{ $evaluation->points_forts }}</p>
        </div>
    </div>
    @endif

    @if($evaluation->axes_amelioration)
    <div class="section">
        <div class="section-title">Axes d'amélioration</div>
        <div class="commentary-box">
            <p>{{ $evaluation->axes_amelioration }}</p>
        </div>
    </div>
    @endif

    @if($evaluation->objectifs)
    <div class="section">
        <div class="section-title">Objectifs pour la prochaine période</div>
        <div class="commentary-box">
            <p>{{ $evaluation->objectifs }}</p>
        </div>
    </div>
    @endif

    @if($evaluation->commentaire_general)
    <div class="section">
        <div class="section-title">Commentaire général</div>
        <div class="commentary-box">
            <p>{{ $evaluation->commentaire_general }}</p>
        </div>
    </div>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <p>Signature de l'évaluateur</p>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <p>Signature de l'employé</p>
        </div>
    </div>

    <div class="footer">
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }} — HR Core Module RH</p>
    </div>
</body>
</html>
