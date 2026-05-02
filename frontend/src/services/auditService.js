import api, { getCachedApi, prefetchNextPage } from './api'

/**
 * Service pour la gestion des audits
 */
export const auditService = {
  /**
   * Récupère les logs d'audit avec filtres
   */
  getLogs(filters = {}) {
    return getCachedApi('/v1/audit', { params: filters })
  },

  /**
   * Précharge la page suivante des logs d'audit
   */
  prefetchLogs(filters = {}, pagination = {}) {
    return prefetchNextPage('/v1/audit', filters, pagination)
  },

  /**
   * Récupère un log spécifique
   */
  getLog(id) {
    return api.get(`/v1/audit/${id}`)
  },

  /**
   * Récupère les statistiques d'audit
   */
  getStatistiques(period = 'week') {
    return api.get('/v1/audit/statistiques', { params: { period } })
  },

  /**
   * Récupère les actions disponibles
   */
  getActions() {
    return api.get('/v1/audit/actions')
  },

  /**
   * Récupère les types d'entités auditées
   */
  getTypes() {
    return api.get('/v1/audit/types')
  },

  /**
   * Récupère les utilisateurs ayant des logs
   */
  getUsers() {
    return api.get('/v1/audit/users')
  },

  /**
   * Exporte les logs en CSV
   */
  exportCsv(filters = {}) {
    return api.get('/v1/audit/export', { 
      params: filters,
      responseType: 'blob'
    })
  },

  /**
   * Récupère l'historique d'une entité spécifique
   */
  getEntityHistory(type, id) {
    return api.get('/v1/audit/entity-history', { params: { type, id } })
  }
}

export default auditService
