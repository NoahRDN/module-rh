-- =================================================================================================
-- 🛑 A NE PAS COPIER DANS LA BASE DE DONNEES NORMALE - JUSTE POUR TESTS (CHACUN PEUT METTRE SES TESTS)
-- =================================================================================================

-- 1. D'abord, on s'assure qu'il y a des contrats pour que le code trouve les dates
-- Contrat 1 : CDD en 2021 (Fini)
INSERT INTO Contrat_Employe (Id_Employe, Id_Type_Contrat, Date_Debut, Date_Fin) 
VALUES (1, 1, '2021-01-01', '2021-12-31');

-- Contrat 2 : CDI depuis 2022 (En cours -> Date_Fin est NULL)
INSERT INTO Contrat_Employe (Id_Employe, Id_Type_Contrat, Date_Debut, Date_Fin) 
VALUES (1, 2, '2022-01-01', NULL);


-- 2. Ensuite, voici les données de POSTE_HISTORIQUE pour tester l'affichage
-- Cas A : L'embauche initiale (Correspondra au CDD)
INSERT INTO Poste_Historique (Id_Poste, Id_Employe, Action, Description, Date_Action) 
VALUES 
(1, 1, 'Embauche', 'Recrutement au poste Junior', '2021-01-15 09:00:00');

-- Cas B : Passage en CDI (Même poste, mais nouvelle date qui doit matcher le contrat CDI)
INSERT INTO Poste_Historique (Id_Poste, Id_Employe, Action, Description, Date_Action) 
VALUES 
(1, 1, 'Titularisation', 'Passage en CDI suite à fin de période probatoire', '2022-01-01 09:00:00');

-- Cas C : Promotion (Nouveau poste, toujours sous le contrat CDI)
INSERT INTO Poste_Historique (Id_Poste, Id_Employe, Action, Description, Date_Action) 
VALUES 
(2, 1, 'Promotion', 'Passage au grade Senior', '2023-06-10 14:00:00');