# Scénarios de Test & Debug - Module RH

## Vue d'ensemble
Ce document contient tous les scénarios de test pour chaque fonctionnalité du module RH, avec les données de test et les workflows associés.

---

## A. Tableaux de bord et indicateurs RH

### A.1 Statistiques effectifs
**Endpoint:** `GET /api/v1/dashboard/statistiques?filtre=annee&date=2025-12-14`

**Scénario 1:** Affichage des KPI principaux
- Total employés actifs
- Nouveaux employés sur la période
- Départs sur la période
- Employés inactifs

**Données de test requises:**
```json
{
  "employes": [
    { "matricule": "EMP001", "nom": "Dupont", "prenom": "Jean", "date_embauche": "2024-01-15" },
    { "matricule": "EMP002", "nom": "Martin", "prenom": "Marie", "date_embauche": "2025-03-01" },
    { "matricule": "EMP003", "nom": "Durand", "prenom": "Pierre", "date_embauche": "2023-06-01" }
  ]
}
```

**Workflow de test:**
1. Créer 3 employés avec dates d'embauche différentes
2. Créer des contrats actifs pour 2 d'entre eux
3. Créer un contrat terminé pour le 3ème
4. Appeler l'endpoint et vérifier les chiffres

### A.2 Indicateurs RH
**Métriques:**
- Taux de turnover
- Taux d'absentéisme
- Ancienneté moyenne
- Performance moyenne

### A.3 Répartitions graphiques
- Par département
- Par type de contrat
- Par tranche d'âge
- Par genre

### A.4 Alertes
**Types d'alertes:**
- Fin de contrat proche (< 30 jours)
- Congés non pris
- Période d'essai fin proche
- Dépassement budget formation

---

## B. Gestion des performances

### B.1 Créer une évaluation
**Endpoint:** `POST /api/v1/evaluations`

**Données de test:**
```json
{
  "employe_id": 1,
  "periode": "2025-12",
  "notes": [
    { "critere_id": 1, "note": 85, "commentaire": "Très bon travail" },
    { "critere_id": 2, "note": 75, "commentaire": "À améliorer" }
  ],
  "points_forts": "Autonomie, initiative",
  "axes_amelioration": "Communication",
  "objectifs": "Améliorer les rapports mensuels",
  "statut": "brouillon"
}
```

**Workflow:**
1. Créer des critères d'évaluation (qualité, ponctualité, travail d'équipe, etc.)
2. Sélectionner un employé
3. Remplir les notes pour chaque critère
4. Calculer le score global pondéré
5. Valider l'évaluation

### B.2 Consulter historique évaluations
**Endpoint:** `GET /api/v1/evaluations/employe/{id}/historique`

### B.3 Générer rapport PDF
**Endpoint:** `GET /api/v1/evaluations/{id}/pdf`

---

## C. Gestion des compétences

### C.1 Cartographie des compétences
**Endpoint:** `GET /api/v1/competences/cartographie`

**Données de test:**
```json
{
  "categories": [
    { "nom": "Techniques", "description": "Compétences techniques" },
    { "nom": "Soft Skills", "description": "Compétences comportementales" }
  ],
  "competences": [
    { "code": "DEV-PHP", "nom": "PHP/Laravel", "categorie_id": 1 },
    { "code": "DEV-JS", "nom": "JavaScript/Vue.js", "categorie_id": 1 },
    { "code": "COM-ORA", "nom": "Communication orale", "categorie_id": 2 }
  ]
}
```

### C.2 Matching profil/poste
**Endpoint:** `POST /api/v1/matching/compatibilite`

**Données de test:**
```json
{
  "employe_id": 1,
  "poste_id": 2
}
```

**Résultat attendu:**
```json
{
  "score_global": 75.5,
  "score_obligatoires": 80,
  "score_souhaitees": 65,
  "competences_manquantes": [],
  "competences_insuffisantes": [
    { "nom": "PHP/Laravel", "niveau_requis": 4, "niveau_employe": 3 }
  ]
}
```

### C.3 Suggestions de formations
**Endpoint:** `GET /api/v1/matching/employes/{id}/suggestions-formations`

---

## D. Self-Service Employé

### D.1 Dashboard employé
**Endpoint:** `GET /api/v1/self-service/dashboard`

**Workflow:**
1. L'employé se connecte
2. Voit ses KPI: congés restants, demandes en attente, messages non lus
3. Accès rapide à ses fonctions

### D.2 Mise à jour profil
**Endpoint:** `PUT /api/v1/self-service/profil`

**Données modifiables:**
```json
{
  "telephone": "0612345678",
  "adresse": "15 rue des Lilas, 75015 Paris"
}
```

### D.3 Consulter bulletins de paie
**Endpoint:** `GET /api/v1/self-service/bulletins`

### D.4 Consulter solde congés
**Endpoint:** `GET /api/v1/self-service/solde-conges`

### D.5 Soumettre demande de congé
**Endpoint:** `POST /api/v1/self-service/demandes-conges`

**Données de test:**
```json
{
  "type_conge_id": 1,
  "date_debut": "2025-12-24",
  "date_fin": "2025-12-31",
  "motif": "Vacances de Noël"
}
```

