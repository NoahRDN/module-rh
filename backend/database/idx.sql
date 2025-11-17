CREATE INDEX idx_employe_poste ON Employe(Id_Poste);
CREATE INDEX idx_conge_statut ON Conge(Statut);
CREATE INDEX idx_fiche_paie_employe ON Fiche_Paie(Id_Employe);
CREATE INDEX idx_fiche_paie_mois_annee ON Fiche_Paie(Mois, Annee);
