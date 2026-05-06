## 1. Vue d'ensemble

Module RH est une application web de gestion des ressources humaines destinee principalement aux equipes RH et a l'administration. L'application centralise la gestion des employes, contrats, conges, presence, paie, caisse, documents RH et alertes dans une interface unique.

La valeur metier principale de l'application est de relier dans un meme outil :

- le suivi administratif des collaborateurs ;
- le suivi contractuel ;
- la gestion des absences et soldes ;
- le controle de la presence ;
- la generation et le suivi de la paie ;
- les justificatifs PDF ;
- le controle des mouvements de caisse associes.

Pour cette documentation, le perimetre retenu est volontairement recentre sur les scenarios metier effectivement presentables dans l'application, sans insister sur des modules secondaires, experimentaux ou non retenus pour la demonstration.

## 2. Sommaire

- [1. Vue d'ensemble](#1-vue-densemble)
- [2. Sommaire](#2-sommaire)
- [3. Scenarios lies au dashboard](#3-scenarios-lies-au-dashboard)
- [4. Scenarios lies aux employes](#4-scenarios-lies-aux-employes)
- [5. Scenarios lies aux contrats](#5-scenarios-lies-aux-contrats)
- [6. Scenarios lies aux conges](#6-scenarios-lies-aux-conges)
- [7. Scenarios lies au pointage et a la presence](#7-scenarios-lies-au-pointage-et-a-la-presence)
- [8. Scenarios lies a la paie](#8-scenarios-lies-a-la-paie)
- [9. Scenarios lies a la caisse](#9-scenarios-lies-a-la-caisse)
- [10. Scenarios lies aux documents-rh](#10-scenarios-lies-aux-documents-rh)
- [11. Scenarios lies aux alertes](#11-scenarios-lies-aux-alertes)
- [12. Scenarios lies a la configuration metier](#12-scenarios-lies-a-la-configuration-metier)
- [13. Classement des scenarios par importance](#13-classement-des-scenarios-par-importance)
- [14. Scenarios candidats pour les captures et la video](#14-scenarios-candidats-pour-les-captures-et-la-video)
- [15. Scenarios documentes mais non retenus pour la demo courte](#15-scenarios-documentes-mais-non-retenus-pour-la-demo-courte)
- [16. Points a verifier manuellement](#16-points-a-verifier-manuellement)

## 3. Scenarios lies au dashboard

### S01 - Consulter le tableau de bord RH

- Domaine : Dashboard
- Acteur concerne : RH, administrateur
- Objectif utilisateur : obtenir une vue d'ensemble immediate de la situation RH
- Preconditions : utilisateur authentifie, donnees de demonstration disponibles
- Etapes principales :
  1. L'utilisateur ouvre l'application.
  2. Il arrive sur le dashboard RH.
  3. Il consulte les indicateurs et alertes visibles.
- Resultat attendu : les informations globales RH sont disponibles immediatement
- Valeur metier : pilotage rapide des activites RH
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : meilleur point d'entree pour poser le contexte

### S02 - Consulter les statistiques RH d'une periode

- Domaine : Dashboard
- Acteur concerne : RH, administrateur
- Objectif utilisateur : visualiser les indicateurs sur une periode donnee
- Preconditions : donnees statistiques disponibles
- Etapes principales :
  1. L'utilisateur ajuste la periode.
  2. Le dashboard se recharge.
  3. Il consulte les evolutions affichees.
- Resultat attendu : les statistiques affichent la periode choisie
- Valeur metier : aide a l'analyse et au pilotage
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : utile si les donnees de demo sont coherentes

## 4. Scenarios lies aux employes

### S03 - Consulter la liste des employes

- Domaine : Employes
- Acteur concerne : RH
- Objectif utilisateur : retrouver un collaborateur dans l'annuaire
- Preconditions : des employes existent
- Etapes principales :
  1. L'utilisateur ouvre le module employes.
  2. Il parcourt ou filtre la liste.
  3. Il identifie l'employe souhaite.
- Resultat attendu : l'annuaire affiche les employes et leurs informations essentielles
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
  2. Il renseigne les informations administratives.
  3. Il enregistre la fiche.
- Resultat attendu : un nouvel employe est cree dans le systeme
- Valeur metier : alimente tous les processus RH en aval
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : plus utile en demonstration longue qu'en video courte

### S05 - Consulter une fiche employe consolidee

- Domaine : Employes
- Acteur concerne : RH
- Objectif utilisateur : voir sur une seule page les informations principales d'un employe
- Preconditions : un employe existe
- Etapes principales :
  1. L'utilisateur ouvre la fiche d'un employe.
  2. Il consulte les informations principales.
  3. Il parcourt les sections contrat, documents, pointages et conges.
  4. Il peut telecharger la fiche PDF.
- Resultat attendu : les donnees RH de l'employe sont centralisees
- Valeur metier : gain de temps et vision transverse du collaborateur
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : l'un des ecrans les plus forts de l'application

### S06 - Consulter l'historique des postes d'un employe

- Domaine : Employes
- Acteur concerne : RH
- Objectif utilisateur : retracer les mobilites internes
- Preconditions : des historiques existent
- Etapes principales :
  1. L'utilisateur ouvre la fiche employe ou le module historique.
  2. Il consulte les changements de poste.
  3. Il analyse la chronologie.
- Resultat attendu : les mobilites internes sont historisees
- Valeur metier : suivi d'evolution professionnelle
- Priorite : Secondaire
- Utilisable pour la demo : Optionnel
- Remarque : scenario utile mais non prioritaire

## 5. Scenarios lies aux contrats

### S07 - Creer un contrat pour un employe

- Domaine : Contrats
- Acteur concerne : RH
- Objectif utilisateur : rattacher un contrat actif a un employe
- Preconditions : employe existant et absence de contrat actif incompatible
- Etapes principales :
  1. L'utilisateur ouvre le module contrats.
  2. Il cree un contrat pour l'employe.
  3. Il renseigne type, dates et salaire.
  4. Il enregistre.
- Resultat attendu : le contrat est disponible pour la fiche employe et la paie
- Valeur metier : scenario central de gestion administrative
- Priorite : Critique
- Utilisable pour la demo : Optionnel
- Remarque : utile si la demo inclut la creation de donnees

### S08 - Consulter un contrat

- Domaine : Contrats
- Acteur concerne : RH
- Objectif utilisateur : verifier les informations contractuelles
- Preconditions : au moins un contrat existe
- Etapes principales :
  1. L'utilisateur ouvre la liste des contrats.
  2. Il selectionne un contrat.
  3. Il consulte son detail.
- Resultat attendu : les informations contractuelles sont accessibles
- Valeur metier : verification et controle administratif
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : bon scenario de documentation

## 6. Scenarios lies aux conges

### S09 - Consulter les soldes de conges

- Domaine : Conges
- Acteur concerne : RH
- Objectif utilisateur : verifier les droits acquis, utilises et restants
- Preconditions : des soldes existent
- Etapes principales :
  1. L'utilisateur ouvre les soldes de conges.
  2. Il filtre si besoin par employe ou type.
  3. Il consulte les soldes disponibles.
- Resultat attendu : les droits sont visibles et exploitables
- Valeur metier : aide immediate a la decision avant validation d'absence
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : bon ecran metier et lisible

### S10 - Creer une demande de conge

- Domaine : Conges
- Acteur concerne : RH
- Objectif utilisateur : enregistrer une demande d'absence
- Preconditions : employe existant, type de conge disponible
- Etapes principales :
  1. L'utilisateur ouvre le formulaire de demande.
  2. Il selectionne l'employe, le type et les dates.
  3. Il enregistre.
- Resultat attendu : la demande est creee avec son statut initial
- Valeur metier : point d'entree du workflow conges
- Priorite : Critique
- Utilisable pour la demo : Optionnel
- Remarque : plus fort si enchaine avec la validation

### S11 - Valider ou rejeter une demande de conge

- Domaine : Conges
- Acteur concerne : RH, manager selon le workflow
- Objectif utilisateur : prendre une decision sur une demande et appliquer son impact metier
- Preconditions : une demande existe en attente
- Etapes principales :
  1. L'utilisateur ouvre la liste des demandes.
  2. Il consulte une demande a traiter.
  3. Il valide ou rejette.
  4. Le solde est mis a jour en cas de validation finale.
- Resultat attendu : le workflow est applique et le statut evolue
- Valeur metier : coeur de la gestion des absences
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : excellent scenario de demonstration

## 7. Scenarios lies au pointage et a la presence

### S12 - Enregistrer un pointage

- Domaine : Pointage
- Acteur concerne : RH
- Objectif utilisateur : saisir une entree ou une sortie de presence
- Preconditions : employe actif, regles horaires disponibles
- Etapes principales :
  1. L'utilisateur ouvre le formulaire de pointage.
  2. Il saisit l'evenement.
  3. L'application controle la coherence.
  4. Le pointage est enregistre s'il est valide.
- Resultat attendu : le pointage alimente les releves de presence
- Valeur metier : base des calculs de temps et de paie
- Priorite : Critique
- Utilisable pour la demo : Optionnel
- Remarque : utile, mais moins lisible qu'un releve deja calcule

### S13 - Consulter le releve de presence

- Domaine : Presence
- Acteur concerne : RH
- Objectif utilisateur : analyser heures, retards et absences
- Preconditions : des pointages ou releves existent
- Etapes principales :
  1. L'utilisateur ouvre le releve de presence.
  2. Il choisit une periode.
  3. Il consulte le detail et les totaux.
- Resultat attendu : la presence est consolidee et lisible
- Valeur metier : verification avant paie
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : bon support a la paie

## 8. Scenarios lies a la paie

### S14 - Generer une fiche de paie

- Domaine : Paie
- Acteur concerne : RH, gestionnaire paie
- Objectif utilisateur : calculer la paie mensuelle d'un employe
- Preconditions : contrat actif, presence exploitable, parametres disponibles
- Etapes principales :
  1. L'utilisateur ouvre la generation ou l'etat de paie.
  2. Il choisit un employe et un mois.
  3. Il lance la generation.
  4. Le systeme calcule les montants.
- Resultat attendu : une fiche de paie est creee
- Valeur metier : coeur calculatoire de l'application
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : scenario incontournable

### S15 - Consulter le detail d'une fiche de paie

- Domaine : Paie
- Acteur concerne : RH, gestionnaire paie
- Objectif utilisateur : verifier le calcul avant validation
- Preconditions : une paie existe
- Etapes principales :
  1. L'utilisateur ouvre le detail de paie.
  2. Il consulte brut, retenues, net et details utiles.
  3. Il controle la coherence.
- Resultat attendu : le bulletin est justifie de facon transparente
- Valeur metier : fiabilite et comprehension du calcul
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : ecran tres fort metierement

### S16 - Valider une paie

- Domaine : Paie
- Acteur concerne : RH, gestionnaire paie
- Objectif utilisateur : faire passer la paie a l'etape de validation
- Preconditions : une paie generee existe
- Etapes principales :
  1. L'utilisateur ouvre l'etat ou le detail.
  2. Il declenche la validation.
  3. Le statut evolue.
- Resultat attendu : la paie passe au statut valide
- Valeur metier : controle avant paiement
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : bonne etape de narration

### S17 - Payer une paie

- Domaine : Paie
- Acteur concerne : RH, gestionnaire paie
- Objectif utilisateur : finaliser la paie et enregistrer le paiement
- Preconditions : paie validee, caisse disponible si necessaire
- Etapes principales :
  1. L'utilisateur selectionne la caisse si besoin.
  2. Il declenche le paiement.
  3. L'application enregistre le mouvement associe.
- Resultat attendu : la paie devient payee
- Valeur metier : cloture du cycle paie
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : bon scenario final

### S18 - Telecharger le bulletin de paie PDF

- Domaine : Paie
- Acteur concerne : RH
- Objectif utilisateur : obtenir le justificatif PDF
- Preconditions : une paie existe
- Etapes principales :
  1. L'utilisateur ouvre la fiche de paie.
  2. Il lance le telechargement PDF.
  3. Le fichier est recupere.
- Resultat attendu : le bulletin PDF est disponible
- Valeur metier : preuve documentaire immediate
- Priorite : Critique
- Utilisable pour la demo : Oui
- Remarque : excellente conclusion visuelle

## 9. Scenarios lies a la caisse

### S19 - Consulter l'etat de caisse

- Domaine : Caisse
- Acteur concerne : RH, administration
- Objectif utilisateur : suivre les soldes et mouvements de caisse
- Preconditions : des caisses existent
- Etapes principales :
  1. L'utilisateur ouvre le module caisse.
  2. Il consulte soldes et mouvements.
  3. Il filtre si necessaire.
- Resultat attendu : l'etat financier RH est visible
- Valeur metier : supervision des flux lies a la paie
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : utile en capture, pas indispensable en video courte

### S20 - Valider ou rejeter un mouvement de caisse

- Domaine : Caisse
- Acteur concerne : RH, administration
- Objectif utilisateur : controler un flux avant impact sur le solde
- Preconditions : un mouvement est en attente
- Etapes principales :
  1. L'utilisateur ouvre la validation caisse.
  2. Il consulte le mouvement.
  3. Il valide ou rejette.
- Resultat attendu : le mouvement est traite et le solde evolue si necessaire
- Valeur metier : controle financier des operations RH
- Priorite : Important
- Utilisable pour la demo : Oui
- Remarque : bon prolongement du paiement paie

## 10. Scenarios lies aux documents-rh

### S21 - Televerser un document employe

- Domaine : Documents RH
- Acteur concerne : RH
- Objectif utilisateur : rattacher un document administratif a un employe
- Preconditions : employe existant
- Etapes principales :
  1. L'utilisateur ouvre le formulaire document.
  2. Il selectionne l'employe et le type.
  3. Il televerse puis enregistre.
- Resultat attendu : le document apparait dans la fiche employe
- Valeur metier : centralisation documentaire
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : utile surtout en capture ou en support

### S22 - Consulter et telecharger des documents RH

- Domaine : Documents RH
- Acteur concerne : RH
- Objectif utilisateur : acceder aux justificatifs d'un employe
- Preconditions : des documents existent
- Etapes principales :
  1. L'utilisateur ouvre la liste ou la fiche employe.
  2. Il identifie le document voulu.
  3. Il le previsualise ou le telecharge.
- Resultat attendu : les documents sont rapidement accessibles
- Valeur metier : gain de temps administratif
- Priorite : Important
- Utilisable pour la demo : Optionnel
- Remarque : souvent mieux montre depuis la fiche employe

## 11. Scenarios lies aux alertes

### S23 - Consulter les alertes RH

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
- Remarque : bonne capture complementaire

## 12. Scenarios lies a la configuration metier

### S24 - Configurer les types de conges

- Domaine : Configuration metier
- Acteur concerne : RH, administrateur
- Objectif utilisateur : definir les regles metier des conges
- Preconditions : droits de configuration
- Etapes principales :
  1. L'utilisateur ouvre les types de conges.
  2. Il cree ou modifie une regle.
  3. Il enregistre.
- Resultat attendu : les demandes de conges reposent sur des regles coherentes
- Valeur metier : socle des workflows d'absence
- Priorite : Support
- Utilisable pour la demo : Non
- Remarque : scenario documente, mais peu visuel

### S25 - Configurer les parametres de paie

- Domaine : Configuration metier
- Acteur concerne : RH, administrateur habilite
- Objectif utilisateur : definir les taux et plafonds utilises dans la paie
- Preconditions : droits de configuration
- Etapes principales :
  1. L'utilisateur ouvre les parametres de paie.
  2. Il modifie les valeurs.
  3. Il enregistre.
- Resultat attendu : les paies utilisent les parametres saisis
- Valeur metier : justesse des bulletins
- Priorite : Support
- Utilisable pour la demo : Non
- Remarque : scenario de support interne

### S26 - Configurer le temps de travail

- Domaine : Configuration metier
- Acteur concerne : RH, administrateur habilite
- Objectif utilisateur : definir les horaires et regles de presence
- Preconditions : droits de configuration
- Etapes principales :
  1. L'utilisateur ouvre la configuration horaire.
  2. Il ajuste les regles.
  3. Il enregistre.
- Resultat attendu : les releves et calculs utilisent ces nouvelles regles
- Valeur metier : socle des calculs de presence
- Priorite : Support
- Utilisable pour la demo : Non
- Remarque : scenario necessaire au fonctionnement, peu demonstratif

## 13. Classement des scenarios par importance

### Critique

- S01 - Consulter le tableau de bord RH
- S03 - Consulter la liste des employes
- S05 - Consulter une fiche employe consolidee
- S07 - Creer un contrat pour un employe
- S09 - Consulter les soldes de conges
- S10 - Creer une demande de conge
- S11 - Valider ou rejeter une demande de conge
- S12 - Enregistrer un pointage
- S14 - Generer une fiche de paie
- S15 - Consulter le detail d'une fiche de paie
- S16 - Valider une paie
- S17 - Payer une paie
- S18 - Telecharger le bulletin de paie PDF

### Important

- S02 - Consulter les statistiques RH d'une periode
- S04 - Creer un employe
- S08 - Consulter un contrat
- S13 - Consulter le releve de presence
- S19 - Consulter l'etat de caisse
- S20 - Valider ou rejeter un mouvement de caisse
- S21 - Televerser un document employe
- S22 - Consulter et telecharger des documents RH
- S23 - Consulter les alertes RH

### Secondaire

- S06 - Consulter l'historique des postes d'un employe

### Support / configuration

- S24 - Configurer les types de conges
- S25 - Configurer les parametres de paie
- S26 - Configurer le temps de travail

## 14. Scenarios candidats pour les captures et la video

- S01 - Consulter le tableau de bord RH
- S03 - Consulter la liste des employes
- S05 - Consulter une fiche employe consolidee
- S09 - Consulter les soldes de conges
- S11 - Valider ou rejeter une demande de conge
- S14 - Generer une fiche de paie
- S15 - Consulter le detail d'une fiche de paie
- S17 - Payer une paie
- S18 - Telecharger le bulletin de paie PDF
- S20 - Valider ou rejeter un mouvement de caisse

## 15. Scenarios documentes mais non retenus pour la demo courte

- S02 : utile pour l'analyse, mais redondant si le dashboard est deja montre.
- S04 : scenario important, mais trop oriente formulaire pour une video courte.
- S06 : scenario complet, mais trop secondaire pour la narration principale.
- S07 : coeur metier, mais peut allonger inutilement la demo si la paie est deja preparee.
- S08 : utile en documentation, moins fort visuellement qu'une fiche employe ou une paie.
- S10 : interessant, mais S11 est plus demonstratif car il montre le resultat.
- S12 : fort metierement, mais moins lisible a l'ecran qu'un releve ou une paie.
- S13 : bon support, mais moins decisif que le detail de paie.
- S19 : utile en capture, pas indispensable a la storyline principale.
- S21 : scenario valable, mais moins prioritaire que la fiche employe ou la paie.
- S22 : faible dynamique video.
- S23 : scenario complementaire plutot adapte au portfolio qu'a la video de 40 secondes.
- S24 : scenario de configuration.
- S25 : scenario de configuration.
- S26 : scenario de configuration.

## 16. Points a verifier manuellement

- [ ] Les scenarios existent-ils reellement dans l'application ?
- [ ] Les etapes sont-elles correctes ?
- [ ] Les roles sont-ils coherents ?
- [ ] Les scenarios critiques representent-ils bien le coeur metier ?
- [ ] Les scenarios de support sont-ils bien separes des scenarios principaux ?
- [ ] Les scenarios candidats pour la demo sont-ils realistes ?
