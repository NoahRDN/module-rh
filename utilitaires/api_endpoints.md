# Endpoints API — Module RH

## Auth
- `POST /api/login` — connexion (identifiant/mdp)
- `POST /api/logout` — déconnexion (auth:sanctum)
- `GET /api/me` — profil connecté (auth:sanctum)

## Routes héritées (hors /v1)
- `GET /api/employes` — liste employés (legacy)
- `GET /api/employes/{id}/fiche_employe` — fiche employé (legacy)
- `GET /api/employes/{id}/historique_poste` — historique poste (legacy)

## /api/v1 — publiques
- `GET /api/v1/departements`
- `POST /api/v1/departements`
- `GET /api/v1/departements/{id}`
- `PUT /api/v1/departements/{id}`
- `DELETE /api/v1/departements/{id}`
- `GET /api/v1/postes`
- `POST /api/v1/postes`
- `GET /api/v1/postes/{id}`
- `PUT /api/v1/postes/{id}`
- `DELETE /api/v1/postes/{id}`
- `GET /api/v1/historiques-postes`
- `POST /api/v1/historiques-postes`
- `GET /api/v1/historiques-postes/{id}`
- `DELETE /api/v1/historiques-postes/{id}`

## /api/v1 — protégées (auth:sanctum + role:admin,rh)
- `GET /api/v1/employes`
- `POST /api/v1/employes`
- `GET /api/v1/employes/{id}`
- `PUT /api/v1/employes/{id}`
- `DELETE /api/v1/employes/{id}`

- `GET /api/v1/contrats`
- `POST /api/v1/contrats`
- `GET /api/v1/contrats/{id}`
- `PUT /api/v1/contrats/{id}`
- `DELETE /api/v1/contrats/{id}`

- `GET /api/v1/documents`
- `POST /api/v1/documents`
- `GET /api/v1/documents/{id}`
- `PUT /api/v1/documents/{id}`
- `DELETE /api/v1/documents/{id}`
- `POST /api/v1/documents/upload`

- `GET /api/v1/absences-types`
- `POST /api/v1/absences-types`
- `GET /api/v1/absences-types/{id}`
- `PUT /api/v1/absences-types/{id}`
- `DELETE /api/v1/absences-types/{id}`

- `GET /api/v1/soldes-conges`
- `POST /api/v1/soldes-conges`
- `GET /api/v1/soldes-conges/{id}`
- `PUT /api/v1/soldes-conges/{id}`
- `DELETE /api/v1/soldes-conges/{id}`

- `GET /api/v1/demandes-conges`
- `POST /api/v1/demandes-conges`
- `GET /api/v1/demandes-conges/{id}`
- `PUT /api/v1/demandes-conges/{id}`
- `DELETE /api/v1/demandes-conges/{id}`
- `POST /api/v1/demandes-conges/{id}/manager-approve`
- `POST /api/v1/demandes-conges/{id}/rh-approve`
- `POST /api/v1/demandes-conges/{id}/reject`

- `GET /api/v1/pointages`
- `POST /api/v1/pointages`
- `GET /api/v1/pointages/releve-journalier`
- `GET /api/v1/pointages/releve-mensuel`

- `POST /api/v1/paies/generer`
- `GET /api/v1/paies/{id}/pdf`

- `GET /api/v1/paie-parametres`
- `PUT /api/v1/paie-parametres/{id}`
