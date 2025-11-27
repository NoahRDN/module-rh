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