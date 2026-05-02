import api from './api'

/**
 * @remarks Feature prête mais désactivée temporairement.
 * @deprecated Activation ultérieure (phase 2).
 * 
 * Service pour le chatbot RH intelligent
 * Features: Chatbot RH pour répondre aux questions fréquentes (congés, paie, etc.)
 */
const chatbotService = {
  /**
   * Envoyer une question au chatbot
   * @param {string} question - La question de l'utilisateur
   * @param {string|null} context - Contexte additionnel optionnel
   * @returns {Promise}
   */
  ask(question, context = null) {
    return api.post('/v1/chatbot/ask', { question, context }, { timeout: 60000 })
  },

  /**
   * Obtenir des suggestions de questions
   * @returns {Promise}
   */
  getSuggestions() {
    return api.get('/v1/chatbot/suggestions')
  },

  /**
   * Récupérer les événements du calendrier (feries / rh / conge / absence)
   * @param {string} type - Le type d'événement ('ferie', 'rh', 'conge', 'absence')
   * @param {number} days - Nombre de jours à partir d'aujourd'hui
   * @returns {Promise}
   */
  getUpcomingEvents(type = 'ferie', days = 30) {
    const from = new Date().toISOString().slice(0, 10)
    const toDate = new Date()
    toDate.setDate(toDate.getDate() + Number(days))
    const to = toDate.toISOString().slice(0, 10)

    return api.get('/v1/calendrier-evenements', { params: { type, from, to } })
  },

  /**
   * Obtenir l'historique des conversations
   * @param {number} limit - Nombre de messages à récupérer
   * @returns {Promise}
   */
  getHistorique(limit = 20) {
    return api.get('/v1/chatbot/historique', { params: { limit } })
  }
}

export default chatbotService
