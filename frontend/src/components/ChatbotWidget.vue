<template>
  <div class="chatbot-container">
    <!-- Bouton flottant -->
    <button 
      v-if="!isOpen" 
      class="chatbot-toggle"
      @click="toggleChat"
    >
      <AppIcon name="sparkles" :size="22" />
      <span class="chatbot-badge" v-if="unreadCount > 0">{{ unreadCount }}</span>
    </button>

    <!-- Fenêtre de chat -->
    <transition name="slide-up">
      <div v-if="isOpen" class="chatbot-window">
        <!-- Header -->
        <div class="chatbot-header">
            <div class="chatbot-title">
            <span class="chatbot-avatar"><AppIcon name="sparkles" :size="26" /></span>
            <div class="chatbot-info">
              <h4>Assistant RH</h4>
              <span class="chatbot-status">En ligne</span>
            </div>
          </div>
          <div class="chatbot-actions">
            <button @click="clearChat" class="action-btn" title="Effacer la conversation">
              <AppIcon name="trash" :size="16" />
            </button>
            <button @click="toggleChat" class="action-btn close-btn">
              <AppIcon name="logout" :size="16" />
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
              <AppIcon :name="msg.isUser ? 'users' : 'sparkles'" :size="20" />
            </div>
            <div class="message-content">
              <div class="message-text" v-html="formatMessage(msg.text)"></div>
              <span class="message-time">{{ formatTime(msg.timestamp) }}</span>
            </div>
          </div>

          <!-- Indicateur de frappe -->
          <div v-if="isTyping" class="message bot-message typing">
            <div class="message-avatar"><AppIcon name="sparkles" :size="20" /></div>
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
            aria-label="Envoyer"
          >
            <AppIcon name="download" :size="16" />
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import chatbotService from '@/services/chatbotService'
import AppIcon from './ui/AppIcon.vue'

export default {
  name: 'ChatbotWidget',
  components: {
    AppIcon,
  },
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
        
        // Si le backend indique un échec, privilégier le champ d'erreur (plus explicite)
        const botMessage = response.data?.success === false
          ? (response.data.erreur || response.data.reponse || 'Erreur inconnue')
          : (response.data.reponse || response.data.response || 'Pas de réponse')
        
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
/* Use app variables for color and dark mode compatibility */
.chatbot-container{position:fixed;bottom:20px;right:20px;z-index:9999}
.chatbot-toggle{width:56px;height:56px;border-radius:12px;background:var(--vt-c-indigo);color:#fff;border:none;cursor:pointer;box-shadow:0 10px 30px rgba(2,6,23,0.12);display:flex;align-items:center;justify-content:center;position:relative;transition:transform .18s}
.chatbot-toggle:hover{transform:translateY(-3px)}
.chatbot-icon{font-size:22px}
.chatbot-badge{position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;padding:4px 7px;border-radius:999px;font-size:12px;font-weight:600}
.chatbot-window{width:380px;max-width:calc(100vw - 32px);height:560px;max-height:calc(100vh - 120px);background:var(--color-background);color:var(--color-text);border-radius:12px;display:flex;flex-direction:column;overflow:hidden;border:1px solid var(--color-border);box-shadow:0 20px 50px rgba(2,6,23,0.14)}
.chatbot-header{background:linear-gradient(135deg,var(--vt-c-indigo),#164e9f);color:#fff;padding:12px 14px;display:flex;align-items:center;justify-content:space-between}
.chatbot-title{display:flex;gap:10px;align-items:center}.chatbot-avatar{font-size:26px}.chatbot-info h4{margin:0;font-size:15px}.chatbot-status{font-size:12px;opacity:.95}.chatbot-actions{display:flex;gap:8px}.action-btn{background:rgba(255,255,255,0.08);border:none;width:34px;height:34px;border-radius:8px;color:#fff;cursor:pointer}.action-btn:hover{background:rgba(255,255,255,0.12)}
.chatbot-messages{flex:1;overflow-y:auto;padding:18px;background:var(--color-background-soft)}
.welcome-message{text-align:center;padding:14px}.welcome-icon{font-size:44px;margin-bottom:8px}.welcome-message h4{margin:0 0 8px 0;color:var(--color-heading)}.welcome-message p{color:var(--color-text);opacity:.85}
.suggestion-btn{display:block;width:100%;text-align:left;padding:10px 12px;margin-bottom:8px;background:var(--color-background);border:1px solid var(--color-border);border-radius:8px;cursor:pointer;color:var(--color-text)}
.suggestion-btn:hover{background:rgba(37,99,235,0.08);border-color:rgba(37,99,235,0.18);color:var(--vt-c-indigo)}
.message{display:flex;gap:10px;margin-bottom:14px;align-items:flex-end}.message-avatar{font-size:20px;flex-shrink:0}.message-content{max-width:78%}.message-text{padding:10px 14px;border-radius:12px;font-size:14px;line-height:1.45}
.user-message{flex-direction:row-reverse}.user-message .message-text{background:var(--vt-c-indigo);color:#fff;border-bottom-right-radius:6px}
.bot-message .message-text{background:var(--color-background);border:1px solid var(--color-border);color:var(--color-text);box-shadow:none}
.message-time{display:block;font-size:11px;color:var(--color-text);opacity:.6;margin-top:6px}
.typing-indicator{display:flex;gap:6px;align-items:center}.typing-indicator span{width:8px;height:8px;background:var(--vt-c-indigo);border-radius:50%;animation:bounce 1.2s infinite}@keyframes bounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}
.chatbot-input{display:flex;gap:10px;padding:12px;border-top:1px solid var(--color-border);background:var(--color-background)}
.chatbot-input input{flex:1;padding:10px 14px;border-radius:999px;border:1px solid var(--color-border);background:var(--color-background-soft);color:var(--color-text);outline:none}
.chatbot-input input:focus{box-shadow:0 0 0 3px rgba(37,99,235,0.08);border-color:var(--vt-c-indigo)}
.send-btn{width:44px;height:44px;border-radius:10px;background:var(--vt-c-indigo);color:#fff;border:none;cursor:pointer}.send-btn:disabled{opacity:.45;cursor:not-allowed}
.slide-up-enter-active,.slide-up-leave-active{transition:all .25s ease}.slide-up-enter-from,.slide-up-leave-to{opacity:0;transform:translateY(14px)}
@media(max-width:520px){.chatbot-window{width:calc(100vw - 32px);height:calc(100vh - 120px);right:16px;bottom:80px}}
</style>
