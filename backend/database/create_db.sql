\c postgres
DROP DATABASE IF EXISTS rh;
CREATE DATABASE rh;
\c rh

CREATE TABLE Genre(
   Id_Genre SERIAL PRIMARY KEY,
   Nom VARCHAR(50) NOT NULL
);

CREATE TABLE Ville(
   Id_Ville SERIAL PRIMARY KEY,
   Nom VARCHAR(100) NOT NULL
);

CREATE TABLE Departement(
   Id_Departement SERIAL PRIMARY KEY,
   Nom VARCHAR(100) NOT NULL
);

CREATE TABLE Unite(
   Id_Unite SERIAL PRIMARY KEY,
   Nom VARCHAR(100),
   Niveau INTEGER
);

CREATE TABLE Type_Contrat (
   Id_Type_Contrat SERIAL PRIMARY KEY,
   Nom VARCHAR(100) NOT NULL,
   Description TEXT
);

CREATE TABLE Type_Travail (
   Id_Type_Travail SERIAL PRIMARY KEY,
   Nom VARCHAR(100) NOT NULL,
   Description TEXT
);

CREATE TABLE Niveau_Carriere (
   Id_Niveau_Carriere SERIAL PRIMARY KEY,
   Nom VARCHAR(100) NOT NULL,
   Description TEXT
);

CREATE TABLE Profil(
   Id_Profil SERIAL PRIMARY KEY,
   Nom VARCHAR(50) NOT NULL,
   Date_Derniere_Postulation DATE,
   Id_Type_Contrat INTEGER REFERENCES Type_Contrat(Id_Type_Contrat),
   Id_Type_Travail INTEGER REFERENCES Type_Travail(Id_Type_Travail),
   Id_Niveau_Carriere INTEGER REFERENCES Niveau_Carriere(Id_Niveau_Carriere)
);

CREATE TABLE Personne(
   Id_Personne SERIAL PRIMARY KEY,
   Nom VARCHAR(100) NOT NULL,
   Prenom VARCHAR(100) NOT NULL,
   Date_Naissance DATE NOT NULL,
   Image VARCHAR(255),
   Id_Ville INTEGER REFERENCES Ville(Id_Ville),
   Id_Genre INTEGER REFERENCES Genre(Id_Genre)
);

CREATE TABLE Role (
   Id_Role SERIAL PRIMARY KEY,
   Code INTEGER UNIQUE NOT NULL,
   Nom VARCHAR(50) UNIQUE NOT NULL,
   Description TEXT
);

CREATE TABLE Utilisateur(
   Id_Utilisateur SERIAL PRIMARY KEY,
   Identifiant VARCHAR(255) UNIQUE NOT NULL,
   Mdp VARCHAR(255) NOT NULL,
   Id_Personne INTEGER UNIQUE REFERENCES Personne(Id_Personne),
   Id_Role INTEGER REFERENCES Role(Id_Role)
);

CREATE TABLE Poste(
   Id_Poste SERIAL PRIMARY KEY,
   Id_Profil INTEGER NOT NULL REFERENCES Profil(Id_Profil),
   Id_Departement INTEGER NOT NULL REFERENCES Departement(Id_Departement),
   Id_Unite INTEGER REFERENCES Unite(Id_Unite),
   Fonction VARCHAR(255),
   Description TEXT,
   Nombre INTEGER
);

CREATE TABLE Poste_Detail (
    Id_Poste_Detail SERIAL PRIMARY KEY,
    Id_Poste INTEGER NOT NULL REFERENCES Poste(Id_Poste) ON DELETE CASCADE,
    Description TEXT,
    Objectif TEXT
);

CREATE TABLE Employe(
   Id_Employe SERIAL PRIMARY KEY,
   Debut DATE NOT NULL,
   Id_Poste INTEGER NOT NULL REFERENCES Poste(Id_Poste),
   Id_Personne INTEGER REFERENCES Personne(Id_Personne)
);

CREATE TABLE Contrat_Employe (
    Id_Contrat_Employe SERIAL PRIMARY KEY,
    Id_Employe INTEGER NOT NULL REFERENCES Employe(Id_Employe) ON DELETE CASCADE,
    Id_Type_Contrat INTEGER NOT NULL REFERENCES Type_Contrat(Id_Type_Contrat),
    Date_Debut DATE NOT NULL,
    Date_Fin DATE
);

