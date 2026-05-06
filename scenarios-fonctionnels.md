## 1. Vue d'ensemble

Module RH est une application web de gestion des ressources humaines destinee aux equipes RH, a l'administration et, selon les parcours, aux managers et aux employes en self-service. Elle centralise les donnees collaborateurs, les contrats, les conges, la presence, la paie, la caisse, les documents RH, les alertes, les permissions, l'audit et plusieurs modules d'aide a la decision.

L'objectif metier principal est de fiabiliser et centraliser les operations RH critiques dans une seule interface :

- gestion administrative des employes ;
- suivi contractuel ;
- gestion des absences et soldes ;
- controle de la presence et du temps de travail ;
- generation, validation et paiement de la paie ;
- suivi de caisse associe ;
- generation de justificatifs PDF ;
- tracabilite et controle d'acces.

Les utilisateurs principaux reellement couverts sont :

- administrateur ;
- RH ;
- manager sur certains workflows ;
- employe en self-service pour quelques parcours dedies.

## 2. Sommaire

- [1. Vue d'ensemble](#1-vue-densemble)
- [2. Sommaire](#2-sommaire)
- [3. Scenarios lies au dashboard](#3-scenarios-lies-au-dashboard)
- [4. Scenarios lies aux employes et a lorganisation-rh](#4-scenarios-lies-aux-employes-et-a-lorganisation-rh)
- [5. Scenarios lies aux contrats](#5-scenarios-lies-aux-contrats)
- [6. Scenarios lies aux conges et au calendrier-rh](#6-scenarios-lies-aux-conges-et-au-calendrier-rh)
- [7. Scenarios lies au pointage et a la presence](#7-scenarios-lies-au-pointage-et-a-la-presence)
- [8. Scenarios lies a la paie](#8-scenarios-lies-a-la-paie)
- [9. Scenarios lies a la caisse](#9-scenarios-lies-a-la-caisse)
- [10. Scenarios lies aux documents-rh](#10-scenarios-lies-aux-documents-rh)
- [11. Scenarios lies aux alertes](#11-scenarios-lies-aux-alertes)
- [12. Scenarios lies aux competences formations et matching](#12-scenarios-lies-aux-competences-formations-et-matching)
- [13. Scenarios lies au self-service employe](#13-scenarios-lies-au-self-service-employe)
- [14. Scenarios lies a laudit archives et permissions](#14-scenarios-lies-a-laudit-archives-et-permissions)
- [15. Scenarios lies a lIA et aux automatisations](#15-scenarios-lies-a-lia-et-aux-automatisations)
- [16. Scenarios lies a la configuration et aux referentiels](#16-scenarios-lies-a-la-configuration-et-aux-referentiels)
- [17. Classement des scenarios par importance](#17-classement-des-scenarios-par-importance)
- [18. Scenarios candidats pour les captures et la video](#18-scenarios-candidats-pour-les-captures-et-la-video)
- [19. Scenarios documentes mais non retenus pour la demo courte](#19-scenarios-documentes-mais-non-retenus-pour-la-demo-courte)
- [20. Points a verifier manuellement](#20-points-a-verifier-manuellement)

## 3. Scenarios lies au dashboard

### S01 - Consulter le tableau de bord RH

- Domaine : Dashboard
- Acteur concerne : RH, administrateur
- Objectif utilisateur : obtenir une vue d'ensemble immediate des indicateurs RH et alertes
- Preconditions : utilisateur authentifie, donnees disponibles
- Etapes principales :
  1. L'utilisateur ouvre l'application.
  2. Il arrive sur le dashboard RH.
  3. Il consulte KPI, alertes et acces rapides.
- Resultat attendu : la situation RH globale est visible immediatement
- Valeur metier : pilotage quotidien des activites RH
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : meilleur ecran d'introduction

### S02 - Consulter les statistiques RH par periode

- Domaine : Dashboard
- Acteur concerne : RH, administrateur
- Objectif utilisateur : analyser l'evolution des indicateurs RH sur une periode
- Preconditions : donnees de synthese disponibles
- Etapes principales :
  1. L'utilisateur change le filtre de periode.
  2. Le dashboard recharge les indicateurs.
  3. Il compare les tendances.
- Resultat attendu : les statistiques reflètent la periode choisie
- Valeur metier : aide a l'analyse et a la decision
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : utile si les donnees demo sont coherentes

## 4. Scenarios lies aux employes et a lorganisation-rh

### S03 - Consulter la liste des employes

- Domaine : Employes
- Acteur concerne : RH
- Objectif utilisateur : retrouver un collaborateur dans l'annuaire
- Preconditions : des employes existent
- Etapes principales :
  1. L'utilisateur ouvre le module employes.
  2. Il parcourt ou filtre la liste.
  3. Il identifie l'employe cible.
- Resultat attendu : les employes et leurs informations principales sont visibles
- Valeur metier : point d'entree central de la gestion RH
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : bonne transition vers la fiche employe

### S04 - Creer un employe

- Domaine : Employes
- Acteur concerne : RH
- Objectif utilisateur : enregistrer un nouveau collaborateur
- Preconditions : droits de creation
- Etapes principales :
  1. L'utilisateur ouvre le formulaire de creation.
  2. Il renseigne les informations personnelles et administratives.
  3. Il enregistre la fiche.
- Resultat attendu : un nouvel employe est cree dans le referentiel
- Valeur metier : alimente le cycle RH complet
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : scenario utile mais moins fort visuellement qu'une fiche deja renseignee

### S05 - Consulter une fiche employe consolidee

- Domaine : Employes
- Acteur concerne : RH
- Objectif utilisateur : voir sur une seule page le statut, le contrat, les documents, les pointages et les conges d'un employe
- Preconditions : un employe existe
- Etapes principales :
  1. L'utilisateur ouvre la fiche d'un employe.
  2. Il consulte les informations principales.
  3. Il parcourt contrat, documents, pointages et soldes.
  4. Il peut telecharger la fiche PDF.
- Resultat attendu : les donnees RH de l'employe sont centralisees
- Valeur metier : gain de temps et vision transversale du collaborateur
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : un des meilleurs ecrans metier du produit

### S06 - Suivre l'historique des postes d'un employe

- Domaine : Employes
- Acteur concerne : RH
- Objectif utilisateur : retracer les mobilites internes
- Preconditions : des historiques de postes existent
- Etapes principales :
  1. L'utilisateur ouvre la fiche employe ou le module historiques.
  2. Il consulte les changements de poste.
  3. Il analyse la chronologie.
- Resultat attendu : les mobilites sont historisees et consultables
- Valeur metier : suivi de carriere et traçabilite interne
- Priorite : Secondaire
- Utilisable pour la demo : Optionnel
- Remarque : complet mais moins prioritaire pour une demo courte

### S07 - Gerer les departements, postes et categories de poste

- Domaine : Organisation RH
- Acteur concerne : RH, administrateur
- Objectif utilisateur : maintenir la structure organisationnelle
- Preconditions : droits de gestion referentielle
- Etapes principales :
  1. L'utilisateur ouvre les modules concernes.
  2. Il cree ou modifie un departement, un poste ou une categorie.
  3. Il rattache les elements entre eux.
- Resultat attendu : la structure organisationnelle est a jour
- Valeur metier : base de coherence pour les affectations et statistiques
- Priorite : Support
- Utilisable pour la demo : Non
- Remarque : scenario de support et de parametrage

## 5. Scenarios lies aux contrats

### S08 - Creer un contrat pour un employe

- Domaine : Contrats
- Acteur concerne : RH
- Objectif utilisateur : rattacher un contrat actif a un employe
- Preconditions : employe existant, absence de doublon de contrat actif
- Etapes principales :
  1. L'utilisateur ouvre le module contrats.
  2. Il cree un contrat pour un employe.
  3. Il renseigne type, dates et salaire.
  4. Il enregistre.
- Resultat attendu : le contrat devient disponible pour la fiche employe et la paie
- Valeur metier : scenario central de gestion administrative
- Priorite : Critique
- Utilisable pour la demo : Optionnel
- Remarque : important, mais peut allonger inutilement la storyline courte

### S09 - Consulter un contrat et son detail

- Domaine : Contrats
- Acteur concerne : RH
- Objectif utilisateur : verifier les informations contractuelles
- Preconditions : au moins un contrat existe
- Etapes principales :
  1. L'utilisateur ouvre la liste des contrats.
  2. Il selectionne un contrat.
  3. Il consulte le detail.
- Resultat attendu : les conditions contractuelles sont accessibles
- Valeur metier : verification et controle des donnees contractuelles
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : tres utile en documentation et capture

### S10 - Consulter l'historique des contrats

- Domaine : Contrats
- Acteur concerne : RH
- Objectif utilisateur : retracer les contrats successifs
- Preconditions : des contrats historiques existent
- Etapes principales :
  1. L'utilisateur ouvre l'historique des contrats.
  2. Il filtre par employe ou periode.
  3. Il consulte la chronologie.
- Resultat attendu : les evolutions contractuelles sont historisees
- Valeur metier : continute administrative et verification
- Priorite : Secondaire
- Utilisable pour la demo : Non
- Remarque : scenario riche mais peu prioritaire en video

## 6. Scenarios lies aux conges et au calendrier-rh

### S11 - Configurer un type de conge

- Domaine : Conges
- Acteur concerne : RH, administrateur
- Objectif utilisateur : definir les regles d'un type de conge
- Preconditions : droits de configuration
- Etapes principales :
  1. L'utilisateur ouvre les types de conges.
  2. Il cree ou modifie un type.
  3. Il renseigne quotas et regles.
- Resultat attendu : le type de conge devient exploitable
- Valeur metier : conditionne les regles d'absence
- Priorite : Support
- Utilisable pour la demo : Non
- Remarque : essentiel fonctionnellement, peu demonstratif

### S12 - Consulter les soldes de conges

- Domaine : Conges
- Acteur concerne : RH
- Objectif utilisateur : verifier acquis, utilises, restants et expirations
- Preconditions : des soldes existent
- Etapes principales :
  1. L'utilisateur ouvre les soldes de conges.
  2. Il filtre eventuellement par employe ou type.
  3. Il consulte les soldes par type.
- Resultat attendu : les droits sont visibles et exploitables
- Valeur metier : aide immediate a la decision RH
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : bon ecran de preuve metier

### S13 - Creer une demande de conge

- Domaine : Conges
- Acteur concerne : RH, manager selon le parcours, employe en self-service selon le cas
- Objectif utilisateur : soumettre une demande d'absence
- Preconditions : employe existant, type de conge disponible
- Etapes principales :
  1. L'utilisateur ouvre le formulaire.
  2. Il selectionne employe, type et dates.
  3. Il enregistre la demande.
- Resultat attendu : la demande est creee avec un statut initial
- Valeur metier : point d'entree du workflow d'absence
- Priorite : Critique
- Utilisable pour la demo : Optionnel
- Remarque : plus fort si enchaine avec une validation

### S14 - Valider ou rejeter une demande de conge

- Domaine : Conges
- Acteur concerne : Manager, RH
- Objectif utilisateur : prendre une decision et produire un impact sur le solde ou le statut
- Preconditions : demande en attente ou validee manager
- Etapes principales :
  1. L'utilisateur ouvre la liste des demandes.
  2. Il consulte une demande en attente.
  3. Il valide ou rejette.
  4. Le solde est mis a jour en cas de validation finale.
- Resultat attendu : le workflow est applique et trace
- Valeur metier : scenario coeur de gestion RH
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : excellent candidat de demonstration

### S15 - Gerer les jours feries et le calendrier RH

- Domaine : Calendrier RH
- Acteur concerne : RH, administrateur
- Objectif utilisateur : tenir a jour les jours feries et evenements RH
- Preconditions : droits de gestion
- Etapes principales :
  1. L'utilisateur ouvre les jours feries ou le calendrier.
  2. Il cree ou modifie une entree.
  3. Il verifie son apparition.
- Resultat attendu : le calendrier RH est a jour
- Valeur metier : coherence entre conges, presence et paie
- Priorite : Important
- Utilisable pour la demo : Non
- Remarque : utile mais trop long a contextualiser en video

## 7. Scenarios lies au pointage et a la presence

### S16 - Enregistrer un pointage

- Domaine : Pointage
- Acteur concerne : RH
- Objectif utilisateur : saisir une entree ou une sortie de presence
- Preconditions : employe actif, regles horaires disponibles
- Etapes principales :
  1. L'utilisateur ouvre le formulaire de pointage.
  2. Il saisit l'evenement.
  3. L'application controle la coherence.
  4. Le pointage est enregistre si valide.
- Resultat attendu : le pointage alimente les releves de presence
- Valeur metier : base des calculs de temps et de paie
- Priorite : Critique
- Utilisable pour la demo : Optionnel
- Remarque : fonction forte mais moins lisible qu'un releve ou une paie deja calculee

### S17 - Consulter le releve de presence

- Domaine : Presence
- Acteur concerne : RH
- Objectif utilisateur : analyser heures, retards, absences et heures supplementaires
- Preconditions : des donnees de presence existent
- Etapes principales :
  1. L'utilisateur ouvre le releve de presence.
  2. Il choisit une periode.
  3. Il consulte le detail et les totaux.
- Resultat attendu : la presence est consolidee et lisible
- Valeur metier : verification avant paie
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : bon ecran de support

### S18 - Detecter une incoherence de pointage

- Domaine : Pointage
- Acteur concerne : RH
- Objectif utilisateur : empecher des saisies invalides
- Preconditions : une tentative de pointage invalide est effectuee
- Etapes principales :
  1. L'utilisateur saisit un pointage incoherent.
  2. L'application applique les controles.
  3. Elle refuse l'enregistrement.
- Resultat attendu : la qualite des donnees est preservee
- Valeur metier : fiabilite des calculs de presence
- Priorite : Important
- Utilisable pour la demo : Non
- Remarque : forte valeur metier, faible valeur visuelle

## 8. Scenarios lies a la paie

### S19 - Configurer les parametres de paie

- Domaine : Paie
- Acteur concerne : Administrateur, RH habilite
- Objectif utilisateur : definir taux et plafonds de calcul
- Preconditions : droits de configuration
- Etapes principales :
  1. L'utilisateur ouvre les parametres de paie.
  2. Il modifie les valeurs.
  3. Il enregistre.
- Resultat attendu : les calculs futurs utilisent ces parametres
- Valeur metier : justesse des bulletins
- Priorite : Support
- Utilisable pour la demo : Non
- Remarque : scenario de configuration

### S20 - Gerer les elements de remuneration

- Domaine : Paie
- Acteur concerne : RH
- Objectif utilisateur : creer ou maintenir des primes et indemnites
- Preconditions : droits sur le module
- Etapes principales :
  1. L'utilisateur ouvre les elements de remuneration.
  2. Il cree ou modifie un element.
  3. Il l'enregistre.
- Resultat attendu : l'element devient utilisable dans la paie
- Valeur metier : prise en compte des cas reels de remuneration
- Priorite : Important
- Utilisable pour la demo : Non
- Remarque : plus complet qu'essentiel pour une video courte

### S21 - Generer une fiche de paie

- Domaine : Paie
- Acteur concerne : RH, gestionnaire paie
- Objectif utilisateur : calculer la paie mensuelle d'un employe
- Preconditions : contrat actif, presence exploitable, regles de paie disponibles
- Etapes principales :
  1. L'utilisateur ouvre la generation ou l'etat de paie.
  2. Il choisit un employe et un mois.
  3. Il lance la generation.
  4. Le systeme calcule les montants.
- Resultat attendu : une fiche de paie est creee avec son statut
- Valeur metier : coeur calculatoire du produit
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : scenario incontournable

### S22 - Consulter le detail d'une fiche de paie

- Domaine : Paie
- Acteur concerne : RH, gestionnaire paie
- Objectif utilisateur : verifier le calcul avant validation
- Preconditions : une paie existe
- Etapes principales :
  1. L'utilisateur ouvre le detail de paie.
  2. Il consulte brut, retenues, net et details.
  3. Il controle la coherence.
- Resultat attendu : le bulletin est justifie de facon transparente
- Valeur metier : fiabilite et comprehension du calcul
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : tres fort visuellement et metierement

### S23 - Valider une paie

- Domaine : Paie
- Acteur concerne : RH, gestionnaire paie
- Objectif utilisateur : faire franchir l'etape de controle a une paie
- Preconditions : une paie generee existe
- Etapes principales :
  1. L'utilisateur ouvre l'etat ou le detail.
  2. Il declenche la validation.
  3. Le statut evolue.
- Resultat attendu : la paie passe au statut valide
- Valeur metier : controle avant paiement
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : bon pivot narratif en video

### S24 - Payer une paie et produire le justificatif

- Domaine : Paie
- Acteur concerne : RH, gestionnaire paie
- Objectif utilisateur : enregistrer le paiement et finaliser le cycle
- Preconditions : paie validee, caisse disponible si workflow de paiement actif
- Etapes principales :
  1. L'utilisateur choisit une caisse si necessaire.
  2. Il declenche le paiement.
  3. Le mouvement de caisse est genere.
  4. Le recu ou l'etat final est disponible.
- Resultat attendu : la paie devient payee et justifiee
- Valeur metier : cloture du cycle paie
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : excellente conclusion de demonstration

### S25 - Telecharger le bulletin de paie PDF

- Domaine : Paie
- Acteur concerne : RH, employe en self-service selon le parcours
- Objectif utilisateur : obtenir le justificatif PDF
- Preconditions : une fiche de paie existe
- Etapes principales :
  1. L'utilisateur ouvre la fiche de paie.
  2. Il lance le telechargement PDF.
  3. Le fichier est genere puis recupere.
- Resultat attendu : le bulletin PDF est disponible
- Valeur metier : preuve documentaire immediate
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : bonne sortie visuelle pour la video

## 9. Scenarios lies a la caisse

### S26 - Consulter l'etat de caisse

- Domaine : Caisse
- Acteur concerne : RH, administration
- Objectif utilisateur : suivre les soldes et mouvements
- Preconditions : des caisses existent
- Etapes principales :
  1. L'utilisateur ouvre le module caisse.
  2. Il consulte soldes et mouvements.
  3. Il filtre si necessaire.
- Resultat attendu : l'etat financier RH est visible
- Valeur metier : supervision des flux de paiement
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : bon ecran de support

### S27 - Creer ou activer un type de caisse

- Domaine : Caisse
- Acteur concerne : RH, administration
- Objectif utilisateur : maintenir la liste des caisses disponibles
- Preconditions : droits de gestion
- Etapes principales :
  1. L'utilisateur ouvre les types de caisse.
  2. Il cree ou active/desactive une caisse.
  3. Il confirme l'etat final.
- Resultat attendu : les caisses utilisables sont mises a jour
- Valeur metier : maintenance du referentiel financier
- Priorite : Support
- Utilisable pour la demo : Non
- Remarque : documentation utile, faible impact visuel

### S28 - Soumettre un mouvement de caisse

- Domaine : Caisse
- Acteur concerne : RH, administration
- Objectif utilisateur : enregistrer une entree ou sortie soumise a validation
- Preconditions : une caisse active existe
- Etapes principales :
  1. L'utilisateur ouvre le formulaire de mouvement.
  2. Il saisit type, categorie, montant et source.
  3. Il soumet le mouvement.
- Resultat attendu : le mouvement passe en attente
- Valeur metier : separation entre saisie et impact financier
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : scenario intermediaire utile mais allongeant la narration

### S29 - Valider ou rejeter un mouvement de caisse

- Domaine : Caisse
- Acteur concerne : RH, administration
- Objectif utilisateur : controler les flux avant impact sur le solde
- Preconditions : un mouvement est en attente
- Etapes principales :
  1. L'utilisateur ouvre la validation caisse.
  2. Il consulte le mouvement.
  3. Il valide ou rejette.
- Resultat attendu : le mouvement est traite et le solde evolue si besoin
- Valeur metier : controle financier des operations RH
- Priorite : Important
- Utilisable pour la demo : Oui
- Remarque : utile en prolongement d'un paiement de paie

## 10. Scenarios lies aux documents-rh

### S30 - Televerser un document employe

- Domaine : Documents RH
- Acteur concerne : RH
- Objectif utilisateur : rattacher un document a un employe
- Preconditions : employe existant
- Etapes principales :
  1. L'utilisateur ouvre le formulaire document.
  2. Il selectionne l'employe et le type.
  3. Il televerse le fichier.
  4. Il enregistre.
- Resultat attendu : le document apparait dans la fiche employe et le module documents
- Valeur metier : centralisation documentaire
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : plus utile en capture qu'en video courte

### S31 - Consulter et telecharger des documents RH

- Domaine : Documents RH
- Acteur concerne : RH
- Objectif utilisateur : acceder rapidement aux justificatifs
- Preconditions : des documents existent
- Etapes principales :
  1. L'utilisateur ouvre la liste ou la fiche employe.
  2. Il identifie le document voulu.
  3. Il le previsualise ou le telecharge.
- Resultat attendu : les pieces sont accessibles sans recherche externe
- Valeur metier : gain de temps administratif
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : souvent mieux montre dans la fiche employe

## 11. Scenarios lies aux alertes

### S32 - Consulter les alertes RH

- Domaine : Alertes
- Acteur concerne : RH
- Objectif utilisateur : identifier les situations a traiter rapidement
- Preconditions : des alertes existent
- Etapes principales :
  1. L'utilisateur ouvre les alertes.
  2. Il consulte les cartes ou listes.
  3. Il priorise ses actions.
- Resultat attendu : les situations sensibles sont visibles immediatement
- Valeur metier : supervision proactive
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : tres bonne capture de portfolio

### S33 - Configurer les seuils d'alerte

- Domaine : Alertes
- Acteur concerne : RH, administrateur
- Objectif utilisateur : regler la sensibilite des alertes metier
- Preconditions : droits de configuration
- Etapes principales :
  1. L'utilisateur ouvre les parametres d'alerte.
  2. Il ajuste seuils et criticites.
  3. Il enregistre.
- Resultat attendu : les futures alertes utilisent ces regles
- Valeur metier : adaptation aux besoins internes
- Priorite : Support
- Utilisable pour la demo : Non
- Remarque : scenario de configuration

## 12. Scenarios lies aux competences formations et matching

### S34 - Consulter les competences des employes

- Domaine : Competences
- Acteur concerne : RH
- Objectif utilisateur : visualiser les competences detenues
- Preconditions : des competences sont associees aux employes
- Etapes principales :
  1. L'utilisateur ouvre le module competences employes.
  2. Il filtre ou selectionne un employe.
  3. Il consulte les niveaux ou categories.
- Resultat attendu : la cartographie des competences est disponible
- Valeur metier : aide au staffing et a l'evolution interne
- Priorite : Secondaire
- Utilisable pour la demo : Non
- Remarque : scenario complet mais hors coeur de la demo courte

### S35 - Consulter les formations

- Domaine : Formations
- Acteur concerne : RH
- Objectif utilisateur : suivre le catalogue ou l'historique de formation
- Preconditions : des formations existent
- Etapes principales :
  1. L'utilisateur ouvre le module formations.
  2. Il consulte le catalogue ou les parcours suivis.
  3. Il analyse les competences associees.
- Resultat attendu : l'offre de formation est exploitable
- Valeur metier : developpement des competences
- Priorite : Secondaire
- Utilisable pour la demo : Non
- Remarque : peu prioritaire pour une video de 40 secondes

### S36 - Lancer un matching profil-poste

- Domaine : Matching
- Acteur concerne : RH
- Objectif utilisateur : evaluer l'adaptation d'un profil a un poste
- Preconditions : competences et postes disponibles
- Etapes principales :
  1. L'utilisateur ouvre le module de matching.
  2. Il selectionne un employe ou un poste.
  3. Il consulte la compatibilite et les recommandations.
- Resultat attendu : une analyse de correspondance est fournie
- Valeur metier : aide a la decision RH
- Priorite : Secondaire
- Utilisable pour la demo : Optionnel
- Remarque : scenario differentiant mais non essentiel

## 13. Scenarios lies au self-service employe

### S37 - Consulter son dashboard self-service

- Domaine : Self-service
- Acteur concerne : Employe
- Objectif utilisateur : acceder a un resume de ses donnees RH
- Preconditions : employe connecte avec compte lie
- Etapes principales :
  1. L'employe ouvre son espace.
  2. Il consulte ses indicateurs et acces rapides.
  3. Il navigue vers ses modules personnels.
- Resultat attendu : l'employe accede seul a ses informations
- Valeur metier : autonomie et reduction des demandes RH
- Priorite : Important
- Utilisable pour la demo : Non
- Remarque : seconde histoire utilisateur

### S38 - Consulter ses bulletins et documents

- Domaine : Self-service
- Acteur concerne : Employe
- Objectif utilisateur : telecharger ses justificatifs sans intervention RH
- Preconditions : des bulletins ou documents existent
- Etapes principales :
  1. L'employe ouvre bulletins ou documents.
  2. Il selectionne un fichier.
  3. Il le telecharge.
- Resultat attendu : les justificatifs personnels sont accessibles en autonomie
- Valeur metier : reduction des demandes administratives
- Priorite : Important
- Utilisable pour la demo : Non
- Remarque : fort pour une demo orientee employe, pas pour la story RH principale

### S39 - Consulter ses conges en self-service

- Domaine : Self-service
- Acteur concerne : Employe
- Objectif utilisateur : suivre ses soldes et demandes
- Preconditions : donnees de conges disponibles
- Etapes principales :
  1. L'employe ouvre le module conges.
  2. Il consulte ses soldes et statuts.
  3. Il suit ses demandes.
- Resultat attendu : la situation conges est lisible sans passer par les RH
- Valeur metier : transparence pour le collaborateur
- Priorite : Important
- Utilisable pour la demo : Non
- Remarque : hors focus de la demo RH courte

## 14. Scenarios lies a laudit archives et permissions

### S40 - Consulter le journal d'audit

- Domaine : Audit
- Acteur concerne : Administrateur, RH habilite
- Objectif utilisateur : tracer les actions sensibles
- Preconditions : des actions ont ete journalisees
- Etapes principales :
  1. L'utilisateur ouvre l'audit.
  2. Il filtre les logs.
  3. Il consulte un evenement.
- Resultat attendu : les operations sensibles sont tracees
- Valeur metier : conformite et controle interne
- Priorite : Secondaire
- Utilisable pour la demo : Non
- Remarque : forte valeur de credibilite, faible valeur visuelle

### S41 - Exporter les logs d'audit

- Domaine : Audit
- Acteur concerne : Administrateur
- Objectif utilisateur : produire un export de controle
- Preconditions : acces au module audit
- Etapes principales :
  1. L'utilisateur applique des filtres.
  2. Il declenche l'export.
  3. Il recupere le fichier.
- Resultat attendu : les logs sont exportables
- Valeur metier : exploitation externe et verification
- Priorite : Support
- Utilisable pour la demo : Non
- Remarque : scenario utile mais tres technique

### S42 - Consulter ou maintenir les archives

- Domaine : Archives
- Acteur concerne : Administrateur, RH habilite
- Objectif utilisateur : suivre la retention et l'etat des documents archives
- Preconditions : des archives existent
- Etapes principales :
  1. L'utilisateur ouvre les archives.
  2. Il consulte documents et parametres.
  3. Il verifie l'integrite ou lance une maintenance.
- Resultat attendu : les archives restent conformes et exploitables
- Valeur metier : conformite documentaire
- Priorite : Support
- Utilisable pour la demo : Non
- Remarque : scenario de support / conformite

### S43 - Gerer les permissions par role

- Domaine : Permissions
- Acteur concerne : Administrateur
- Objectif utilisateur : controler les droits d'acces par role
- Preconditions : compte administrateur
- Etapes principales :
  1. L'utilisateur ouvre la matrice des permissions.
  2. Il active ou desactive des droits.
  3. Les changements sont enregistres.
- Resultat attendu : les acces evoluent selon la politique choisie
- Valeur metier : securisation de l'application
- Priorite : Support
- Utilisable pour la demo : Non
- Remarque : scenario utile a documenter, peu demonstratif

## 15. Scenarios lies a lIA et aux automatisations

### S44 - Consulter la detection d'anomalies

- Domaine : IA et automatisation
- Acteur concerne : RH, administrateur
- Objectif utilisateur : identifier automatiquement des anomalies sur pointage, paie, conges ou contrats
- Preconditions : donnees et module anomalies disponibles
- Etapes principales :
  1. L'utilisateur ouvre la vue anomalies.
  2. Il parcourt les anomalies par type ou gravite.
  3. Il navigue vers le collaborateur concerne si necessaire.
- Resultat attendu : les cas anormaux sont remontes sans recherche manuelle
- Valeur metier : assistance au controle RH
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : scenario differentiant si les donnees sont convaincantes

### S45 - Utiliser le matching IA

- Domaine : IA et automatisation
- Acteur concerne : RH
- Objectif utilisateur : obtenir des suggestions de postes, candidats ou formations
- Preconditions : module matching IA accessible
- Etapes principales :
  1. L'utilisateur ouvre le module.
  2. Il lance une analyse.
  3. Il consulte les recommandations.
- Resultat attendu : une aide a la decision est fournie
- Valeur metier : fonctionnalite differentiatrice
- Priorite : Secondaire
- Utilisable pour la demo : Optionnel
- Remarque : a reserver si l'on veut mettre l'IA en avant

### S46 - Utiliser le chatbot RH

- Domaine : IA et automatisation
- Acteur concerne : RH, utilisateur habilite
- Objectif utilisateur : obtenir une assistance conversationnelle sur des sujets RH
- Preconditions : chatbot disponible et accessible
- Etapes principales :
  1. L'utilisateur ouvre le chatbot.
  2. Il saisit une question.
  3. Il consulte la reponse.
- Resultat attendu : une assistance contextuelle est fournie
- Valeur metier : support rapide et transversal
- Priorite : Secondaire
- Utilisable pour la demo : Optionnel
- Remarque : a utiliser seulement si la fonction est stable en demo

## 16. Scenarios lies a la configuration et aux referentiels

### S47 - Configurer le temps de travail

- Domaine : Configuration
- Acteur concerne : Administrateur, RH habilite
- Objectif utilisateur : definir jours ouvres, horaires, tolerance et regles associees
- Preconditions : droits de configuration
- Etapes principales :
  1. L'utilisateur ouvre la configuration horaire.
  2. Il ajuste les valeurs.
  3. Il enregistre.
- Resultat attendu : les releves et calculs utilisent les nouvelles regles
- Valeur metier : socle des calculs de presence et de paie
- Priorite : Support
- Utilisable pour la demo : Non
- Remarque : scenario de parametrage interne

### S48 - Configurer l'entreprise et les devises

- Domaine : Configuration
- Acteur concerne : Administrateur
- Objectif utilisateur : maintenir les informations globales de l'entreprise
- Preconditions : droits de configuration
- Etapes principales :
  1. L'utilisateur ouvre les parametres entreprise ou devises.
  2. Il modifie les informations necessaires.
  3. Il enregistre.
- Resultat attendu : les informations globales sont appliquees aux ecrans et documents
- Valeur metier : coherence institutionnelle et documentaire
- Priorite : Support
- Utilisable pour la demo : Non
- Remarque : impact reel mais faible interet visuel

## 17. Classement des scenarios par importance

### Critique

- S01 - Consulter le tableau de bord RH
- S03 - Consulter la liste des employes
- S05 - Consulter une fiche employe consolidee
- S08 - Creer un contrat pour un employe
- S12 - Consulter les soldes de conges
- S13 - Creer une demande de conge
- S14 - Valider ou rejeter une demande de conge
- S16 - Enregistrer un pointage
- S21 - Generer une fiche de paie
- S22 - Consulter le detail d'une fiche de paie
- S23 - Valider une paie
- S24 - Payer une paie et produire le justificatif
- S25 - Telecharger le bulletin de paie PDF

### Important

- S02 - Consulter les statistiques RH par periode
- S04 - Creer un employe
- S09 - Consulter un contrat et son detail
- S15 - Gerer les jours feries et le calendrier RH
- S17 - Consulter le releve de presence
- S18 - Detecter une incoherence de pointage
- S20 - Gerer les elements de remuneration
- S26 - Consulter l'etat de caisse
- S28 - Soumettre un mouvement de caisse
- S29 - Valider ou rejeter un mouvement de caisse
- S30 - Televerser un document employe
- S31 - Consulter et telecharger des documents RH
- S32 - Consulter les alertes RH
- S37 - Consulter son dashboard self-service
- S38 - Consulter ses bulletins et documents
- S39 - Consulter ses conges en self-service
- S44 - Consulter la detection d'anomalies

### Secondaire

- S06 - Suivre l'historique des postes d'un employe
- S10 - Consulter l'historique des contrats
- S34 - Consulter les competences des employes
- S35 - Consulter les formations
- S36 - Lancer un matching profil-poste
- S40 - Consulter le journal d'audit
- S45 - Utiliser le matching IA
- S46 - Utiliser le chatbot RH

### Support / configuration

- S07 - Gerer les departements, postes et categories de poste
- S11 - Configurer un type de conge
- S19 - Configurer les parametres de paie
- S27 - Creer ou activer un type de caisse
- S33 - Configurer les seuils d'alerte
- S41 - Exporter les logs d'audit
- S42 - Consulter ou maintenir les archives
- S43 - Gerer les permissions par role
- S47 - Configurer le temps de travail
- S48 - Configurer l'entreprise et les devises

## 18. Scenarios candidats pour les captures et la video

- S01 - Consulter le tableau de bord RH
- S03 - Consulter la liste des employes
- S05 - Consulter une fiche employe consolidee
- S12 - Consulter les soldes de conges
- S14 - Valider ou rejeter une demande de conge
- S21 - Generer une fiche de paie
- S22 - Consulter le detail d'une fiche de paie
- S24 - Payer une paie et produire le justificatif
- S25 - Telecharger le bulletin de paie PDF
- S29 - Valider ou rejeter un mouvement de caisse
- S32 - Consulter les alertes RH

## 19. Scenarios documentes mais non retenus pour la demo courte

- S02 : utile analytiquement, mais redondant si le dashboard est deja montre.
- S04 : scenario important mais trop formulaire pour une courte demo.
- S06 : trop detaille pour une video d'environ 40 secondes.
- S07 : scenario referentiel a faible valeur visuelle.
- S08 : coeur metier, mais rallonge beaucoup la storyline si l'objectif est la paie.
- S09 : utile en capture ou documentation, moins fort en narration courte.
- S10 : historique pertinent mais secondaire.
- S11 : scenario de configuration.
- S13 : utile, mais S14 est plus demonstratif grace a l'impact visible.
- S15 : scenario long a contextualiser.
- S16 : metierement fort, visuellement moins lisible qu'une fiche ou un detail de paie.
- S17 : bon appui, mais moins decisif que la paie.
- S18 : forte valeur de controle, faible valeur visuelle.
- S19 : scenario technique de parametrage.
- S20 : riche mais secondaire dans une demo generaliste.
- S26 : bon ecran de support, pas indispensable a la storyline principale.
- S27 : maintenance referentielle.
- S28 : etape intermediaire qui allonge la narration.
- S30 : important mais moins prioritaire que la fiche employe ou la paie.
- S31 : faible dynamique video.
- S33 : pure configuration.
- S34 : hors coeur metier de la demo courte.
- S35 : trop secondaire.
- S36 : differentiant, mais disperserait la video.
- S37 : seconde histoire utilisateur.
- S38 : self-service hors focus principal RH.
- S39 : idem, utile mais hors storyline retenue.
- S40 : credibilise le produit mais apporte peu visuellement.
- S41 : trop technique.
- S42 : scenario de support / conformite.
- S43 : scenario d'administration interne.
- S44 : interessant si l'IA est prioritaire, sinon trop dispersant.
- S45 : differentiant, mais non essentiel a la comprehension globale.
- S46 : a montrer seulement si le chatbot est tres stable et convaincant.
- S47 : configuration.
- S48 : support institutionnel et documentaire.

## 20. Points a verifier manuellement

- [ ] Les scenarios existent-ils reellement dans l'application ?
- [ ] Les etapes sont-elles correctes ?
- [ ] Les roles sont-ils coherents ?
- [ ] Les scenarios critiques representent-ils bien le coeur metier ?
- [ ] Les scenarios de support sont-ils bien separes des scenarios principaux ?
- [ ] Les scenarios candidats pour la demo sont-ils realistes ?
