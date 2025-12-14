import api from './api'

/**
 * Service pour la détection d'anomalies RH
 */
const anomalyService = {
  /**
   * Détecter toutes les anomalies
   * @param {object} params - Paramètres de filtrage
   * @returns {Promise}
   */
  detecterToutes(params = {}) {
    return api.get('/v1/anomalies', { params })
  },

  /**
   * Obtenir le dashboard des anomalies
   * @returns {Promise}
   */
  getDashboard() {
    return api.get('/v1/anomalies/dashboard')
  },

  /**
   * Obtenir les statistiques des anomalies
   * @returns {Promise}
   */
  getStatistiques() {
    return api.get('/v1/anomalies/statistiques')
  },

  /**
   * Obtenir les alertes critiques
   * @returns {Promise}
   */
  getAlertesCritiques() {
    return api.get('/v1/anomalies/critiques')
  },

  // === Anomalies par catégorie ===

  /**
   * Détecter les anomalies de pointage
   * @param {object} params - Paramètres (date_debut, date_fin, employe_id)
   * @returns {Promise}
   */
  detecterPointage(params = {}) {
    return api.get('/v1/anomalies/pointage', { params })
  },

  /**
   * Détecter les anomalies de paie
   * @param {object} params - Paramètres (mois, annee, employe_id)
   * @returns {Promise}
   */
  detecterPaie(params = {}) {
    return api.get('/v1/anomalies/paie', { params })
  },

  /**
   * Détecter les anomalies de congés
   * @param {object} params - Paramètres (employe_id)
   * @returns {Promise}
   */
  detecterConges(params = {}) {
    return api.get('/v1/anomalies/conges', { params })
  },

  /**
   * Détecter les anomalies de contrats
   * @param {object} params - Paramètres (employe_id)
   * @returns {Promise}
   */
  detecterContrats(params = {}) {
    return api.get('/v1/anomalies/contrats', { params })
  },

  /**
   * Détecter les anomalies d'heures
   * @param {object} params - Paramètres (semaine, employe_id)
   * @returns {Promise}
   */
  detecterHeures(params = {}) {
    return api.get('/v1/anomalies/heures', { params })
  },

  // === Utilitaires ===

  /**
   * Obtenir les types d'anomalies disponibles
   * @returns {object}
   */
  getTypesAnomalies() {
    return {
      pointage: [
        { type: 'pointage_incomplet', label: 'Pointage incomplet', severity: 'moyenne' },
        { type: 'retard_significatif', label: 'Retard significatif', severity: 'basse' },
        { type: 'depart_anticipe', label: 'Départ anticipé', severity: 'basse' },
        { type: 'heures_excessives', label: 'Heures excessives', severity: 'haute' },
        { type: 'pointage_weekend', label: 'Pointage weekend', severity: 'moyenne' },
        { type: 'absence_non_justifiee', label: 'Absence non justifiée', severity: 'haute' }
      ],
      paie: [
        { type: 'variation_salaire_importante', label: 'Variation salaire importante', severity: 'haute' },
        { type: 'heures_sup_excessives', label: 'Heures sup excessives', severity: 'moyenne' },
        { type: 'salaire_negatif', label: 'Salaire négatif', severity: 'critique' },
        { type: 'retenues_excessives', label: 'Retenues excessives', severity: 'haute' },
        { type: 'paie_manquante', label: 'Paie manquante', severity: 'critique' }
      ],
      conges: [
        { type: 'conge_passe_non_traite', label: 'Congé passé non traité', severity: 'haute' },
        { type: 'conge_tres_long', label: 'Congé très long', severity: 'moyenne' },
        { type: 'chevauchement_conges', label: 'Chevauchement congés', severity: 'haute' }
      ],
      contrats: [
        { type: 'contrats_multiples_actifs', label: 'Contrats multiples actifs', severity: 'critique' },
        { type: 'contrat_expire', label: 'Contrat expiré', severity: 'critique' },
        { type: 'periode_essai_non_validee', label: 'Période essai non validée', severity: 'haute' },
        { type: 'cdd_sans_date_fin', label: 'CDD sans date fin', severity: 'haute' },
        { type: 'employe_sans_contrat_actif', label: 'Employé sans contrat actif', severity: 'critique' }
      ],
      heures: [
        { type: 'heures_hebdo_excessives', label: 'Heures hebdo excessives', severity: 'haute' },
        { type: 'heures_insuffisantes', label: 'Heures insuffisantes', severity: 'moyenne' }
      ]
    }
  },

  /**
   * Obtenir la couleur selon la sévérité
   * @param {string} severity - Niveau de sévérité
   * @returns {string}
   */
  getSeverityColor(severity) {
    const colors = {
      critique: 'red',
      haute: 'orange',
      moyenne: 'yellow',
      basse: 'blue'
    }
    return colors[severity] || 'gray'
  },

  /**
   * Obtenir l'icône selon la sévérité
   * @param {string} severity - Niveau de sévérité
   * @returns {string}
   */
  getSeverityIcon(severity) {
    const icons = {
      critique: '🚨',
      haute: '⚠️',
      moyenne: '⚡',
      basse: 'ℹ️'
    }
    return icons[severity] || '📋'
  }
}

export default anomalyService
