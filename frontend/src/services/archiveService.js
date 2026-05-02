import api, { getCachedApi, prefetchNextPage } from './api'

/**
 * Service pour la gestion des archives
 */
export const archiveService = {
  // ========================================
  // PARAMÈTRES D'ARCHIVAGE
  // ========================================

  /**
   * Récupère les paramètres d'archivage
   */
  getSettings() {
    return api.get('/v1/archives/settings')
  },

  /**
   * Récupère un paramètre spécifique
   */
  getSetting(id) {
    return api.get(`/v1/archives/settings/${id}`)
  },

  /**
   * Met à jour un paramètre
   */
  updateSetting(id, data) {
    return api.put(`/v1/archives/settings/${id}`, data)
  },

  // ========================================
  // DOCUMENTS ARCHIVÉS
  // ========================================

  /**
   * Récupère les documents archivés
   */
  getDocuments(filters = {}) {
    return getCachedApi('/v1/archives', { params: filters })
  },

  /**
   * Précharge la page suivante des documents archivés
   */
  prefetchDocuments(filters = {}, pagination = {}) {
    return prefetchNextPage('/v1/archives', filters, pagination)
  },

  /**
   * Récupère un document archivé
   */
  getDocument(id) {
    return api.get(`/v1/archives/${id}`)
  },

  /**
   * Archive un document
   */
  archiveDocument(data) {
    return api.post('/v1/archives', data)
  },

  /**
   * Télécharge un document archivé
   */
  downloadDocument(id) {
    return api.get(`/v1/archives/${id}/download`, { responseType: 'blob' })
  },

  /**
   * Ajoute une note à un document
   */
  addNote(id, notes) {
    return api.post(`/v1/archives/${id}/note`, { notes })
  },

  // ========================================
  // STATISTIQUES ET MAINTENANCE
  // ========================================

  /**
   * Récupère les statistiques d'archivage
   */
  getStatistiques() {
    return api.get('/v1/archives/statistiques')
  },

  /**
   * Récupère les types de documents
   */
  getTypes() {
    return api.get('/v1/archives/types')
  },

  /**
   * Récupère les documents expirant bientôt
   */
  getExpiringDocuments(days = 30) {
    return api.get('/v1/archives/expiring', { params: { days } })
  },

  /**
   * Récupère les documents expirés
   */
  getExpiredDocuments() {
    return api.get('/v1/archives/expired')
  },

  /**
   * Traite les documents expirés
   */
  processExpired() {
    return api.post('/v1/archives/process-expired')
  },

  /**
   * Vérifie l'intégrité des archives
   */
  verifyIntegrity() {
    return api.post('/v1/archives/verify-integrity')
  }
}

export default archiveService
