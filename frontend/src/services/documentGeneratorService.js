import api from './api'

/**
 * Service pour la génération automatique de documents RH
 */
const documentGeneratorService = {
  /**
   * Obtenir la liste des types de documents disponibles
   * @returns {Promise}
   */
  getTypes() {
    return api.get('/documents-generator/types')
  },

  /**
   * Générer un document
   * @param {string} type - Type de document
   * @param {object} data - Données pour la génération
   * @returns {Promise}
   */
  generer(type, data) {
    return api.post('/documents-generator/generer', { type, ...data })
  },

  // === Documents spécifiques par employé ===

  /**
   * Générer une attestation de travail
   * @param {number} employeId - ID de l'employé
   * @param {object} options - Options supplémentaires
   * @returns {Promise}
   */
  attestationTravail(employeId, options = {}) {
    return api.post(`/documents-generator/employes/${employeId}/attestation-travail`, options)
  },

  /**
   * Générer un certificat de travail
   * @param {number} employeId - ID de l'employé
   * @param {object} options - Options supplémentaires
   * @returns {Promise}
   */
  certificatTravail(employeId, options = {}) {
    return api.post(`/documents-generator/employes/${employeId}/certificat-travail`, options)
  },

  /**
   * Générer une attestation de salaire
   * @param {number} employeId - ID de l'employé
   * @param {object} options - Options (mois, annee, nombre_mois)
   * @returns {Promise}
   */
  attestationSalaire(employeId, options = {}) {
    return api.post(`/documents-generator/employes/${employeId}/attestation-salaire`, options)
  },

  /**
   * Générer une attestation de congé
   * @param {number} employeId - ID de l'employé
   * @param {number} congeId - ID de la demande de congé
   * @returns {Promise}
   */
  attestationConge(employeId, congeId) {
    return api.post(`/documents-generator/employes/${employeId}/attestation-conge`, { conge_id: congeId })
  },

  /**
   * Générer une lettre de recommandation
   * @param {number} employeId - ID de l'employé
   * @param {object} options - Options (competences, commentaires_additionnels)
   * @returns {Promise}
   */
  lettreRecommandation(employeId, options = {}) {
    return api.post(`/documents-generator/employes/${employeId}/lettre-recommandation`, options)
  },

  /**
   * Générer un contrat de travail
   * @param {number} employeId - ID de l'employé
   * @param {object} options - Options (contrat_id, clauses_specifiques)
   * @returns {Promise}
   */
  contratTravail(employeId, options = {}) {
    return api.post(`/documents-generator/employes/${employeId}/contrat-travail`, options)
  },

  /**
   * Générer un avenant au contrat
   * @param {number} employeId - ID de l'employé
   * @param {object} modifications - Modifications à apporter
   * @returns {Promise}
   */
  avenantContrat(employeId, modifications) {
    return api.post(`/documents-generator/employes/${employeId}/avenant-contrat`, { modifications })
  },

  /**
   * Générer une attestation de formation
   * @param {number} employeId - ID de l'employé
   * @param {number} formationId - ID de la formation
   * @param {object} options - Options (competences_acquises, note_evaluation)
   * @returns {Promise}
   */
  attestationFormation(employeId, formationId, options = {}) {
    return api.post(`/documents-generator/employes/${employeId}/attestation-formation`, { 
      formation_id: formationId,
      ...options 
    })
  },

  // === Self-service (pour l'employé connecté) ===

  /**
   * Générer un document pour soi-même (self-service)
   * @param {string} type - Type de document
   * @param {object} options - Options supplémentaires
   * @returns {Promise}
   */
  selfServiceGenerer(type, options = {}) {
    return api.post('/documents/generer', { type, ...options })
  },

  /**
   * Télécharger un document généré
   * @param {string} filePath - Chemin du fichier
   * @returns {Promise}
   */
  download(filePath) {
    return api.get(`/storage/${filePath}`, { responseType: 'blob' })
  }
}

export default documentGeneratorService
