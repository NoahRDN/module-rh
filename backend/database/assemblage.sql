\c postgres
DROP DATABASE IF EXISTS rh;
CREATE DATABASE rh WITH ENCODING 'UTF8' TEMPLATE template0 LC_COLLATE 'C' LC_CTYPE 'C';
\c rh

CREATE EXTENSION IF NOT EXISTS pgcrypto;

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
   Valide_Par INTEGER REFERENCES Employe(Id_Employe),
   Id_Statut INTEGER NOT NULL REFERENCES Statut(Id_Statut) DEFAULT 1
);

CREATE TABLE Solde_Conge (
   Id_Solde SERIAL PRIMARY KEY,
   Id_Employe INTEGER NOT NULL REFERENCES Employe(Id_Employe) ON DELETE CASCADE,
   Annee INTEGER NOT NULL,
   Total_Acquis NUMERIC(5,2) DEFAULT 30.0,
   Total_Pris NUMERIC(5,2) DEFAULT 0.0,
   Total_Restant NUMERIC(5,2) GENERATED ALWAYS AS (Total_Acquis - Total_Pris) STORED,
   Date_MAJ TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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


-- =============================
-- DONNÉES DE TEST POUR BASE RH
-- Avec ID explicitement définis
-- =============================

-- =============================
-- Genre
-- =============================
INSERT INTO Genre (Id_Genre, Nom) VALUES
(1, 'Masculin'),
(2, 'Féminin');

-- =============================
-- Ville
-- =============================
INSERT INTO Ville (Id_Ville, Nom) VALUES
(1, 'Antananarivo'),
(2, 'Toamasina'),
(3, 'Fianarantsoa');

-- =============================
-- Departement
-- =============================
INSERT INTO Departement (Id_Departement, Nom) VALUES
(1, 'Informatique'),
(2, 'Ressources Humaines'),
(3, 'Finance');

-- =============================
-- Unite
-- =============================
INSERT INTO Unite (Id_Unite, Nom, Niveau) VALUES
(1, 'Direction Générale', 1),
(2, 'Service IT', 2),
(3, 'Service Comptable', 2);

-- =============================
-- Type_Contrat
-- =============================
INSERT INTO Type_Contrat VALUES
(1, 'CDI', 'Contrat à durée indéterminée'),
(2, 'CDD', 'Contrat à durée déterminée'),
(3, 'Stage', 'Contrat de stage');

-- =============================
-- Type_Travail
-- =============================
INSERT INTO Type_Travail VALUES
(1, 'Temps plein', 'Horaire complet'),
(2, 'Temps partiel', 'Horaire réduit');

-- =============================
-- Niveau_Carriere
-- =============================
INSERT INTO Niveau_Carriere VALUES
(1, 'Junior', 'Débutant'),
(2, 'Senior', 'Expérimenté'),
(3, 'Manager', 'Responsable département');

-- =============================
-- Profil
-- =============================
INSERT INTO Profil VALUES
(1, 'Développeur Backend', '2024-01-10', 1, 1, 2),
(2, 'Comptable', '2024-02-05', 1, 1, 1);

-- =============================
-- Personne
-- =============================
INSERT INTO Personne VALUES
(1, 'RANDRIANAH', 'Noah', '1998-05-10', NULL, 1, 1),
(2, 'RASOA', 'Marie', '1995-11-20', NULL, 2, 2);

-- =============================
-- Role
-- =============================
INSERT INTO Role VALUES
(1, 100, 'ADMIN', 'Administrateur système'),
(2, 200, 'EMPLOYE', 'Utilisateur standard');

-- =============================
-- Utilisateur
-- =============================

INSERT INTO Utilisateur
VALUES

(
    1,
    'admin',
    REPLACE(crypt('admin123', gen_salt('bf')), '$2a$', '$2y$'),
    1,
    1
),  -- ADMIN
(
    2,
    'rh_user',
    REPLACE(crypt('rh2025', gen_salt('bf')), '$2a$', '$2y$'),
    2,
    2
);  -- RH

-- =============================
-- Poste
-- =============================
INSERT INTO Poste VALUES
(1, 1, 1, 2, 'Développeur Java', 'Développement backend', 2),
(2, 2, 3, 3, 'Comptable junior', 'Gestion comptable', 1);

-- =============================
-- Poste_Detail
-- =============================
INSERT INTO Poste_Detail VALUES
(1, 1, 'Développement API', 'Créer des services REST'),
(2, 2, 'Tenue des comptes', 'Gérer les bilans mensuels');

-- =============================
-- Employe
-- =============================
INSERT INTO Employe VALUES
(1, '2023-03-01', 1, 1),
(2, '2023-06-01', 2, 2);

-- =============================
-- Contrat_Employe
-- =============================
INSERT INTO Contrat_Employe VALUES
(1, 1, 1, '2023-03-01', NULL),
(2, 2, 2, '2023-06-01', '2024-06-01');

-- =============================
-- Statut
-- =============================
INSERT INTO Statut VALUES
(1, 'EN_ATTENTE', 'En attente de validation'),
(2, 'VALIDE', 'Demande validée'),
(3, 'REFUSE', 'Demande refusée');

-- =============================
-- Type_Conge
-- =============================
INSERT INTO Type_Conge VALUES
(1, 'Congé annuel', 'Congé payé annuel', true, 30),
(2, 'Maladie', 'Congé maladie', true, 15);

-- =============================
-- Conge
-- =============================
INSERT INTO Conge VALUES
(1, 1, 1, '2024-07-01', '2024-07-10', 'Vacances', CURRENT_TIMESTAMP, NULL, 2);

-- =============================
-- Solde_Conge
-- =============================
INSERT INTO Solde_Conge (Id_Solde, Id_Employe, Annee, Total_Acquis, Total_Pris)
VALUES
(1, 1, 2024, 30, 5),
(2, 2, 2024, 30, 0);

-- =============================
-- Presence
-- =============================
INSERT INTO Presence VALUES
(1, 1, '2024-07-15', '08:05', '17:00', 5, 1.5);

-- =============================
-- Document_Employe
-- =============================
INSERT INTO Document_Employe VALUES
(1, 1, 'Contrat', '/docs/contrat_noah.pdf', CURRENT_TIMESTAMP);

-- =============================
-- Parametre_Paie
-- =============================
INSERT INTO Parametre_Paie VALUES
(1, 'CNAPS', 1.0, CURRENT_DATE),
(2, 'IRSA', 20.0, CURRENT_DATE);

-- =============================
-- Fiche_Paie
-- =============================
INSERT INTO Fiche_Paie (Id_Fiche, Id_Employe, Mois, Annee, Salaire_Base, Heures_Supp, Prime, Retenue, CNAPS, OSTIE, IRSA)
VALUES
(1, 1, 7, 2024, 800000, 5, 100000, 50000, 8000, 2000, 160000);

-- =============================
-- Type_Demande
-- =============================
INSERT INTO Type_Demande VALUES
(1, 'Congé', 'Demande de congé'),
(2, 'Paiement', 'Validation de paie');

-- =============================
-- Validation
-- =============================
INSERT INTO Validation VALUES
(1, 1, 1, 2, 1, CURRENT_TIMESTAMP, 'Approuvé');

-- =============================
-- Jour_Ferie
-- =============================
INSERT INTO Jour_Ferie VALUES
(1, 'Fête du Travail', '2024-05-01', true);

-- =============================
-- Fiche_Paie_Parametre
-- =============================
INSERT INTO Fiche_Paie_Parametre VALUES
(1, 1, 8000),
(1, 2, 160000);

-- =============================
-- Mode_Paiement
-- =============================
INSERT INTO Mode_Paiement VALUES
(1, 'Virement bancaire', 'Paiement par banque'),
(2, 'Espèces', 'Paiement en cash');

-- =============================
-- Statut_Paiement
-- =============================
INSERT INTO Statut_Paiement VALUES
(1, 'PAYE', 'Paiement effectué'),
(2, 'EN_ATTENTE', 'Paiement en attente');

-- =============================
-- Paiement
-- =============================
INSERT INTO Paiement VALUES
(1, 1, CURRENT_DATE, 1, 1, 862000);


-- ===========================================
-- 🔍 VUES UTILITAIRES POUR APPLICATION RH
-- ===========================================

-- 1️⃣ VUE FICHE EMPLOYÉ COMPLÈTE
-- Regroupe les infos essentielles d’un employé :
-- identité, poste, profil, département, contrat.

CREATE OR REPLACE VIEW v_employe_poste AS
SELECT
    e.id_employe,
    p.nom AS nom,
    p.prenom AS prenom,
    p.date_naissance,
    g.nom AS genre,
    v.nom AS ville,
    dep.nom AS departement,
    po.fonction,
    prof.nom AS profil,
    nc.nom AS niveau_carriere,
    tc.nom AS type_contrat,
    e.debut AS date_embauche,
    c.date_debut AS contrat_debut,
    c.date_fin AS contrat_fin,
    u.identifiant AS utilisateur
FROM employe e
LEFT JOIN personne p ON e.id_personne = p.id_personne
LEFT JOIN genre g ON p.id_genre = g.id_genre
LEFT JOIN ville v ON p.id_ville = v.id_ville
LEFT JOIN poste po ON e.id_poste = po.id_poste
LEFT JOIN departement dep ON po.id_departement = dep.id_departement
LEFT JOIN profil prof ON po.id_profil = prof.id_profil
LEFT JOIN niveau_carriere nc ON prof.id_niveau_carriere = nc.id_niveau_carriere
LEFT JOIN type_contrat tc ON prof.id_type_contrat = tc.id_type_contrat
LEFT JOIN utilisateur u ON p.id_personne = u.id_personne
LEFT JOIN contrat_employe c ON c.id_employe = e.id_employe
ORDER BY e.id_employe;


-- 2️⃣ VUE FICHE DE PAIE DÉTAILLÉE
-- Permet de générer rapidement un état de paie complet pour chaque employé.

CREATE OR REPLACE VIEW v_fiche_paie_detaillee AS
SELECT
    fp.id_fiche,
    e.id_employe,
    p.nom || ' ' || p.prenom AS employe,
    dep.nom AS departement,
    po.fonction,
    fp.mois,
    fp.annee,
    fp.salaire_base,
    fp.heures_supp,
    fp.prime,
    fp.retenue,
    fp.cnaps,
    fp.ostie,
    fp.irsa,
    fp.net_a_payer,
    fp.date_generation
FROM fiche_paie fp
JOIN employe e ON fp.id_employe = e.id_employe
LEFT JOIN poste po ON e.id_poste = po.id_poste
LEFT JOIN departement dep ON po.id_departement = dep.id_departement
LEFT JOIN personne p ON e.id_personne = p.id_personne
ORDER BY fp.annee DESC, fp.mois DESC;


-- 3️⃣ VUE CONGÉS ACTIFS
-- Suivi simplifié des congés approuvés, en attente ou refusés.

CREATE OR REPLACE VIEW v_conge_actif AS
SELECT
    c.id_conge,
    e.id_employe,
    p.nom || ' ' || p.prenom AS employe,
    tc.nom AS type_conge,
    c.date_debut,
    c.date_fin,
    s.nom AS statut,
    c.commentaire,
    c.date_demande,
    v.nom AS ville,
    dep.nom AS departement
FROM conge c
JOIN employe e ON c.id_employe = e.id_employe
JOIN personne p ON e.id_personne = p.id_personne
LEFT JOIN poste po ON e.id_poste = po.id_poste
LEFT JOIN departement dep ON po.id_departement = dep.id_departement
JOIN type_conge tc ON c.id_type_conge = tc.id_type_conge
JOIN statut s ON c.id_statut = s.id_statut
LEFT JOIN ville v ON p.id_ville = v.id_ville
ORDER BY c.date_demande DESC;


-- 4️⃣ VUE SOLDE CONGÉS GLOBAL
-- Combine le solde et les congés pris par employé pour affichage rapide.

CREATE OR REPLACE VIEW v_solde_conge_employe AS
SELECT
    s.id_solde,
    e.id_employe,
    p.nom || ' ' || p.prenom AS employe,
    s.annee,
    s.total_acquis,
    s.total_pris,
    s.total_restant,
    COALESCE(SUM(
        CASE
            WHEN stat.nom = 'Approuvé' THEN (c.date_fin - c.date_debut)
            ELSE 0
        END
    ), 0) AS jours_conges_pris
FROM solde_conge s
JOIN employe e ON s.id_employe = e.id_employe
JOIN personne p ON e.id_personne = p.id_personne
LEFT JOIN conge c
    ON c.id_employe = e.id_employe
    AND EXTRACT(YEAR FROM c.date_debut) = s.annee
LEFT JOIN statut stat ON c.id_statut = stat.id_statut
GROUP BY s.id_solde, e.id_employe, p.nom, p.prenom, s.annee, s.total_acquis, s.total_pris, s.total_restant
ORDER BY s.annee DESC;


CREATE OR REPLACE VIEW v_utilisateur_role AS
SELECT
    u.id_utilisateur,
    u.identifiant,
    p.nom || ' ' || p.prenom AS nom_complet,
    r.nom AS role,
    r.description AS role_description
FROM utilisateur u
LEFT JOIN personne p ON u.id_personne = p.id_personne
LEFT JOIN role r ON u.id_role = r.id_role
ORDER BY u.id_utilisateur;


CREATE OR REPLACE VIEW v_validation_statut_actuel AS
SELECT
    v.Id_Type_Demande,
    v.Id_Reference,
    td.Nom AS Type_Demande,
    s.Nom AS Statut_Actuel,
    v.Date_Action,
    u.Identifiant AS Valide_Par
FROM validation v
JOIN statut s ON v.id_statut = s.id_statut
JOIN type_demande td ON v.id_type_demande = td.id_type_demande
LEFT JOIN utilisateur u ON v.valide_par = u.id_utilisateur
WHERE v.date_action = (
    SELECT MAX(v2.date_action)
    FROM validation v2
    WHERE v2.id_reference = v.id_reference
      AND v2.id_type_demande = v.id_type_demande
);


CREATE OR REPLACE VIEW v_paiement_detaille AS
SELECT
   p.Id_Paiement,
   f.Id_Fiche,
   e.Id_Employe,
   per.Nom || ' ' || per.Prenom AS Employe,
   mp.Nom AS Mode_Paiement,
   sp.Nom AS Statut_Paiement,
   p.Montant,
   p.Date_Paiement
FROM Paiement p
JOIN Fiche_Paie f ON p.Id_Fiche = f.Id_Fiche
JOIN Employe e ON f.Id_Employe = e.Id_Employe
JOIN Personne per ON e.Id_Personne = per.Id_Personne
JOIN Mode_Paiement mp ON p.Id_Mode_Paiement = mp.Id_Mode_Paiement
JOIN Statut_Paiement sp ON p.Id_Statut_Paiement = sp.Id_Statut_Paiement;


DO $$
DECLARE
    max_id INTEGER;
    seq_name TEXT;
    tbl RECORD;
BEGIN
    FOR tbl IN
        SELECT table_name, column_name
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND column_default LIKE 'nextval%'
    LOOP
        BEGIN
            -- Récupérer le MAX(id) + 1
            EXECUTE format(
                'SELECT COALESCE(MAX(%I), 0) + 1 FROM %I',
                tbl.column_name, tbl.table_name
            ) INTO max_id;

            -- Récupérer le nom de la séquence
            SELECT regexp_replace(column_default, 'nextval\(''(.*)''::regclass\)', '\1')
            INTO seq_name
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND table_name = tbl.table_name
              AND column_name = tbl.column_name;

            -- Mettre à jour la séquence
            IF seq_name IS NOT NULL AND max_id IS NOT NULL THEN
                EXECUTE format('ALTER SEQUENCE %I RESTART WITH %s', seq_name, max_id);
                RAISE NOTICE 'Séquence % mise à jour à %', seq_name, max_id;
            END IF;

        EXCEPTION WHEN OTHERS THEN
            RAISE NOTICE 'Erreur pour %.% : %', tbl.table_name, tbl.column_name, SQLERRM;
        END;
    END LOOP;
END $$;
