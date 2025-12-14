import api from './api'

const BASE_URL = '/v1/self-service'

export default {
  // ========================================
  // DASHBOARD
  // ========================================
  getDashboard() {
    return api.get(`${BASE_URL}/dashboard`)
  },

  // ========================================
  // PROFIL
  // ========================================
  getProfil() {
    return api.get(`${BASE_URL}/profil`)
  },

  updateProfil(data) {
    return api.put(`${BASE_URL}/profil`, data)
  },

  changerMotDePasse(data) {
    return api.post(`${BASE_URL}/changer-mot-de-passe`, data)
  },

  // ========================================
  // BULLETINS & CONGÉS
  // ========================================
  getBulletins(params = {}) {
    return api.get(`${BASE_URL}/bulletins`, { params })
  },

  getSoldeConges() {
    return api.get(`${BASE_URL}/solde-conges`)
  },

  getDemandesConges(params = {}) {
    return api.get(`${BASE_URL}/demandes-conges`, { params })
  },

  creerDemandeConge(data) {
    return api.post(`${BASE_URL}/demandes-conges`, data)
  },

  // ========================================
  // DEMANDES (attestations, remboursements)
  // ========================================
  getDemandes(params = {}) {
    return api.get(`${BASE_URL}/demandes`, { params })
  },

  creerDemande(data) {
    return api.post(`${BASE_URL}/demandes`, data)
  },

  // ========================================
  // COMPÉTENCES & FORMATIONS
  // ========================================
  getCompetences() {
    return api.get(`${BASE_URL}/competences`)
  },

  getFormations() {
    return api.get(`${BASE_URL}/formations`)
  },

  // ========================================
  // CONVERSATIONS
  // ========================================
  getConversations() {
    return api.get(`${BASE_URL}/conversations`)
  }
}
