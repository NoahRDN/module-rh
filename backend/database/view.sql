-- ===========================================
-- 🔍 VUES UTILITAIRES POUR APPLICATION RH
-- ===========================================

-- 1. On supprime l'ancienne version (CASCADE permet de supprimer les dépendances si nécessaire)
DROP VIEW IF EXISTS v_employe_poste CASCADE;

-- 2. On recrée la vue proprement
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
    -- On récupère le nom du contrat directement depuis le résultat du LATERAL
    dernier_contrat.nom_type_contrat AS type_contrat, 
    e.debut AS date_embauche,
    dernier_contrat.date_debut AS contrat_debut,
    dernier_contrat.date_fin AS contrat_fin
FROM employe e
LEFT JOIN personne p ON e.id_personne = p.id_personne
LEFT JOIN genre g ON p.id_genre = g.id_genre
LEFT JOIN ville v ON p.id_ville = v.id_ville
LEFT JOIN poste po ON e.id_poste = po.id_poste
LEFT JOIN departement dep ON po.id_departement = dep.id_departement
LEFT JOIN profil prof ON po.id_profil = prof.id_profil
LEFT JOIN niveau_carriere nc ON prof.id_niveau_carriere = nc.id_niveau_carriere
-- Le LATERAL JOIN récupère le contrat ET le nom du type de contrat d'un coup
LEFT JOIN LATERAL (
    SELECT c.date_debut, c.date_fin, tc.nom as nom_type_contrat
    FROM contrat_employe c
    JOIN type_contrat tc ON c.id_type_contrat = tc.id_type_contrat
    WHERE c.id_employe = e.id_employe
    ORDER BY c.date_debut DESC 
    LIMIT 1
) dernier_contrat ON true;

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


