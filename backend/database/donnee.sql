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
('CDI', 'Contrat à durée indéterminée'),
('CDD', 'Contrat à durée déterminée'),
('Stage', 'Période d’apprentissage'),
('Intérim', 'Travail temporaire');

INSERT INTO Type_Travail (Nom, Description) VALUES
('Temps plein', 'Travail à temps complet'),
('Temps partiel', 'Travail à durée réduite');

INSERT INTO Niveau_Carriere (Nom, Description) VALUES
('Ouvrier', 'Tâches manuelles ou techniques'),
('Employé', 'Tâches administratives ou commerciales'),
('TAM', 'Technicien ou Agent de Maîtrise'),
('Cadre', 'Responsabilité de gestion'),
('Dirigeant', 'Décisions stratégiques');

INSERT INTO Type_Conge (Nom, Description, Est_Paye, Duree_Max_Jours) VALUES
('Congé Payé', 'Repos annuel rémunéré', TRUE, 30),
('Congé Maladie', 'Arrêt médical', TRUE, 60),
('Congé Sans Solde', 'Absence non rémunérée', FALSE, 90),
('Congé Exceptionnel', 'Événement familial', TRUE, 5),
('Congé Maternité', 'Naissance d’un enfant', TRUE, 90);

INSERT INTO Role (Code, Nom, Description) VALUES
(1, 'Employe', 'Accès limité à sa fiche et congés'),
(2, 'RH', 'Gère le personnel et la paie'),
(3, 'DG', 'Valide et supervise'),
(4, 'Admin', 'Tous les droits système');

INSERT INTO Utilisateur (Identifiant, Mdp, Id_Personne, Id_Role)
VALUES
(
    'admin',
    REPLACE(crypt('admin123', gen_salt('bf')), '$2a$', '$2y$'),
    1,
    4
),  -- ADMIN
(
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
('Fête du Travail', '2025-05-01'),
('Indépendance', '2025-06-26'),
('Assomption', '2025-08-15'),
('Noël', '2025-12-25');

INSERT INTO Type_Demande (Nom, Description) VALUES
('Conge', 'Demande de congé d’un employé'),
('Fiche_Paie', 'Validation d’une fiche de paie'),
('Contrat', 'Approbation de contrat'),
('Autre', 'Autre type de validation');

INSERT INTO Statut (Nom, Description) VALUES
('En attente', 'Demande soumise mais non encore traitée'),
('Approuvé', 'Demande acceptée'),
('Refusé', 'Demande rejetée'),
('Annulé', 'Demande annulée par l’auteur');

INSERT INTO Mode_Paiement (Nom, Description) VALUES
('Virement', 'Transfert bancaire vers le compte du salarié'),
('Espèces', 'Paiement en liquide'),
('Chèque', 'Chèque physique émis par l’entreprise'),
('Mobile Money', 'Paiement via service mobile comme MVola ou Orange Money');

INSERT INTO Statut_Paiement (Nom, Description) VALUES
('En attente', 'Paiement prévu mais non encore effectué'),
('Effectue', 'Paiement exécuté avec succès'),
('Annule', 'Paiement annulé avant exécution'),
('Echoue', 'Paiement tenté mais non validé');
