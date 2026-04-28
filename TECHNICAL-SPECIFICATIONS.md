# Spécifications fonctionnelles et techniques — Module RH

## 1. Objectif de l'application

Le Module RH est une application de gestion des ressources humaines destinée à centraliser les opérations RH, administratives et de paie d'une entreprise. Elle permet aux équipes RH de gérer les employés, les contrats, les postes, les congés, les présences, la paie, les documents, les alertes, les compétences et les suivis de conformité depuis une interface unique.

L'objectif principal est de donner une vision claire de ce que l'application permet de faire, des règles métiers déjà couvertes et des modules disponibles pour les différents profils utilisateurs.

## 2. Profils utilisateurs

Dans cette première version, l'application est limitée aux profils internes RH et Administration :

- **Administrateur** : accès complet à la configuration, aux permissions, aux données RH, à la paie, aux audits et aux modules avancés.
- **RH** : gestion opérationnelle des employés, contrats, congés, pointages, paie, documents, formations, alertes et tableaux de bord.

Le périmètre initial couvre uniquement ces deux profils applicatifs.

## 3. Architecture générale

Le projet est composé de deux applications :

- **Backend** : API Laravel exposant les endpoints métiers, la gestion de l'authentification, les règles de calcul, les exports PDF et les traitements RH.
- **Frontend** : SPA Vue 3 consommant l'API backend et fournissant l'interface utilisateur.
- **Base de données** : PostgreSQL, avec des tables métiers et des vues utilisées notamment pour les soldes de congés.
- **Authentification** : Laravel Sanctum avec contrôle d'accès par rôle et permissions.

## 4. Tableau de bord

Le tableau de bord fournit une vue d'ensemble des informations RH importantes :

- statistiques globales sur les employés, les contrats, les absences et la paie ;
- alertes opérationnelles nécessitant une action ;
- indicateurs de performance et synthèses RH ;
- accès rapide aux modules principaux selon le rôle connecté.

## 5. Organisation RH

### 5.1 Employés

L'application permet de gérer l'annuaire des employés avec les informations principales :

- nom, prénom, matricule, statut, poste, catégorie, département et informations personnelles ;
- fiche employé détaillée avec les données administratives, professionnelles, contractuelles et documentaires ;
- création, modification et consultation des employés ;
- export ou téléchargement d'une fiche employé en PDF.

Règle métier importante :

- un employé créé n'a pas automatiquement de contrat ni de poste actif ;
- tant qu'il n'est pas rattaché à un contrat et à un poste, il reste considéré comme inactif ou non pleinement opérationnel dans les traitements RH.

### 5.2 Départements

Les départements représentent la structure interne de l'entreprise. Ils peuvent être utilisés pour :

- classer les employés ;
- rattacher les postes ;
- structurer les employés par service ou unité ;
- alimenter les statistiques par service.

### 5.3 Postes et catégories de postes

Les postes permettent de structurer les fonctions disponibles dans l'entreprise :

- rattachement à un département ;
- rattachement à une catégorie ou famille de poste ;
- suivi des mobilités internes ;
- association possible avec des compétences requises.

Les catégories de postes servent à regrouper les postes par niveau, famille ou classification interne.

### 5.4 Historique des postes

Le module conserve les changements de poste d'un employé :

- affectation initiale ;
- mutation ou promotion ;
- historique des mobilités ;
- consultation de l'évolution professionnelle.

## 6. Contrats

Le module contrats permet de gérer le cycle de vie contractuel des employés :

- création d'un contrat pour un employé ;
- consultation des contrats actifs ;
- détail d'un contrat ;
- historique des contrats ;
- génération ou téléchargement de documents de contrat ;
- clôture ou renouvellement de contrat.

Règles métiers :

- un employé ne peut pas avoir plusieurs contrats actifs simultanément ;
- pour changer le contrat d'un employé, le contrat en cours doit être clôturé avant d'en créer un nouveau ;
- seuls les employés sans contrat actif peuvent recevoir un nouveau contrat actif ;
- la fin de contrat peut déclencher une alerte selon le seuil configuré.

## 7. Congés, absences et calendrier

### 7.1 Types de congés

Les types de congés permettent de définir les règles applicables aux absences :

