import api from './api'

/**
 * Service pour le portail Manager
 */
export const managerService = {
  /**
   * Récupère le tableau de bord manager
   */
  getDashboard() {
    return api.get('/v1/manager/dashboard')
  },

  /**
   * Récupère les employés de l'équipe
   */
  getEquipe() {
    return api.get('/v1/manager/equipe')
  },

  /**
   * Récupère les demandes de congés de l'équipe
   */
  getDemandesConges(statut = null) {
    const params = statut ? { statut } : {}
    return api.get('/v1/manager/demandes-conges', { params })
  },

  /**
   * Récupère les demandes RH de l'équipe
   */
  getDemandesRH(statut = null) {
    const params = statut ? { statut } : {}
    return api.get('/v1/manager/demandes-rh', { params })
  },

  /**
   * Valide une demande de congé
   */
  validerDemandeConge(id, commentaire = null) {
    return api.post(`/v1/manager/demandes-conges/${id}/valider`, { commentaire })
  },

  /**
   * Rejette une demande de congé
   */
  rejeterDemandeConge(id, commentaire) {
    return api.post(`/v1/manager/demandes-conges/${id}/rejeter`, { commentaire })
  },

  /**
   * Valide une demande RH
   */
  validerDemandeRH(id, commentaire = null) {
    return api.post(`/v1/manager/demandes-rh/${id}/valider`, { commentaire })
  },

  /**
   * Statistiques d'absences de l'équipe
   */
  getStatistiquesAbsences(mois = null) {
    const params = mois ? { mois } : {}
    return api.get('/v1/manager/statistiques/absences', { params })
  },

  /**
   * Statistiques de performance de l'équipe
   */
  getStatistiquesPerformance() {
    return api.get('/v1/manager/statistiques/performance')
  },

  /**
   * Calendrier des absences de l'équipe
   */
  getCalendrierAbsences(debut, fin) {
    return api.get('/v1/manager/calendrier-absences', { params: { debut, fin } })
  }
}

export default managerService
