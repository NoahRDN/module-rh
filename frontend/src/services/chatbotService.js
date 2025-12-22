import api from './api'

/**
 * Service pour le chatbot RH intelligent
 */
const chatbotService = {
  /**
   * Envoyer une question au chatbot
   * @param {string} question - La question de l'utilisateur
   * @param {string|null} context - Contexte additionnel optionnel
   * @returns {Promise}
   */
  ask(question, context = null) {
    return api.post('/v1/chatbot/ask', { question, context })
  },

  /**
   * Obtenir des suggestions de questions
   * @returns {Promise}
   */
  getSuggestions() {
    return api.get('/v1/chatbot/suggestions')
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
