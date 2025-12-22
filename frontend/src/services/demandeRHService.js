import api from './api'

const BASE_URL = '/v1'

export default {
  // ========================================
  // TYPES DE DEMANDES
  // ========================================
  getTypesDemandes() {
    return api.get(`${BASE_URL}/types-demandes`)
  },

  // ========================================
  // DEMANDES RH (Admin/RH)
  // ========================================
  getDemandes(params = {}) {
    return api.get(`${BASE_URL}/demandes-rh`, { params })
  },

  getDemande(id) {
    return api.get(`${BASE_URL}/demandes-rh/${id}`)
  },

  createDemande(data) {
    return api.post(`${BASE_URL}/demandes-rh`, data)
  },

  updateDemande(id, data) {
    return api.put(`${BASE_URL}/demandes-rh/${id}`, data)
  },

  soumettreDemande(id) {
    return api.post(`${BASE_URL}/demandes-rh/${id}/soumettre`)
  },

  approuverDemande(id, data = {}) {
    return api.post(`${BASE_URL}/demandes-rh/${id}/approuver`, data)
  },

  rejeterDemande(id, data) {
    return api.post(`${BASE_URL}/demandes-rh/${id}/rejeter`, data)
  },

  annulerDemande(id, data = {}) {
    return api.post(`${BASE_URL}/demandes-rh/${id}/annuler`, data)
  },

  // ========================================
  // DOCUMENTS DE DEMANDE
  // ========================================
  ajouterDocument(demandeId, formData) {
    return api.post(`${BASE_URL}/demandes-rh/${demandeId}/documents`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  supprimerDocument(demandeId, documentId) {
    return api.delete(`${BASE_URL}/demandes-rh/${demandeId}/documents/${documentId}`)
  }
}
