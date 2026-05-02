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
      <div v-if="isOpen" :class="['chatbot-window', { 'theme-dark': appliedTheme === 'dark', 'theme-light': appliedTheme === 'light' }]">
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
            <button class="action-btn" @click="toggleMinimize" aria-label="Minimize chat">
              <AppIcon name="chevron-down" :size="16" />
            </button>
            <button @click="toggleChat" class="action-btn close-btn" title="Fermer">
              <AppIcon name="logout" :size="16" />
            </button>
          </div>
        </div>

        <!-- Messages -->
        <div class="chatbot-messages" ref="messagesContainer">
          <!-- Message de bienvenue -->
          <div v-if="messages.length === 0" class="welcome-message">
            <div class="welcome-icon" style="
                display: flex;
                justify-content: center;
            ">
              <span class="chatbot-avatar"><AppIcon name="sparkles" :size="26" /></span>
            </div>
            <h4>Bienvenue !</h4>
            <p>Je suis votre assistant RH. Comment puis-je vous aider ?</p>
            
            <div class="suggestions" v-if="suggestions.length > 0">
              <p class="suggestions-title">Questions fréquentes :</p>
              <button 
                v-for="(suggestion, index) in suggestions" 
                :key="index"
                class="suggestion-btn"
                @click="handleSuggestion(suggestion)"
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

        <!-- Inline suggestions shown after a bot response -->
        <div v-if="inlineSuggestions.length > 0" class="inline-suggestions" style="padding:12px 18px;border-top:1px solid var(--color-border);background:var(--chat-window-bg)">
          <p class="suggestions-title">Suggestions :</p>
          <div>
            <button
              v-for="(s, i) in inlineSuggestions"
              :key="i"
              class="suggestion-btn"
              @click="handleSuggestion(s)"
            >
              {{ s }}
            </button>
          </div>
        </div>

        <!-- Input -->
        <div class="chatbot-input">
          <input 
            v-model="inputMessage"
            @input="inlineSuggestions = []"
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
  props: {
    /**
     * Controle local du thème pour le widget.
     * - 'auto' : suit `prefers-color-scheme`
     * - 'light' ou 'dark' : force le thème du widget
     */
    theme: {
      type: String,
      default: 'auto'
    }
  },
  data() {
    return {
      isOpen: false,
      messages: [],
      inputMessage: '',
      isTyping: false,
      suggestions: [],
      inlineSuggestions: [],
      unreadCount: 0,
      appliedTheme: 'auto',
      _prefersMediaQuery: null,
      _mutationObserver: null
    }
  },
  mounted() {
    this.determineTheme()
  },
  beforeUnmount() {
    if (this._prefersMediaQuery) {
      try {
        this._prefersMediaQuery.removeEventListener?.('change', this._onPrefersChange)
        this._prefersMediaQuery.removeListener?.(this._onPrefersChange)
      } catch (e) {
        // ignore
      }
    }
    if (this._mutationObserver) {
      try {
        this._mutationObserver.disconnect()
      } catch (e) {
        // ignore
      }
    }
  },
  methods: {
    determineTheme() {
      // Applique le thème local du widget (auto / light / dark)
      if (this.theme === 'light' || this.theme === 'dark') {
        this.appliedTheme = this.theme
        return
      }

      // Priorité 1: regarder si la page a explicitement un data-theme
      const explicit = document.documentElement.getAttribute('data-theme') || document.body.getAttribute('data-theme')
      const mq = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)')

      if (explicit === 'dark' || explicit === 'light') {
        this.appliedTheme = explicit
      } else {
        // 'auto' : suivre la préférence système
        this._prefersMediaQuery = mq
        const isDark = mq ? mq.matches : false
        this.appliedTheme = isDark ? 'dark' : 'light'
      }

      // gérer les changements si l'utilisateur change la préférence système
      this._onPrefersChange = (e) => {
        if (this.theme !== 'auto') return
        this.appliedTheme = e.matches ? 'dark' : 'light'
      }

      try {
        mq?.addEventListener?.('change', this._onPrefersChange)
        mq?.addListener?.(this._onPrefersChange)
      } catch (e) {
        // ignore
      }

      // Observer des changements explicites de l'attribut data-theme sur la page
      try {
        this._mutationObserver = new MutationObserver((mutations) => {
          if (this.theme !== 'auto') return
          for (const m of mutations) {
            if (m.type === 'attributes' && m.attributeName === 'data-theme') {
              const val = document.documentElement.getAttribute('data-theme') || document.body.getAttribute('data-theme')
              if (val === 'dark' || val === 'light') {
                this.appliedTheme = val
              } else {
                const mq2 = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)')
                this.appliedTheme = mq2 && mq2.matches ? 'dark' : 'light'
              }
            }
          }
        })

        this._mutationObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] })
        this._mutationObserver.observe(document.body, { attributes: true, attributeFilter: ['data-theme'] })
      } catch (e) {
        // ignore
      }
    },
    toggleChat() {
      this.isOpen = !this.isOpen
      if (this.isOpen) {
        this.unreadCount = 0
        if (!this.suggestions.length) {
          this.loadSuggestions()
        }
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
        // fallback: adapter les suggestions en local selon le rôle connu (localStorage)
        const role = (localStorage.getItem('role') || '').toLowerCase()
        if (role === 'admin' || role === 'rh') {
          this.suggestions = [
          
          ]
        } else if (role === 'manager') {
          this.suggestions = [
            'Valider une demande de congé',
            'Consulter mes équipes',
            'Quand est le prochain jour férié ?',
          ]
        } else {
          this.suggestions = [
            "Quel est mon solde de congés ?",
            "Quand est mon prochain jour férié ?",
            "Comment demander une attestation de travail ?",
            "Quelles formations sont disponibles ?",
          ]
        }
      }
    },

    async handleSuggestion(suggestion) {
      const s = (suggestion || '').toLowerCase()

      // Avoid navigating away from the app for suggestions; prefer text responses
      if (s.includes('créer') && s.includes('événement')) {
        // Ask the chatbot to explain how to create an RH event
        this.sendMessage('Comment créer un événement RH ?')
        this.inlineSuggestions = []
        return
      }

      if (s.includes('ajouter') && s.includes('jour')) {
        // Ask the chatbot to explain how to add a holiday
        this.sendMessage('Comment ajouter un jour férié ?')
        this.inlineSuggestions = []
        return
      }

      if (s.includes('événements rh') || s.includes('événements à venir') || s.includes('événements rh à venir')) {
        await this.fetchUpcomingEvents('rh', 30)
        this.inlineSuggestions = []
        return
      }

      if (s.includes('prochain') && s.includes('jour f')) {
        await this.fetchNextHoliday()
        this.inlineSuggestions = []
        return
      }

      // Default: send the suggestion as a user message to the chatbot
      this.inlineSuggestions = []
      this.sendMessage(suggestion)
    },

    async fetchNextHoliday() {
      try {
        const res = await chatbotService.getUpcomingEvents('ferie', 90)
        const items = res.data.data || []
        if (!items.length) {
          this.messages.push({ text: 'Aucun jour férié trouvé dans les 90 prochains jours.', isUser: false, timestamp: new Date() })
          return
        }

        const next = items[0]
        const desc = `${next.description || 'Jour férié'} — ${next.date_debut || next.date}
`
        this.messages.push({ text: `Prochain jour férié : ${desc}`, isUser: false, timestamp: new Date() })
        this.scrollToBottom()
      } catch (error) {
        console.error('Erreur récupération prochain jour férié', error)
        this.messages.push({ text: 'Impossible de récupérer le prochain jour férié.', isUser: false, timestamp: new Date() })
      }
    },

    async fetchUpcomingEvents(type = 'rh', days = 30) {
      try {
        const res = await chatbotService.getUpcomingEvents(type, days)
        const items = res.data.data || []
        if (!items.length) {
          this.messages.push({ text: `Aucun événement ${type} trouvé dans les ${days} prochains jours.`, isUser: false, timestamp: new Date() })
          return
        }

        const lines = items.slice(0, 6).map((it) => {
          const name = it.description || it.meta?.nom || it.description || 'Événement'
          const date = it.date_debut || it.date || ''
          return `• ${name} — ${date}`
        })

        this.messages.push({ text: `Événements à venir (${type}):\n${lines.join('\n')}`, isUser: false, timestamp: new Date() })
        this.scrollToBottom()
      } catch (error) {
        console.error('Erreur récupération événements', error)
        this.messages.push({ text: `Impossible de récupérer les événements ${type}.`, isUser: false, timestamp: new Date() })
      }
    },
    async sendMessage(text = null) {
      const message = text || this.inputMessage.trim()
      if (!message) return

      // Capturer les 5 derniers messages comme contexte (avant d'ajouter le nouveau message)
      const recent = this.messages.slice(-5).map(m => ({
        role: m.isUser ? 'user' : 'assistant',
        text: m.text,
        timestamp: m.timestamp
      }))

      // Ajouter le message utilisateur localement
      this.messages.push({
        text: message,
        isUser: true,
        timestamp: new Date()
      })

      this.inputMessage = ''
      this.isTyping = true
      this.scrollToBottom()

      try {
        const response = await chatbotService.ask(message, recent)

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

        // Afficher des suggestions inline si le backend en fournit
        if (Array.isArray(response.data?.suggestions) && response.data.suggestions.length) {
          this.inlineSuggestions = response.data.suggestions
        } else {
          this.inlineSuggestions = []
        }

        if (!this.isOpen) {
          this.unreadCount++
        }
      } catch (error) {
        console.error('Erreur chatbot:', error)
        console.error('Error details:', error.response?.data)

        const errorMessage = error.code === 'ECONNABORTED'
          ? "La réponse du chatbot prend trop de temps. Vérifie la configuration IA ou réessaie dans quelques secondes."
          : error.response?.data?.reponse 
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
/* Ensure inline SVGs are centered and don't shift baseline */
.chatbot-toggle svg,
.chatbot-avatar svg,
.message-avatar svg,
.action-btn svg,
.send-btn svg {
  display: block;
  margin: auto;
  line-height: 0;
}

.chatbot-avatar {
  /* Icon color inside the avatar uses currentColor from AppIcon */
  color: var(--chat-avatar-icon-color, var(--color-heading));
}

/* Local theme overrides for the widget when the app provides a choice */
.chatbot-window.theme-dark {
  --chat-header-start: #0f1724;
  --chat-header-end: #12233a;
  --chat-avatar-bot-bg: var(--vt-c-black-soft);
  --chat-avatar-user-bg: #1f6feb;
  --chat-window-bg: var(--color-background);
  --chat-bubble-bot-bg: var(--vt-c-black-soft);
  --color-background: var(--vt-c-black);
  --color-background-soft: var(--vt-c-black-soft);
  --color-text: var(--vt-c-text-dark-2);
  --color-border: var(--vt-c-divider-dark-2);
  --color-heading: var(--vt-c-text-dark-1);
  --chat-avatar-icon-color: var(--vt-c-text-dark-1);
}

.chatbot-window.theme-light {
  --chat-header-start: var(--vt-c-indigo);
  --chat-header-end: #164e9f;
  --chat-avatar-bot-bg: var(--color-background-soft);
  --chat-avatar-user-bg: var(--vt-c-indigo);
  --chat-window-bg: var(--color-background);
  --chat-bubble-bot-bg: var(--color-background-soft);
  --color-background: var(--vt-c-white);
  --color-text: var(--vt-c-text-light-1);
  --color-border: var(--vt-c-divider-light-2);
  --color-heading: var(--vt-c-text-light-1);
  --chat-avatar-icon-color: var(--vt-c-indigo);
  --color-background-soft: var(--vt-c-white-soft);
}

/* Use app variables for color and dark mode compatibility */
.chatbot-container{position:fixed;bottom:20px;right:20px;z-index:9999}
.chatbot-toggle{width:56px;height:56px;border-radius:12px;background:var(--vt-c-indigo);color:#fff;border:none;cursor:pointer;box-shadow:0 10px 30px rgba(2,6,23,0.12);display:flex;align-items:center;justify-content:center;position:relative;transition:transform .18s}
.chatbot-toggle:hover{transform:translateY(-3px)}
.chatbot-icon{font-size:22px}
.chatbot-badge{position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;padding:4px 7px;border-radius:999px;font-size:12px;font-weight:600}
.chatbot-window{width:380px;max-width:calc(100vw - 32px);height:560px;max-height:calc(100vh - 120px);background:var(--color-background);color:var(--color-text);border-radius:12px;display:flex;flex-direction:column;overflow:hidden;border:1px solid var(--color-border);box-shadow:0 20px 50px rgba(2,6,23,0.14)}
.chatbot-header{background:var(--chat-header-start);color:#fff;padding:12px 14px;display:flex;align-items:center;justify-content:space-between;transition:background-color .35s, color .35s}
.chatbot-title{display:flex;gap:10px;align-items:center}
.chatbot-avatar{font-size:26px;display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:var(--chat-avatar-bot-bg);border:1px solid var(--color-border);transition:background-color .25s,border-color .25s}
.chatbot-info h4{margin:0;font-size:15px}
.chatbot-status{font-size:12px;opacity:.95}
.chatbot-actions{display:flex;gap:8px;align-items:center}
.action-btn{background:rgba(255,255,255,0.08);border:none;width:36px;height:36px;border-radius:8px;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center}
.action-btn:hover{background:rgba(255,255,255,0.12)}
.chatbot-messages{flex:1;overflow-y:auto;padding:18px;background:var(--chat-window-bg);transition:background-color .35s}
.welcome-message{text-align:center;padding:14px}.welcome-icon{font-size:44px;margin-bottom:8px}.welcome-message h4{margin:0 0 8px 0;color:var(--color-heading)}.welcome-message p{color:var(--color-text);opacity:.85}
.suggestion-btn{display:block;width:100%;text-align:left;padding:10px 12px;margin-bottom:8px;background:var(--color-background);border:1px solid var(--color-border);border-radius:8px;cursor:pointer;color:var(--color-text)}
.suggestion-btn:hover{background:rgba(37,99,235,0.08);border-color:rgba(37,99,235,0.18);color:var(--vt-c-indigo)}
.message{display:flex;gap:10px;margin-bottom:14px;align-items:center}
.message-avatar{font-size:20px;flex-shrink:0;display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:var(--chat-avatar-bot-bg);border:1px solid var(--color-border);margin-top:0;transition:background-color .25s,border-color .25s}
.message-content{max-width:78%}
.message-text{padding:10px 14px;border-radius:12px;font-size:14px;line-height:1.45}
.user-message{flex-direction:row-reverse}
.user-message .message-avatar{background:var(--chat-avatar-user-bg);border-color:rgba(0,0,0,0.06);}
.user-message .message-avatar svg{color:#fff}
.user-message .message-text{background:var(--vt-c-indigo);color:#fff;border-bottom-right-radius:6px}
.bot-message .message-text{background:var(--chat-bubble-bot-bg);border:1px solid var(--color-border);color:var(--color-text);box-shadow:none;transition:background-color .25s,border-color .25s,color .25s}
.message-time{display:block;font-size:11px;color:var(--color-text);opacity:.6;margin-top:6px}
.typing-indicator{display:flex;gap:6px;align-items:center}.typing-indicator span{width:8px;height:8px;background:var(--vt-c-indigo);border-radius:50%;animation:bounce 1.2s infinite}@keyframes bounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}
.chatbot-input{display:flex;gap:10px;padding:12px;border-top:1px solid var(--color-border);background:var(--color-background)}
.chatbot-input input{flex:1;padding:10px 14px;border-radius:999px;border:1px solid var(--color-border);background:var(--color-background-soft);color:var(--color-text);outline:none}
.chatbot-input input:focus{box-shadow:0 0 0 3px rgba(37,99,235,0.08);border-color:var(--vt-c-indigo)}
.send-btn{width:44px;height:44px;border-radius:10px;background:var(--vt-c-indigo);color:#fff;border:none;cursor:pointer}.send-btn:disabled{opacity:.45;cursor:not-allowed}
.slide-up-enter-active,.slide-up-leave-active{transition:all .25s ease}.slide-up-enter-from,.slide-up-leave-to{opacity:0;transform:translateY(14px)}
@media(max-width:520px){.chatbot-window{width:calc(100vw - 32px);height:calc(100vh - 120px);right:16px;bottom:80px}}
</style>
