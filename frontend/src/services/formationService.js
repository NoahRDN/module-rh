import api from './api'

const BASE_URL = '/v1'

export default {
  // ========================================
  // FORMATIONS
  // ========================================
  getFormations(params = {}) {
    return api.get(`${BASE_URL}/formations`, { params })
  },

  getFormation(id) {
    return api.get(`${BASE_URL}/formations/${id}`)
  },

  createFormation(data) {
    return api.post(`${BASE_URL}/formations`, data)
  },

  updateFormation(id, data) {
    return api.put(`${BASE_URL}/formations/${id}`, data)
  },

  deleteFormation(id) {
    return api.delete(`${BASE_URL}/formations/${id}`)
  },

  // ========================================
  // INSCRIPTIONS AUX FORMATIONS
  // ========================================
  getInscriptions(params = {}) {
    return api.get(`${BASE_URL}/formation-employes`, { params })
  },

  getInscription(id) {
    return api.get(`${BASE_URL}/formation-employes/${id}`)
  },

  inscrireEmploye(data) {
    return api.post(`${BASE_URL}/formation-employes`, data)
  },

  updateInscription(id, data) {
    return api.put(`${BASE_URL}/formation-employes/${id}`, data)
  },

  annulerInscription(id) {
    return api.delete(`${BASE_URL}/formation-employes/${id}`)
  },

  // Historique formations d'un employé
  getHistoriqueEmploye(employeId) {
    return api.get(`${BASE_URL}/employes/${employeId}/formations`)
  }
}
