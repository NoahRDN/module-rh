ce module RH a plusieurs sous fonctionnalités comme ce qui est déja seaprer dans le menu.

dashboard: 
  - présente divers statistique utile pour dans le platforme


concernant l'ajout employé:
- une liste d'employé est présent pour savoir: 
  - nom, numero de matricule, poste, catégorie, departement, status
  - possibilité d'avoir une fiche employé aussi pour avoir plus d'information sur l'employé
- possibilité d'ajouter des employés aussi
  - un employé ajouter n'a pas encore de contrat ni de poste donc après création de cette employé, cette employé est encore inactif
  - il faut associer cette employé à un contrat et puis au poste après pour qu'il soit actif 
- concernant les poste, ils sont associés a des départemeent et catégorie de poste
- concernant le contrat, il est possible de clore un contrat, renouveller le contrat 
  - c'est seuelement les employés qui n'ont pas encore de contrat qu'on peut creer de contrat 
  - pour changer le contrat d'un employé, il faut le clore d'abord avant d'en créer un nouveau pour lui
- concernant le type de congés:
  - un congé peut être payant ou non
  - un congé peut avoir des limites par different type de fréquence 
  - un quota limite en jours ou non 
  - cumulable ou non
- concernant le solde congés:
  - liste des congés des employés avec leur type, coquat en possesion, utilise et restant et solde par période
- concernant le calendrier:
  - c'est pour avoir une vue des différents jours où les employés pourrait être absent
    - congés, absences, fériés, RH (toute evenement qui n'est pas l'un des trois premier est automatiquement RH)
- concernant alerte
  - possibilité d'avoir plusieurs alerte en activant les types d'alerte qu'on veut utilisé.
    - Absences maladie fréquentes: Seuil numérique, Fenêtre d'analyse, Niveau de criticité
    - Congé imminent non validé:
      - Seuil en jours, Niveau de criticité
    - Congés exceptionnels fréquents
      - Nombre de congés exceptionnels, Nombre de jours utilisés pour le calcul, Niveau de criticité
    - Alerte si plus de X jours de congés non pris:
      - Nombre minimum de jours non pris, Niveau de criticité
    - Demande en attente
      - Seuil en jours, Niveau de criticité
    - Fin de contrat proche
      - Seuil en jours, Niveau de criticité
- parametre de ces alerte
  - 
- 