CREATE TABLE Poste_Historique (
    Id_Historique SERIAL PRIMARY KEY,
    Id_Poste INTEGER NOT NULL REFERENCES Poste(Id_Poste) ON DELETE CASCADE,
    Id_Employe INTEGER REFERENCES Employe(Id_Employe),
    Action VARCHAR(255) NOT NULL,
    Description TEXT,
    Date_Action TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Statut (
    Id_Statut SERIAL PRIMARY KEY,
    Nom VARCHAR(30) UNIQUE NOT NULL,
    Description TEXT
);

CREATE TABLE Type_Conge (
   Id_Type_Conge SERIAL PRIMARY KEY,
   Nom VARCHAR(100) NOT NULL,
   Description TEXT,
   Est_Paye BOOLEAN DEFAULT TRUE,
   Duree_Max_Jours INTEGER
);

CREATE TABLE Conge (
   Id_Conge SERIAL PRIMARY KEY,
   Id_Employe INTEGER NOT NULL REFERENCES Employe(Id_Employe) ON DELETE CASCADE,
   Id_Type_Conge INTEGER NOT NULL REFERENCES Type_Conge(Id_Type_Conge),
   Date_Debut DATE NOT NULL,
   Date_Fin DATE NOT NULL,
   Commentaire TEXT,
   Date_Demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   Valide_Par INTEGER REFERENCES Employe(Id_Employe)
);

CREATE TABLE Solde_Conge (
   Id_Solde SERIAL PRIMARY KEY,
   Id_Employe INTEGER NOT NULL REFERENCES Employe(Id_Employe) ON DELETE CASCADE,
   Annee INTEGER NOT NULL,
   Total_Acquis NUMERIC(5,2) DEFAULT 30.0,
   Total_Pris NUMERIC(5,2) DEFAULT 0.0,
   Total_Restant NUMERIC(5,2) GENERATED ALWAYS AS (Total_Acquis - Total_Pris) STORED,
   Date_MAJ TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
);

CREATE TABLE Presence (
   Id_Presence SERIAL PRIMARY KEY,
   Id_Employe INTEGER NOT NULL REFERENCES Employe(Id_Employe) ON DELETE CASCADE,
   Date_Jour DATE NOT NULL,
   Heure_Entree TIME,
   Heure_Sortie TIME,
   Retard_Minutes INTEGER DEFAULT 0,
   Heures_Supplementaires NUMERIC(5,2) DEFAULT 0.0
);

CREATE TABLE Document_Employe (
   Id_Document SERIAL PRIMARY KEY,
   Id_Employe INTEGER NOT NULL REFERENCES Employe(Id_Employe) ON DELETE CASCADE,
   Type_Document VARCHAR(100),
   Chemin_Fichier TEXT,
   Date_Ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Parametre_Paie (
   Id_Param SERIAL PRIMARY KEY,
   Libelle VARCHAR(50),
   Taux NUMERIC(5,2),
   Date_Effet DATE DEFAULT CURRENT_DATE
);

CREATE TABLE Fiche_Paie (
   Id_Fiche SERIAL PRIMARY KEY,
   Id_Employe INTEGER NOT NULL REFERENCES Employe(Id_Employe) ON DELETE CASCADE,
   Mois SMALLINT CHECK (Mois BETWEEN 1 AND 12),
   Annee SMALLINT,
   Salaire_Base NUMERIC(12,2),
   Heures_Supp NUMERIC(5,2),
   Prime NUMERIC(12,2),
   Retenue NUMERIC(12,2),
   CNAPS NUMERIC(12,2),
   OSTIE NUMERIC(12,2),
   IRSA NUMERIC(12,2),
   Net_A_Payer NUMERIC(12,2) GENERATED ALWAYS AS (
       Salaire_Base + Prime + (Heures_Supp * 1000)
       - (Retenue + CNAPS + OSTIE + IRSA)
   ) STORED,
   Date_Generation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Type_Demande (
    Id_Type_Demande SERIAL PRIMARY KEY,
    Nom VARCHAR(50) UNIQUE NOT NULL,
    Description TEXT
);

CREATE TABLE Validation (
    Id_Validation SERIAL PRIMARY KEY,
    Id_Type_Demande INTEGER NOT NULL REFERENCES Type_Demande(Id_Type_Demande),
    Id_Reference INTEGER NOT NULL, -- référence à Id_Conge, Id_Fiche_Paie, etc.
    Id_Statut INTEGER NOT NULL REFERENCES Statut(Id_Statut),
    Valide_Par INTEGER REFERENCES Utilisateur(Id_Utilisateur),
    Date_Action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Commentaire TEXT
);

CREATE TABLE Jour_Ferie (
    Id_Jour SERIAL PRIMARY KEY,
    Nom VARCHAR(100),
    Date_Jour DATE UNIQUE NOT NULL,
    Repetition_Annuelle BOOLEAN DEFAULT TRUE 
);

CREATE TABLE Fiche_Paie_Parametre (
   Id_Fiche INTEGER REFERENCES Fiche_Paie(Id_Fiche) ON DELETE CASCADE,
   Id_Param INTEGER REFERENCES Parametre_Paie(Id_Param),
   Valeur NUMERIC(12,2), -- taux appliqué lors de la génération
   PRIMARY KEY (Id_Fiche, Id_Param)
);

CREATE TABLE Mode_Paiement (
   Id_Mode_Paiement SERIAL PRIMARY KEY,
   Nom VARCHAR(50) UNIQUE NOT NULL,
   Description TEXT
);

CREATE TABLE Statut_Paiement (
   Id_Statut_Paiement SERIAL PRIMARY KEY,
   Nom VARCHAR(30) UNIQUE NOT NULL,
   Description TEXT
);

CREATE TABLE Paiement (
   Id_Paiement SERIAL PRIMARY KEY,
   Id_Fiche INTEGER NOT NULL REFERENCES Fiche_Paie(Id_Fiche) ON DELETE CASCADE,
   Date_Paiement DATE DEFAULT CURRENT_DATE,
   Id_Mode_Paiement INTEGER NOT NULL REFERENCES Mode_Paiement(Id_Mode_Paiement),
   Id_Statut_Paiement INTEGER NOT NULL REFERENCES Statut_Paiement(Id_Statut_Paiement),
   Montant NUMERIC(12,2) NOT NULL
);


