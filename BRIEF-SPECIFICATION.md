Module RH
Application de gestion RH centralisant employes, contrats, conges, presence, paie, documents et alertes.
Cible: equipes RH et administration, avec workflow de validation et traçabilite des actions.

1. Contexte et objectif
- Probleme: informations RH dispersees, suivi paie/absence non fiable, manque de pilotage.
- Objectifs principaux: centraliser les donnees RH, automatiser les calculs critiques, tracer et securiser les actions.

2. Perimetre fonctionnel
2.1 Donnees / referentiels
- Employe, contrat, poste, categorie de poste, departement
- Type de conge, jour ferie, calendrier RH
- Parametres horaires, parametres paie, elements de remuneration
- Caisse, mouvement de caisse, document RH, archive
- Role, permission, audit

2.2 Module Employes
- Creer, modifier, consulter un employe
- Fiche employe PDF, export
- Associer poste, departement, categorie

2.3 Module Contrats
- Creer, consulter, cloturer, renouveler un contrat
- Historique des contrats
- Document contractuel associe

2.4 Module Conges / absences
- Types de conges (paye, non paye, cumulable, quota)
- Demandes de conges: creation, validation, rejet, suivi
- Soldes par type et periode
- Jours feries et calendrier RH

2.5 Module Pointage / presence
- Enregistrer entree/sortie
- Releves journalier et mensuel
- Calcul heures travaillees, retards, absences, heures sup

2.6 Module Paie
- Parametres de paie (CNAPS, OSTIE, IRSA, primes)
- Generation fiche de paie par mois
- Validation, paiement, PDF
- Etat et suivi de paie

2.7 Module Caisse
- Types de caisse, activation/desactivation
- Mouvements entree/sortie, validation
- Impact sur solde de caisse

2.8 Module Documents
- Depots documents employes
- Bulletin de paie, contrat, reçu de paiement
- Archivage et retention

2.9 Module Alertes RH
- Fin de contrat proche
- Conges non pris, conges en attente
- Absences frequentes, jours feries proches

2.10 Module Audit / Permissions
- Journal d'audit filtre et export
- Gestion roles/permissions, matrice d'acces

2.11 Module IA et automatisation
- Chatbot RH
- Detection d'anomalies (pointage, paie, conges, contrats)

3. Logique metier et regles critiques
- Employe sans contrat actif = inactif pour traitements RH
- Un seul contrat actif par employe
- Changement de contrat: cloturer l'actif avant creation du nouveau
- Conges payes acquis mensuellement (regle standard 2.5 j/mois)
- Conges payes consomment le solde selon FIFO
- Jour ferie = absence payee, ne deduit pas le solde de conges
- Pointage: interdit entree sans sortie, sortie sans entree, chevauchement
- Paie: une fiche par employe et par mois, pas de duplication
- Fiche de paie: statut attente -> validee -> payee
- Paiement paie genere mouvement de caisse et reçu
- Acces et actions sensibles limites par role/permission

4. Architecture technique
- Backend: API Laravel 12 (PHP 8.2) + Sanctum
- Frontend: SPA Vue 3 + Vite
- Base de donnees: PostgreSQL
- Organisation: monorepo avec backend/ et frontend/
- Flux principaux:
  - Frontend -> API Laravel -> PostgreSQL
  - API -> Queue -> Generation snapshots/paie
  - API -> Storage -> Documents PDF

5. Indicateurs / pilotage
- Effectif global, effectif par departement
- Taux d'absence, taux de retard, heures sup
- Solde moyen de conges, conges en attente
- Paie: total brut, retenues, net a payer par periode
- Caisse: solde par caisse, mouvements en attente
