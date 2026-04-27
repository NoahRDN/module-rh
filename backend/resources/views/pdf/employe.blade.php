<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche employe</title>
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
            padding: 6px 8px;
        }
        .no-border td { border: none; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .title { font-size: 16px; font-weight: bold; text-align: center; }
        .subtitle { text-align: center; font-weight: bold; }
        .section-title {
            background: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
        }
        .muted {
            color: #555;
            font-size: 11px;
        }
        .company-brand {
            display: table;
            width: 100%;
        }
        .company-brand-cell {
            display: table-cell;
            vertical-align: middle;
        }
        .company-brand-cell.logo {
            width: 74px;
        }
        .company-brand-name {
            font-size: 18px;
            font-weight: 700;
            line-height: 1.2;
        }
        .company-logo {
            max-height: 52px;
            max-width: 62px;
            display: block;
            margin-right: 10px;
        }
    </style>
</head>
<body>

<table class="no-border">
    <tr>
        <td width="30%">
            <div class="company-brand">
                @if(!empty($entreprise_logo_path))
                    <div class="company-brand-cell logo">
                        <img src="{{ $entreprise_logo_path }}" alt="Logo entreprise" class="company-logo">
                    </div>
                @endif
                <div class="company-brand-cell">
                    <div class="company-brand-name">{{ $entreprise_nom ?? config('app.name', 'Module RH') }}</div>
                </div>
            </div>
        </td>
        <td width="40%" class="center">
            <div class="title">FICHE EMPLOYE</div>
            <div class="subtitle">Dossier collaborateur</div>
        </td>
        <td width="30%" class="right muted">
            Genere le {{ now()->format('d/m/Y') }}
        </td>
    </tr>
</table>

<br>

<table>
    <tr class="section-title">
        <th colspan="4">Identite</th>
    </tr>
    <tr>
        <td class="bold">Matricule</td>
        <td>{{ $employe->matricule ?? '—' }}</td>
        <td class="bold">Nom complet</td>
        <td>{{ trim(($employe->nom ?? '') . ' ' . ($employe->prenom ?? '')) ?: '—' }}</td>
    </tr>
    <tr>
        <td class="bold">Email</td>
        <td>{{ $employe->email ?? '—' }}</td>
        <td class="bold">Telephone</td>
        <td>{{ $employe->telephone ?? '—' }}</td>
    </tr>
    <tr>
        <td class="bold">Adresse</td>
        <td>{{ $employe->adresse ?? '—' }}</td>
        <td class="bold">Date de naissance</td>
        <td>{{ optional($employe->date_naissance)->format('d/m/Y') ?? '—' }}</td>
    </tr>
    <tr>
        <td class="bold">Date d'embauche</td>
        <td>{{ optional($employe->date_embauche)->format('d/m/Y') ?? '—' }}</td>
        <td class="bold">Departement</td>
        <td>{{ $employe->departement->nom ?? '—' }}</td>
    </tr>
</table>

<br>

<table>
    <tr class="section-title">
        <th colspan="4">Poste actuel</th>
    </tr>
    <tr>
        <td class="bold">Poste</td>
        <td>{{ $employe->poste->nom ?? '—' }}</td>
        <td class="bold">Categorie</td>
        <td>{{ $employe->poste->categorie ?? '—' }}</td>
    </tr>
</table>

<br>

<table>
    <tr class="section-title">
        <th colspan="4">Contrat actuel</th>
    </tr>
    @if($contratActuel)
    <tr>
        <td class="bold">Numero</td>
        <td>{{ $contratActuel->numero ?? '—' }}</td>
        <td class="bold">Type</td>
        <td>{{ $contratActuel->type_contrat ?? '—' }}</td>
    </tr>
    <tr>
        <td class="bold">Periode contrat</td>
        <td>{{ optional($contratActuel->date_debut)->format('d/m/Y') ?? '—' }} -> {{ optional($contratActuel->date_fin)->format('d/m/Y') ?? '—' }}</td>
        <td class="bold">Periode d'essai</td>
        <td>{{ optional($contratActuel->periode_essai_debut)->format('d/m/Y') ?? '—' }} -> {{ optional($contratActuel->periode_essai_fin)->format('d/m/Y') ?? '—' }}</td>
    </tr>
    <tr>
        <td class="bold">Salaire de base</td>
        <td>{{ number_format((float) $contratActuel->salaire_base, 0, ',', ' ') }} Ar</td>
        <td class="bold">Statut</td>
        <td>{{ $contratActuel->date_fin && $contratActuel->date_fin->lt(now()) ? 'Inactif' : 'Actif' }}</td>
    </tr>
    @else
    <tr>
        <td colspan="4" class="center muted">Aucun contrat enregistre.</td>
    </tr>
    @endif
</table>

<br>

<table>
    <tr class="section-title">
        <th>Date</th>
        <th>Poste</th>
        <th>Departement</th>
        <th>Motif</th>
    </tr>
    @forelse($employe->historiquePostes->take(3) as $h)
        <tr>
            <td>{{ optional($h->date_changement)->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $h->poste->nom ?? '—' }}</td>
            <td>{{ $h->departement->nom ?? '—' }}</td>
            <td>{{ $h->motif ?? '—' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="4" class="center muted">Aucun historique.</td>
        </tr>
    @endforelse
</table>

</body>
</html>
