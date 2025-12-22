<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { margin: 0 0 8px; font-size: 20px; }
        h3 { margin: 16px 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        td, th { border: 1px solid #ddd; padding: 6px 8px; }
        th { background: #f5f5f5; text-align: left; }
        .muted { color: #666; }
        .section { margin-top: 12px; }
    </style>
</head>
<body>
    <h1>Fiche employé</h1>
    <p class="muted">Générée automatiquement</p>

    <div class="section">
        <h3>Identité</h3>
        <table>
            <tr><th>Matricule</th><td>{{ $employe->matricule }}</td></tr>
            <tr><th>Nom complet</th><td>{{ $employe->nom }} {{ $employe->prenom }}</td></tr>
            <tr><th>Email</th><td>{{ $employe->email }}</td></tr>
            <tr><th>Téléphone</th><td>{{ $employe->telephone ?? '—' }}</td></tr>
            <tr><th>Adresse</th><td>{{ $employe->adresse ?? '—' }}</td></tr>
            <tr><th>Date de naissance</th><td>{{ optional($employe->date_naissance)->format('Y-m-d') }}</td></tr>
            <tr><th>Date d'embauche</th><td>{{ optional($employe->date_embauche)->format('Y-m-d') }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>Poste & département</h3>
        <table>
            <tr><th>Poste</th><td>{{ $employe->poste->nom ?? '—' }}</td></tr>
            <tr><th>Catégorie</th><td>{{ $employe->poste->categorie ?? '—' }}</td></tr>
            <tr><th>Département</th><td>{{ $employe->departement->nom ?? '—' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h3>Contrat actuel</h3>
        @if($contratActuel)
        <table>
            <tr><th>Numéro</th><td>{{ $contratActuel->numero ?? '—' }}</td></tr>
            <tr><th>Type</th><td>{{ $contratActuel->type_contrat }}</td></tr>
            <tr><th>Contrat</th><td>{{ optional($contratActuel->date_debut)->format('Y-m-d') }} → {{ optional($contratActuel->date_fin)->format('Y-m-d') }}</td></tr>
            <tr><th>Période d'essai</th><td>{{ optional($contratActuel->periode_essai_debut)->format('Y-m-d') ?? '—' }} → {{ optional($contratActuel->periode_essai_fin)->format('Y-m-d') ?? '—' }}</td></tr>
            <tr><th>Salaire base</th><td>{{ number_format($contratActuel->salaire_base, 0, ',', ' ') }} Ar</td></tr>
            <tr><th>Statut</th><td>{{ $contratActuel->date_fin && $contratActuel->date_fin->lt(now()) ? 'Inactif' : 'Actif' }}</td></tr>
        </table>
        @else
            <p class="muted">Aucun contrat enregistré.</p>
        @endif
    </div>

    <div class="section">
        <h3>Historique des postes (3 derniers)</h3>
        <table>
            <thead>
                <tr><th>Date</th><th>Poste</th><th>Département</th><th>Motif</th></tr>
            </thead>
            <tbody>
                @forelse($employe->historiquePostes->take(3) as $h)
                    <tr>
                        <td>{{ optional($h->date_changement)->format('Y-m-d') }}</td>
                        <td>{{ $h->poste->nom ?? '—' }}</td>
                        <td>{{ $h->departement->nom ?? '—' }}</td>
                        <td>{{ $h->motif ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">Aucun historique</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