- congé payé ou non payé ;
- quota limité ou illimité ;
- limite en jours ;
- fréquence d'application ;
- caractère cumulable ou non cumulable ;
- règles d'acquisition selon l'ancienneté, le contrat ou le temps partiel.

### 7.2 Soldes de congés

Le module soldes de congés permet de suivre les droits de chaque employé :

- jours acquis ;
- jours utilisés ;
- jours restants ;
- solde par type de congé ;
- solde par période ;
- détail de la consommation des droits.

Les congés payés sont acquis mensuellement. La règle standard présente dans l'application crédite 2,5 jours par mois complet travaillé, avec possibilité d'adapter les règles via les paramètres de congés.

### 7.3 Demandes de congés

Les demandes de congés couvrent :

- création d'une demande par les RH ;
- validation RH ;
- rejet avec commentaire ;
- suivi du statut ;
- consommation automatique du solde lors de la validation finale.

Les congés payés consomment les acquis disponibles selon une logique FIFO. Les congés non payés ou exceptionnels peuvent être gérés comme des absences autorisées sans forcément déduire le solde de congés payés.

### 7.4 Jours fériés

Le module jours fériés permet de gérer :

- les jours fériés ponctuels ;
- les jours fériés récurrents ;
- leur prise en compte dans le calendrier, les présences et la paie.

Un jour férié est considéré comme une absence légitime payée et ne doit pas déduire le solde de congés.

### 7.5 Calendrier RH

Le calendrier offre une vue globale des événements impactant la disponibilité des employés :

- congés ;
- absences ;
- jours fériés ;
- événements RH internes.

Tout événement ne correspondant pas à un congé, une absence ou un jour férié est classé comme événement RH.

## 8. Alertes RH

Le module alertes permet d'identifier automatiquement les situations nécessitant une action :

- fin de contrat proche ;
- congés non pris au-delà d'un seuil ;
- demande de congé en attente depuis trop longtemps ;
- congé proche non validé ;
- absences maladie fréquentes ;
- congés exceptionnels fréquents ;
- jours fériés proches.

Chaque type d'alerte peut être activé, désactivé et configuré avec ses propres seuils :

- seuil en jours ;
- seuil numérique ;
- fenêtre d'analyse ;
- niveau de criticité.

## 9. Pointage, présence et temps de travail

### 9.1 Pointages

Le pointage permet d'enregistrer les entrées et sorties des employés :

- création d'un pointage d'entrée ;
- création d'un pointage de sortie ;
- consultation filtrée par employé et par période ;
- contrôle de cohérence avant enregistrement.

Contrôles bloquants prévus :

- deux entrées consécutives sans sortie ;
- sortie sans entrée préalable ;
- durée négative ou incohérente ;
- chevauchement de pointages sur un même créneau.

Ces cas doivent empêcher l'enregistrement et retourner un message d'erreur explicite.

### 9.2 Présence

Le module présence calcule les informations de travail à partir des pointages :

- heures travaillées ;
- heures supplémentaires ;
- retards ;
- absences ;
- absences justifiées par un congé validé ;
- présence partielle ;
- heures manquantes.

Les relevés disponibles incluent :

- relevé journalier ;
- relevé mensuel ;
- relevé utilisé pour la paie.

### 9.3 Paramètres horaires

La configuration du temps de travail permet de définir :

- jours ouvrés ;
- heure de début ;
- heure de fin ;
- durée de pause ;
- nombre d'heures par jour ;
- mode de gestion du samedi ;
- tolérance de retard ;
- déduction des retards ou absences sur salaire ou sur solde de congés.

## 10. Paie et rémunération

### 10.1 Paramètres de paie

Le module paramètres de paie permet de configurer :

- plafond CNAPS ;
- taux CNAPS employé ;
- taux CNAPS employeur ;
- taux OSTIE employé ;
- taux OSTIE employeur ;
- primes fixes ;
- tranches IRSA ;
- règles de calcul applicables à la paie.

### 10.2 Éléments de rémunération

L'application permet de gérer des éléments variables ou complémentaires :

- primes ;
- indemnités ;
- avantages ;
- éléments taxables ou non taxables ;
- règles d'application par employé, période ou condition.

Ces éléments sont intégrés dans le calcul de la fiche de paie.

### 10.3 Génération de paie

La génération de paie calcule une fiche pour un employé et un mois donné :

