<template>
  <div class="chatbot-container">
    <!-- Bouton flottant -->
    <button 
      v-if="!isOpen" 
      class="chatbot-toggle"
      @click="toggleChat"
    >
      <span class="chatbot-icon">🤖</span>
      <span class="chatbot-badge" v-if="unreadCount > 0">{{ unreadCount }}</span>
    </button>

    <!-- Fenêtre de chat -->
    <transition name="slide-up">
      <div v-if="isOpen" class="chatbot-window">
        <!-- Header -->
        <div class="chatbot-header">
          <div class="chatbot-title">
            <span class="chatbot-avatar">🤖</span>
            <div class="chatbot-info">
              <h4>Assistant RH</h4>
              <span class="chatbot-status">En ligne</span>
            </div>
          </div>
          <div class="chatbot-actions">
            <button @click="clearChat" class="action-btn" title="Effacer la conversation">
              🗑️
            </button>
            <button @click="toggleChat" class="action-btn close-btn">
              ✕
            </button>
          </div>
        </div>

        <!-- Messages -->
        <div class="chatbot-messages" ref="messagesContainer">
          <!-- Message de bienvenue -->
          <div v-if="messages.length === 0" class="welcome-message">
            <div class="welcome-icon">👋</div>
            <h4>Bienvenue !</h4>
            <p>Je suis votre assistant RH. Comment puis-je vous aider ?</p>
            
            <div class="suggestions" v-if="suggestions.length > 0">
              <p class="suggestions-title">Questions fréquentes :</p>
              <button 
                v-for="(suggestion, index) in suggestions" 
                :key="index"
                class="suggestion-btn"
                @click="sendMessage(suggestion)"
              >
                {{ suggestion }}
              </button>
            </div>
          </div>

          <!-- Liste des messages -->
          <div 
            v-for="(msg, index) in messages" 
            :key="index"
            class="message"
            :class="{ 'user-message': msg.isUser, 'bot-message': !msg.isUser }"
          >
            <div class="message-avatar">
              {{ msg.isUser ? '👤' : '🤖' }}
            </div>
            <div class="message-content">
              <div class="message-text" v-html="formatMessage(msg.text)"></div>
              <span class="message-time">{{ formatTime(msg.timestamp) }}</span>
            </div>
          </div>

          <!-- Indicateur de frappe -->
          <div v-if="isTyping" class="message bot-message typing">
            <div class="message-avatar">🤖</div>
            <div class="message-content">
              <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Input -->
        <div class="chatbot-input">
          <input 
            v-model="inputMessage"
            @keyup.enter="sendMessage()"
            placeholder="Posez votre question..."
            :disabled="isTyping"
          />
          <button 
            @click="sendMessage()"
            :disabled="!inputMessage.trim() || isTyping"
            class="send-btn"
          >
            📤
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import chatbotService from '@/services/chatbotService'

export default {
  name: 'ChatbotWidget',
  data() {
    return {
      isOpen: false,
      messages: [],
      inputMessage: '',
      isTyping: false,
      suggestions: [],
      unreadCount: 0
    }
  },
  mounted() {
    this.loadSuggestions()
  },
  methods: {
    toggleChat() {
      this.isOpen = !this.isOpen
      if (this.isOpen) {
        this.unreadCount = 0
        this.$nextTick(() => {
          this.scrollToBottom()
        })
      }
    },
    async loadSuggestions() {
      try {
        const response = await chatbotService.getSuggestions()
        this.suggestions = response.data.suggestions || []
      } catch (error) {
        console.error('Erreur chargement suggestions:', error)
        this.suggestions = [
          "Quel est mon solde de congés ?",
          "Quand est ma prochaine paie ?",
          "Comment poser un congé ?",
          "Quelles formations sont disponibles ?"
        ]
      }
    },
    async sendMessage(text = null) {
      const message = text || this.inputMessage.trim()
      if (!message) return

      // Ajouter le message utilisateur
      this.messages.push({
        text: message,
        isUser: true,
        timestamp: new Date()
      })
      
      this.inputMessage = ''
      this.isTyping = true
      this.scrollToBottom()

      try {
        const response = await chatbotService.ask(message)
        
        console.log('Chatbot response:', response.data)
        
        // La réponse peut être dans response.data.reponse ou response.data.response
        const botMessage = response.data.reponse || response.data.response || 'Pas de réponse'
        
        this.messages.push({
          text: botMessage,
          isUser: false,
          timestamp: new Date()
        })

        if (!this.isOpen) {
          this.unreadCount++
        }
      } catch (error) {
        console.error('Erreur chatbot:', error)
        console.error('Error details:', error.response?.data)
        
        const errorMessage = error.response?.data?.reponse 
          || error.response?.data?.erreur 
          || "Désolé, une erreur s'est produite. Veuillez réessayer."
        
        this.messages.push({
          text: errorMessage,
          isUser: false,
          timestamp: new Date()
        })
      } finally {
        this.isTyping = false
        this.scrollToBottom()
      }
    },
    clearChat() {
      this.messages = []
    },
    scrollToBottom() {
      this.$nextTick(() => {
        const container = this.$refs.messagesContainer
        if (container) {
          container.scrollTop = container.scrollHeight
        }
      })
    },
    formatMessage(text) {
      // Convertir les sauts de ligne et les listes
      return text
        .replace(/\n/g, '<br>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/- (.*?)(<br>|$)/g, '• $1$2')
    },
    formatTime(date) {
      return new Date(date).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
      })
    }
  }
}
</script>

