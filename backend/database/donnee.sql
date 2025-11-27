-- ==========================
-- DONNÉES DE BASE
-- ==========================

INSERT INTO Genre (Nom) VALUES ('Homme'), ('Femme');

INSERT INTO Ville (Nom) VALUES
('Antananarivo'),
('Toamasina'),
('Antsirabe'),
('Fianarantsoa');

INSERT INTO Personne (Nom, Prenom, Date_Naissance, Image, Id_Ville, Id_Genre) VALUES
('Rakoto', 'Admin', '1988-05-12', NULL, 1, 1),
('Randria', 'Hanitra', '1990-03-08', NULL, 2, 2),
('Andrianina', 'Jean', '1985-01-22', NULL, 1, 1),
('Rasoanirina', 'Tiana', '1995-11-04', NULL, 3, 2);

INSERT INTO Type_Contrat (Nom, Description) VALUES
('CDI', 'Contrat a duree indeterminee'),
('CDD', 'Contrat a duree determinee'),
('Stage', 'Periode d apprentissage'),
('Interim', 'Travail temporaire');

INSERT INTO Type_Travail (Nom, Description) VALUES
('Temps plein', 'Travail a temps complet'),
('Temps partiel', 'Travail a duree reduite');

INSERT INTO Niveau_Carriere (Nom, Description) VALUES
('Ouvrier', 'Taches manuelles ou techniques'),
('Employe', 'Taches administratives ou commerciales'),
('TAM', 'Technicien ou Agent de Maitrise'),
('Cadre', 'Responsabilite de gestion'),
('Dirigeant', 'Decisions strategiques');

INSERT INTO Type_Conge (Nom, Description, Est_Paye, Duree_Max_Jours) VALUES
('Conge Paye', 'Repos annuel remunere', TRUE, 30),
('Conge Maladie', 'Arret medical', TRUE, 60),
('Conge Sans Solde', 'Absence non remuneree', FALSE, 90),
('Conge Exceptionnel', 'Evenement familial', TRUE, 5),
('Conge Maternite', 'Naissance d un enfant', TRUE, 90);

INSERT INTO Role (Code, Nom, Description) VALUES
(1, 'Employe', 'Acces limite a sa fiche et conges'),
(2, 'RH', 'Gere le personnel et la paie'),
(3, 'DG', 'Valide et supervise'),
(4, 'Admin', 'Tous les droits systeme');

INSERT INTO Utilisateur (Identifiant, Mdp, Id_Personne, Id_Role)
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
),  -- RH
(
    'dg_user',
    REPLACE(crypt('dg2025', gen_salt('bf')), '$2a$', '$2y$'),
    3,
    3
),  -- DG
(
    'emp1',
    REPLACE(crypt('emp1', gen_salt('bf')), '$2a$', '$2y$'),
    4,
    1
); -- EMPLOYE

INSERT INTO Jour_Ferie (Nom, Date_Jour) VALUES
('Nouvel An', '2025-01-01'),
('Fete du Travail', '2025-05-01'),
('Independance', '2025-06-26'),
('Assomption', '2025-08-15'),
('Noel', '2025-12-25');

INSERT INTO Type_Demande (Nom, Description) VALUES
('Conge', 'Demande de conge d un employe'),
('Fiche_Paie', 'Validation d une fiche de paie'),
('Contrat', 'Approbation de contrat'),
('Autre', 'Autre type de validation');

INSERT INTO Statut (Nom, Description) VALUES
('En attente', 'Demande soumise mais non encore traitee'),
('Approuve', 'Demande acceptee'),
('Refuse', 'Demande rejetee'),
('Annule', 'Demande annulee par l auteur');

INSERT INTO Mode_Paiement (Nom, Description) VALUES
('Virement', 'Transfert bancaire vers le compte du salarie'),
('Especes', 'Paiement en liquide'),
('Cheque', 'Cheque physique emis par l entreprise'),
('Mobile Money', 'Paiement via service mobile comme MVola ou Orange Money');

INSERT INTO Statut_Paiement (Nom, Description) VALUES
('En attente', 'Paiement prevu mais non encore effectue'),
('Effectue', 'Paiement execute avec succes'),
('Annule', 'Paiement annule avant execution'),
('Echoue', 'Paiement tente mais non valide');