### D.6 Soumettre demande RH
**Endpoint:** `POST /api/v1/self-service/demandes`

**Types de demandes:**
- Attestation de travail
- Attestation de salaire
- Demande de remboursement
- Demande de formation

---

## E. Portail Manager

### E.1 Dashboard Manager
**Endpoint:** `GET /api/v1/manager/dashboard`

**Résultat attendu:**
```json
{
  "departement": { "id": 1, "nom": "Informatique" },
  "equipe": { "total": 5, "employes": [...] },
  "demandes_en_attente": { "conges": 2, "rh": 1 },
  "absences_mois": { "total_jours_absences": 8 },
  "performance": { "note_moyenne": 75, "nombre_evaluations": 3 }
}
```

### E.2 Voir équipe
**Endpoint:** `GET /api/v1/manager/equipe`

### E.3 Valider demande de congé
**Endpoint:** `POST /api/v1/manager/demandes-conges/{id}/valider`

**Workflow:**
1. Manager voit les demandes en attente
2. Clique sur valider ou rejeter
3. Ajoute un commentaire (optionnel pour validation, obligatoire pour rejet)
4. La demande passe en statut "manager_valide"
5. Notification envoyée à l'employé

### E.4 Statistiques absences équipe
**Endpoint:** `GET /api/v1/manager/statistiques/absences`

### E.5 Statistiques performance équipe
**Endpoint:** `GET /api/v1/manager/statistiques/performance`

---

## F. Automatisation et IA

### F.1 Chatbot RH
**Endpoint:** `POST /api/v1/chatbot/ask`

**Questions de test:**
```json
{ "question": "Combien de jours de congés me reste-t-il ?" }
{ "question": "Comment demander une attestation de travail ?" }
{ "question": "Quelle est la procédure pour un remboursement ?" }
```

**Note:** Nécessite configuration API Gemini dans .env:
```
GEMINI_API_KEY=your_key
```

### F.2 Génération de documents
**Endpoint:** `POST /api/v1/documents-generator/generer`

**Types de documents:**
- Attestation de travail
- Certificat de travail
- Attestation de salaire
- Lettre de recommandation
- Attestation de formation
- Contrat de travail

### F.3 Prédiction turnover
**Endpoint:** `GET /api/v1/turnover`

**Facteurs analysés:**
- Ancienneté
- Dernière augmentation
- Absences répétées
- Score de performance bas
- Type de contrat

### F.4 Détection anomalies
**Endpoint:** `GET /api/v1/anomalies/dashboard`

**Types d'anomalies:**
- Heures supplémentaires excessives
- Retards répétés
- Écarts de paie
- Chevauchement de congés

### F.5 Matching IA CV/Poste
**Endpoint:** `GET /api/v1/matching-ia/postes/{id}/candidats`

---

## G. Conformité et Audit

### G.1 Journal d'audit
**Endpoint:** `GET /api/v1/audit`

**Filtres disponibles:**
- user_id: Filtrer par utilisateur
- action: create, update, delete, view, export, approve, reject
- type: Employe, Contrat, DemandeConge, etc.
- from/to: Plage de dates

### G.2 Export audit
**Endpoint:** `GET /api/v1/audit/export`

### G.3 Historique entité
**Endpoint:** `GET /api/v1/audit/entity-history?type=Employe&id=1`

### G.4 Archives
**Endpoint:** `GET /api/v1/archives`

**Actions:**
- Archiver un document
- Consulter les documents archivés
- Vérifier l'intégrité des archives
- Supprimer les archives expirées

### G.5 Permissions par rôle
**Endpoint:** `GET /api/v1/permissions/roles`

**Rôles:**
- admin: Accès complet
- rh: Gestion RH complète
- manager: Gestion équipe
- employe: Self-service uniquement

---

## Corrections identifiées

### Backend

1. **SelfServiceController** - Vérification mot de passe incorrecte
   - Problème: Comparaison directe sans Hash::check()
   - Fichier: `app/Http/Controllers/Api/SelfServiceController.php`

2. **ManagerService** - Champ note_globale vs score_global
   - Problème: Incohérence entre le champ utilisé dans le service et le modèle
   - Fichier: `app/Services/ManagerService.php`

3. **DemandeConge** - Champs de validation workflow manquants
   - Problème: Les champs manager_id, date_validation_manager, commentaire_manager ne sont pas dans fillable
   - Fichier: `app/Models/DemandeConge.php`

### Frontend

4. **SelfService/DashboardView** - Appel API incorrect
   - Problème: Le service retourne directement les données, pas un objet avec profil, solde_conges, etc.
   - Fichier: `frontend/src/views/SelfService/DashboardView.vue`

5. **Routes manquantes dans le routeur**
   - Problème: Certaines routes self-service référencées mais non définies
   - Fichier: `frontend/src/router/index.js`

---

## Données de seed recommandées

Voir fichier `database/seeders/TestDataSeeder.php` pour créer:
- 5 départements avec managers
- 20 employés répartis
- 3 types de congés
- 10 compétences
- 5 formations
- Historique de pointages
- Demandes de congés en différents statuts
