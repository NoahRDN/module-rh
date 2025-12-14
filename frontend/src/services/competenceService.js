import api from './api'

const BASE_URL = '/v1'

export default {
  // ========================================
  // CATÉGORIES DE COMPÉTENCES
  // ========================================
  getCategories() {
    return api.get(`${BASE_URL}/categories-competences`)
  },

  createCategorie(data) {
    return api.post(`${BASE_URL}/categories-competences`, data)
  },

  updateCategorie(id, data) {
    return api.put(`${BASE_URL}/categories-competences/${id}`, data)
  },

  deleteCategorie(id) {
    return api.delete(`${BASE_URL}/categories-competences/${id}`)
  },

  // ========================================
  // COMPÉTENCES
  // ========================================
  getCompetences(params = {}) {
    return api.get(`${BASE_URL}/competences`, { params })
  },

  getCompetence(id) {
    return api.get(`${BASE_URL}/competences/${id}`)
  },

  createCompetence(data) {
    return api.post(`${BASE_URL}/competences`, data)
  },

  updateCompetence(id, data) {
    return api.put(`${BASE_URL}/competences/${id}`, data)
  },

  deleteCompetence(id) {
    return api.delete(`${BASE_URL}/competences/${id}`)
  },

  // Niveaux de compétence (référentiel)
  getNiveaux() {
    return api.get(`${BASE_URL}/niveaux-competence`)
  },

  // Cartographie globale des compétences
  getCartographie() {
    return api.get(`${BASE_URL}/competences/cartographie`)
  },

  // ========================================
  // COMPÉTENCES DES EMPLOYÉS
  // ========================================
  getEmployeCompetences(employeId) {
    return api.get(`${BASE_URL}/employes/${employeId}/competences`)
  },

  addEmployeCompetence(employeId, data) {
    return api.post(`${BASE_URL}/employes/${employeId}/competences`, data)
  },

  updateEmployeCompetences(employeId, competences) {
    return api.put(`${BASE_URL}/employes/${employeId}/competences`, { competences })
  },

  deleteEmployeCompetence(employeId, competenceId) {
    return api.delete(`${BASE_URL}/employes/${employeId}/competences/${competenceId}`)
  },

  getEmployeRadar(employeId) {
    return api.get(`${BASE_URL}/employes/${employeId}/competences/radar`)
  },

  // ========================================
  // COMPÉTENCES REQUISES DES POSTES
  // ========================================
  getPosteCompetences(posteId) {
    return api.get(`${BASE_URL}/postes/${posteId}/competences`)
  },

  addPosteCompetence(posteId, data) {
    return api.post(`${BASE_URL}/postes/${posteId}/competences`, data)
  },

  updatePosteCompetences(posteId, competences) {
    return api.put(`${BASE_URL}/postes/${posteId}/competences`, { competences })
  },

  deletePosteCompetence(posteId, competenceId) {
    return api.delete(`${BASE_URL}/postes/${posteId}/competences/${competenceId}`)
  },

  // ========================================
  // MATCHING
  // ========================================
  calculerCompatibilite(employeId, posteId) {
    return api.post(`${BASE_URL}/matching/compatibilite`, {
      employe_id: employeId,
      poste_id: posteId
    })
  },

  getCandidatsPourPoste(posteId, params = {}) {
    return api.get(`${BASE_URL}/matching/postes/${posteId}/candidats`, { params })
  },

  getPostesCompatibles(employeId, params = {}) {
    return api.get(`${BASE_URL}/matching/employes/${employeId}/postes-compatibles`, { params })
  },

  getSuggestionsFormations(employeId, posteId = null) {
    const params = posteId ? { poste_id: posteId } : {}
    return api.get(`${BASE_URL}/matching/employes/${employeId}/suggestions-formations`, { params })
  },

  getAnalyseGlobale() {
    return api.get(`${BASE_URL}/matching/analyse-globale`)
  }
}
