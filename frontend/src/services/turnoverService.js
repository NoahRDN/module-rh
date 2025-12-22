import api from './api'

/**
 * Service pour la prédiction de turnover et analyse RH
 */
const turnoverService = {
  /**
   * Analyser tous les employés pour le risque de turnover
   * @returns {Promise}
   */
  analyserTous() {
    return api.get('/v1/turnover')
  },

  /**
   * Analyser un employé spécifique
   * @param {number} employeId - ID de l'employé
   * @returns {Promise}
   */
  analyserEmploye(employeId) {
    return api.get(`/v1/turnover/employes/${employeId}`)
  },

  /**
   * Obtenir les employés à haut risque
   * @param {number} limit - Nombre maximum de résultats
   * @returns {Promise}
   */
  getTopRisques(limit = 10) {
    return api.get('/v1/turnover/top-risques', { params: { limit } })
  },

  /**
   * Obtenir les statistiques globales de turnover
   * @returns {Promise}
   */
  getStatistiques() {
    return api.get('/v1/turnover/statistiques')
  },

  /**
   * Obtenir les alertes de turnover
   * @param {string} niveau - Niveau minimum d'alerte (critique, eleve, modere)
   * @returns {Promise}
   */
  getAlertes(niveau = 'modere') {
    return api.get('/v1/turnover/alertes', { params: { niveau } })
  },

  /**
   * Obtenir les risques par département
   * @returns {Promise}
   */
  getParDepartement() {
    return api.get('/v1/turnover/departements')
  },

  /**
   * Obtenir les tendances de turnover
   * @param {number} mois - Nombre de mois d'historique
   * @returns {Promise}
   */
  getTendances(mois = 12) {
    return api.get('/v1/turnover/tendances', { params: { mois } })
  }
}

export default turnoverService