- salaire de base du contrat ;
- salaire proportionnel selon la présence ;
- heures travaillées ;
- heures supplémentaires ;
- heures de nuit si applicables ;
- retards et absences ;
- primes fixes ;
- éléments de rémunération variables ;
- retenues CNAPS, OSTIE et IRSA ;
- total brut ;
- total des retenues ;
- net à payer.

Règles métiers :

- une fiche de paie ne peut pas être générée deux fois pour le même employé et le même mois ;
- la fiche générée passe en statut d'attente de validation ;
- après validation, elle peut être payée ;
- le paiement peut générer un reçu et alimenter les mouvements de caisse ;
- la fiche de paie est téléchargeable en PDF.

### 10.4 État et suivi de paie

Le module état de paie permet de connaître le montant à décaisser sur une période :

- liste des employés concernés ;
- statut de paie : non générée, en attente de validation, validée ou payée ;
- montant brut, retenues et net ;
- accès à la génération, validation, paiement et téléchargement PDF.

Le suivi de paie donne une vue d'avancement mensuelle ou périodique des traitements.

## 11. Caisse

Le module caisse permet de suivre les flux financiers liés ou non à la paie :

- état des caisses ;
- types de caisse ;
- mouvements d'entrée et de sortie ;
- mouvements en attente de validation ;
- validation ou rejet des mouvements ;
- activation ou désactivation d'une caisse ;
- impact des paiements validés sur le solde.

## 12. Documents RH

L'application permet de gérer et générer plusieurs documents :

- documents employés téléversés ;
- contrat de travail ;
- bulletin de paie ;
- reçu de paiement de paie.

Les documents peuvent être associés aux employés, aux contrats ou aux paies selon leur nature.

## 16. Audit, archives et conformité

### 16.1 Audit

Le journal d'audit permet de tracer les actions importantes :

- consultation des événements ;
- filtrage par action, type, utilisateur ou entité ;
- historique d'une entité ;
- statistiques d'audit ;
- export des logs.

### 16.2 Archives

Le module archives permet de gérer la conservation des documents :

- paramètres de rétention ;
- documents archivés ;
- documents expirés ou proches d'expiration ;
- traitement des documents expirés ;
- vérification d'intégrité ;
- téléchargement et ajout de notes.

### 16.3 Permissions

Le module permissions permet aux administrateurs de gérer :

- permissions disponibles ;
- rôles ;
- matrice de permissions ;
- permissions par rôle ;
- vérification d'une permission.

## 17. Modules IA et automatisation

L'application contient des modules d'aide à la décision :

- chatbot RH ;
- analyse par département ;
- détection d'anomalies sur pointage, paie, congés, contrats et heures ;

Ces modules servent d'assistance aux RH. Les décisions finales restent à valider par les utilisateurs habilités.

## 18. Paramètres entreprise et référentiels

L'application permet de configurer les informations globales :

- nom de l'entreprise ;
- logo ;
- devise par défaut ;
- devises disponibles ;
- paramètres de paie ;
- paramètres horaires ;
- jours fériés ;
- types de congés ;
- catégories de postes ;
- catégories de compétences.

## 19. Règles transverses importantes

- Les accès sont filtrés selon le rôle connecté.
- Les traitements sensibles, comme paie, contrats, permissions, audit et archives, sont réservés aux profils habilités.
- Les demandes suivent un workflow de validation avant d'avoir un impact métier définitif.
- Les calculs de paie tiennent compte des pointages, jours ouvrés, jours fériés, congés validés, primes et retenues configurées.
- Les jours fériés sont exclus des jours travaillés exigés et ne doivent pas réduire le solde de congés.
- Les données RH sensibles doivent être manipulées avec traçabilité et contrôle d'accès.

## 20. Périmètre fonctionnel couvert

Le périmètre actuellement couvert par l'application inclut :

- gestion des employés ;
- gestion des départements, postes et catégories ;
- gestion des contrats et historiques ;
- gestion des congés, soldes, jours fériés et calendrier ;
- gestion des pointages, présences, retards et absences ;
- configuration et génération de la paie ;
- suivi de paie et caisse ;
- génération et stockage des documents RH ;
- gestion des compétences, formations et matching ;
- audit, archives et permissions ;
- alertes RH ;
- modules IA d'aide à l'analyse.