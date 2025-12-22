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
    // Backend attend mot_de_passe_actuel / nouveau_mot_de_passe / nouveau_mot_de_passe_confirmation
    const payload = {
      mot_de_passe_actuel: data.current_password || data.mot_de_passe_actuel,
      nouveau_mot_de_passe: data.new_password || data.nouveau_mot_de_passe,
      nouveau_mot_de_passe_confirmation: data.new_password_confirmation || data.nouveau_mot_de_passe_confirmation,
    }
    return api.post(`${BASE_URL}/changer-mot-de-passe`, payload)
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
    // utilise le catalogue public (lecture seule)
    return api.get(`/v1/formations`, { params: { actif: true } })
  },

  // ========================================
  // CONVERSATIONS
  // ========================================
  getConversations() {
    return api.get(`${BASE_URL}/conversations`)
  }
}
