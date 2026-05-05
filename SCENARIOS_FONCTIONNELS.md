# Scénarios fonctionnels de l’application

## 1. Vue d’ensemble

Contexte de l’application :

L'application est un module RH pour la gestion interne des employés d'une entreprise (module-RH). Elle permet de gérer les dossiers employés, les documents (contrats, bulletins), les mouvements de caisse liés aux paies, et de visualiser des synthèses et rapports (caisse, paie, tableau de bord). Les utilisateurs principaux sont : administrateurs RH, gestionnaires de paie, et employés.

Objectif de l’application :

- Centraliser les informations RH et documents employés
- Automatiser la génération de synthèses (caisse, paie)
- Fournir un tableau de bord récapitulatif pour décisions RH

Fonctionnalités principales :
- Recherche et consultation du dossier employé
- Téléversement et visualisation de documents employés
- Génération et visualisation de synthèses (paie, caisse)
- Gestion des catégories et mouvements
- Tableau de bord avec KPI et snapshots

## 2. Liste des scénarios identifiés

### Scénario 1 — Consultation du dossier employé
- Acteur concerné: Gestionnaire RH
- Objectif utilisateur: Visualiser les informations personnelles et documents d’un employé
- Préconditions: L’utilisateur est authentifié et a le rôle RH; l’employé existe dans la base
- Étapes principales:
  1. Aller sur la page de recherche/employés
  2. Rechercher par nom ou matricule
  3. Cliquer sur le résultat pour ouvrir le dossier employé
  4. Consulter l’onglet “Documents” et ouvrir un document (ex: contrat)
- Résultat attendu: Fiche employé affichée avec données à jour et documents consultables
- Valeur pour démonstration: Critique — montre le coeur de la gestion RH (données+documents)

### Scénario 2 — Téléversement d’un document employé
- Acteur concerné: Gestionnaire RH
- Objectif utilisateur: Ajouter un document officiel au dossier d’un employé
- Préconditions: Dossier employé ouvert; l’utilisateur a droit d’écrire
- Étapes principales:
  1. Ouvrir le dossier employé
  2. Aller dans l’onglet “Documents” → “Ajouter un document”
  3. Sélectionner le fichier et renseigner type/date
  4. Valider le téléversement
- Résultat attendu: Document visible dans la liste, téléchargeable
- Valeur pour démonstration: Important — interaction CRUD et preuve de stockage

### Scénario 3 — Génération d’une synthèse paie mensuelle
- Acteur concerné: Gestionnaire paie
- Objectif utilisateur: Générer la synthèse de paie pour un mois donné
- Préconditions: Accès à la fonctionnalité de synthèse; données de paie présentes
- Étapes principales:
  1. Aller sur la page “Synthèse paie”
  2. Choisir le mois et lancer la génération
  3. Attendre confirmation et consulter le rapport/synthèse
- Résultat attendu: Synthèse générée, téléchargeable/exportable et indiquant totaux
- Valeur pour démonstration: Critique — montre automatisation des tâches RH

### Scénario 4 — Visualisation du tableau de bord (KPI)
- Acteur concerné: Administrateur RH / Manager
- Objectif utilisateur: Avoir une vue synthétique des indicateurs clés (effectifs, coût, caisse)
- Préconditions: Données agrégées disponibles
- Étapes principales:
  1. Ouvrir la page “Tableau de bord”
  2. Parcourir les widgets (effectifs, coûts, alertes)
  3. Cliquer sur un widget pour filtrer/voir détails
- Résultat attendu: KPI affichés, interactions possibles
- Valeur pour démonstration: Critique — visuel fort, utile pour la vidéo

### Scénario 5 — Envoi d’une notification interne (ex: nouvelle fiche paie disponible)
- Acteur concerné: Gestionnaire paie
- Objectif utilisateur: Notifier un employé qu’un document est disponible
- Préconditions: Employé a un compte et contact configuré
- Étapes principales:
  1. Générer/ajouter la fiche paie
  2. Cliquer sur “Notifier” ou cocher envoi
  3. Confirmer l’envoi
- Résultat attendu: Notification envoyée; historique visible
- Valeur pour démonstration: Faible valeur — utile mais secondaire

### Scénario 6 — Rechercher mouvements de caisse
- Acteur concerné: Comptable / Gestionnaire RH
- Objectif utilisateur: Consulter les mouvements liés à la paie ou caisses
- Préconditions: Accès à la page mouvements; données présentes
- Étapes principales:
  1. Ouvrir “Mouvements de caisse”
  2. Appliquer filtres (date, catégorie)
  3. Consulter ou exporter la liste
- Résultat attendu: Liste filtrée correcte et export possible
- Valeur pour démonstration: Important — montre traçabilité financière

### Scénario 7 — Pages de configuration et paramétrage
- Acteur concerné: Administrateur RH / Responsable Paie
- Objectif utilisateur: Configurer les règles et paramètres globaux (paie, notifications, modes de paiement, règles de pointage)
- Préconditions: L'utilisateur a le rôle admin; accès aux écrans de configuration
- Étapes principales:
  1. Ouvrir la page « Paramètres » → choisir « Paie / Règles »
  2. Vérifier ou modifier les paramètres : tranches IRSA, taux CNAPS, plafond, modes de paie
  3. Enregistrer et valider les changements
  4. Ouvrir « Paramètres pointage » et vérifier les règles (tolérances, types de pointage)
- Résultat attendu: Les paramètres sont sauvegardés, documentés et utilisés par les processus (paie, notifications, pointage)
- Valeur pour démonstration: Important — montre que l'application est paramétrable et que les calculs utilisent ces règles

### Scénario 8 — Pointage et calcul de paie (intégration)
- Acteur concerné: Employé (pointage), Gestionnaire paie (calcul)
- Objectif utilisateur: Saisir pointages, puis lancer le calcul/ génération des paies en prenant en compte les règles configurées
- Préconditions: Paramètres de paie définis (IRSA, CNAPS), employés actifs présents
- Étapes principales:
  1. Saisir des pointages (entrée/sortie/pause) pour un employé sur une période
  2. Aller à « Générer paie » pour le mois concerné
  3. Lancer la génération; vérifier que les heures sont prises en compte, que les retenues et taux applicables sont calculés selon les paramètres
  4. Vérifier le résultat : paie générée, synthèse et mouvement de caisse créés
- Résultat attendu: Paie calculée correctement selon heures pointées et paramètres; synthèse et mouvements liés créés
- Valeur pour démonstration: Critique — montre le workflow complet pointage → paie → caisse
