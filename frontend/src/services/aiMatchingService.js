import api from './api'

/**
 * Service pour le matching IA amélioré (CV ↔ Poste)
 */
const aiMatchingService = {
  /**
   * Analyser la compatibilité d'un employé avec un poste via IA
   * @param {number} employeId - ID de l'employé
   * @param {number} posteId - ID du poste
   * @returns {Promise}
   */
  analyserProfil(employeId, posteId) {
    return api.post('/v1/matching-ia/analyser-profil', { employe_id: employeId, poste_id: posteId })
  },

  /**
   * Analyser un CV pour un poste
   * @param {string} cvText - Texte du CV
   * @param {number} posteId - ID du poste
   * @returns {Promise}
   */
  analyserCV(cvText, posteId) {
    return api.post('/v1/matching-ia/analyser-cv', { cv_texte: cvText, poste_id: posteId })
  },

  /**
   * Obtenir des suggestions de formations IA pour un employé
   * @param {number} employeId - ID de l'employé
   * @param {number|null} posteId - ID du poste cible (optionnel)
   * @returns {Promise}
   */
  suggestionsFormations(employeId, posteId = null) {
    return api.get(`/v1/matching-ia/employes/${employeId}/suggestions-formations`, { 
      params: posteId ? { poste_cible_id: posteId } : {} 
    })
  },

  /**
   * Générer un plan de carrière IA pour un employé
   * @param {number} employeId - ID de l'employé
   * @returns {Promise}
   */
  planCarriere(employeId) {
    return api.get(`/v1/matching-ia/employes/${employeId}/plan-carriere`)
  },

  /**
   * Trouver les meilleurs candidats pour un poste avec IA
   * @param {number} posteId - ID du poste
   * @param {number} limit - Nombre maximum de résultats
   * @returns {Promise}
   */
  candidatsPourPoste(posteId, limit = 10) {
    return api.get(`/v1/matching-ia/postes/${posteId}/candidats`, { params: { limit } })
  },

  /**
   * Trouver les postes compatibles pour un employé avec IA
   * @param {number} employeId - ID de l'employé
   * @param {number} limit - Nombre maximum de résultats
   * @returns {Promise}
   */
  postesCompatibles(employeId, limit = 10) {
    return api.get(`/v1/matching-ia/employes/${employeId}/postes-compatibles`, { params: { limit } })
  },

  // === Utilitaires ===

  /**
   * Extraire le texte d'un fichier CV (pour l'analyse)
   * @param {File} file - Fichier CV
   * @returns {Promise<string>}
   */
  async extractCVText(file) {
    // Pour les fichiers texte simples
    if (file.type === 'text/plain') {
      return await file.text()
    }
    
    // Pour les autres types, on envoie au backend pour extraction
    const formData = new FormData()
    formData.append('cv_file', file)
    
    const response = await api.post('/v1/matching-ia/extract-cv', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    return response.data.text
  },

  /**
   * Obtenir l'interprétation du score de compatibilité
   * @param {number} score - Score de 0 à 100
   * @returns {object}
   */
  interpretScore(score) {
    if (score >= 85) {
      return {
        level: 'excellent',
        label: 'Excellent',
        color: 'green',
        description: 'Correspondance parfaite, profil très adapté'
      }
    } else if (score >= 70) {
      return {
        level: 'bon',
        label: 'Bon',
        color: 'blue',
        description: 'Bonne correspondance, quelques ajustements possibles'
      }
    } else if (score >= 50) {
      return {
        level: 'moyen',
        label: 'Moyen',
        color: 'yellow',
        description: 'Correspondance partielle, formation recommandée'
      }
    } else {
      return {
        level: 'faible',
        label: 'Faible',
        color: 'red',
        description: 'Peu de correspondance, reconversion nécessaire'
      }
    }
  }
}

export default aiMatchingService