<style scoped>
.chatbot-container {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 9999;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Bouton flottant */
.chatbot-toggle {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.3s, box-shadow 0.3s;
  position: relative;
}

.chatbot-toggle:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5);
}

.chatbot-icon {
  font-size: 28px;
}

.chatbot-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #ef4444;
  color: white;
  font-size: 12px;
  font-weight: bold;
  padding: 2px 6px;
  border-radius: 10px;
  min-width: 18px;
}

/* Fenêtre de chat */
.chatbot-window {
  width: 380px;
  height: 550px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Header */
.chatbot-header {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: white;
  padding: 15px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.chatbot-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.chatbot-avatar {
  font-size: 32px;
}

.chatbot-info h4 {
  margin: 0;
  font-size: 16px;
}

.chatbot-status {
  font-size: 12px;
  opacity: 0.8;
}

.chatbot-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.2s;
}

.action-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.close-btn {
  font-size: 18px;
}

/* Messages */
.chatbot-messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  background: #f8fafc;
}

.welcome-message {
  text-align: center;
  padding: 20px;
}

.welcome-icon {
  font-size: 48px;
  margin-bottom: 10px;
}

.welcome-message h4 {
  margin: 0 0 10px 0;
  color: #1e40af;
}

.welcome-message p {
  color: #64748b;
  margin-bottom: 20px;
}

.suggestions {
  text-align: left;
}

.suggestions-title {
  font-size: 13px;
  color: #64748b;
  margin-bottom: 10px;
}

.suggestion-btn {
  display: block;
  width: 100%;
  text-align: left;
  padding: 10px 15px;
  margin-bottom: 8px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  font-size: 13px;
  color: #334155;
  transition: all 0.2s;
}

.suggestion-btn:hover {
  background: #2563eb;
  color: white;
  border-color: #2563eb;
}

/* Message bubbles */
.message {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.message-avatar {
  font-size: 24px;
  flex-shrink: 0;
}

.message-content {
  max-width: 80%;
}

.message-text {
  padding: 12px 16px;
  border-radius: 16px;
  font-size: 14px;
  line-height: 1.5;
}

.user-message {
  flex-direction: row-reverse;
}

.user-message .message-text {
  background: #2563eb;
  color: white;
  border-bottom-right-radius: 4px;
}

.bot-message .message-text {
  background: white;
  color: #334155;
  border-bottom-left-radius: 4px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.message-time {
  display: block;
  font-size: 11px;
  color: #94a3b8;
  margin-top: 4px;
}

.user-message .message-time {
  text-align: right;
}

/* Typing indicator */
.typing-indicator {
  display: flex;
  gap: 4px;
  padding: 8px 0;
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  background: #94a3b8;
  border-radius: 50%;
  animation: bounce 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(1) { animation-delay: 0s; }
.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

@keyframes bounce {
  0%, 60%, 100% { transform: translateY(0); }
  30% { transform: translateY(-8px); }
}

/* Input */
.chatbot-input {
  display: flex;
  padding: 15px;
  background: white;
  border-top: 1px solid #e2e8f0;
  gap: 10px;
}

.chatbot-input input {
  flex: 1;
  padding: 12px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 24px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s;
}

.chatbot-input input:focus {
  border-color: #2563eb;
}

.send-btn {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #2563eb;
  border: none;
  cursor: pointer;
  font-size: 18px;
  transition: background 0.2s;
}

.send-btn:hover:not(:disabled) {
  background: #1d4ed8;
}

.send-btn:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
}

/* Animation */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

/* Responsive */
@media (max-width: 450px) {
  .chatbot-window {
    width: calc(100vw - 40px);
    height: calc(100vh - 100px);
    position: fixed;
    bottom: 80px;
    right: 20px;
  }
}
</style>
