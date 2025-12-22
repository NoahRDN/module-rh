import api from './api'

const BASE_URL = '/v1'

export default {
  // ========================================
  // NOTIFICATIONS
  // ========================================
  getNotifications(params = {}) {
    return api.get(`${BASE_URL}/notifications`, { params })
  },

  getCountNonLues() {
    return api.get(`${BASE_URL}/notifications/non-lues/count`)
  },

  marquerLue(id) {
    return api.post(`${BASE_URL}/notifications/${id}/lue`)
  },

  marquerToutesLues() {
    return api.post(`${BASE_URL}/notifications/marquer-toutes-lues`)
  },

  deleteNotification(id) {
    return api.delete(`${BASE_URL}/notifications/${id}`)
  },

  supprimerLues() {
    return api.delete(`${BASE_URL}/notifications/lues`)
  },

  // ========================================
  // MESSAGERIE
  // ========================================
  getConversations(params = {}) {
    return api.get(`${BASE_URL}/messagerie/conversations`, { params })
  },

  creerConversation(data) {
    return api.post(`${BASE_URL}/messagerie/conversations`, data)
  },

  getConversation(id) {
    return api.get(`${BASE_URL}/messagerie/conversations/${id}`)
  },

  envoyerMessage(conversationId, data) {
    return api.post(`${BASE_URL}/messagerie/conversations/${conversationId}/messages`, data)
  },

  ajouterPieceJointe(conversationId, formData) {
    return api.post(`${BASE_URL}/messagerie/conversations/${conversationId}/pieces-jointes`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  marquerLu(conversationId) {
    return api.post(`${BASE_URL}/messagerie/conversations/${conversationId}/lue`)
  },

  getStatsNonLus() {
    return api.get(`${BASE_URL}/messagerie/stats-non-lus`)
  },

  // ========================================
  // MESSAGERIE RH (admin/rh uniquement)
  // ========================================
  assignerConversation(conversationId, userId) {
    return api.post(`${BASE_URL}/messagerie-rh/conversations/${conversationId}/assigner`, {
      user_id: userId
    })
  },

  changerStatutConversation(conversationId, statut) {
    return api.put(`${BASE_URL}/messagerie-rh/conversations/${conversationId}/statut`, { statut })
  }
}
