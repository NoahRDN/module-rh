<template>
  <div class="messagerie-view">
    <div class="page-header">
      <h1>💬 Messagerie RH</h1>
      <button class="btn btn-primary" @click="showNewConversation = true">
        + Nouveau message
      </button>
    </div>

    <div class="messagerie-container">
      <!-- Liste des conversations -->
      <div class="conversations-panel">
        <div class="search-box">
          <input type="text" v-model="recherche" placeholder="🔍 Rechercher..." />
        </div>

        <div class="conversations-list">
          <div 
            v-for="conv in conversationsFiltrees" 
            :key="conv.id" 
            class="conversation-item"
            :class="{ active: selectedConversation?.id === conv.id, unread: conv.messages_non_lus > 0 }"
            @click="selectConversation(conv)"
          >
            <div class="conv-avatar" :class="conv.priorite">
              {{ getInitiales(conv) }}
            </div>
            <div class="conv-content">
              <div class="conv-header">
                <span class="conv-sujet">{{ conv.sujet }}</span>
                <span class="conv-date">{{ formatRelativeDate(conv.dernier_message_at || conv.created_at) }}</span>
              </div>
              <p class="conv-preview">{{ conv.dernier_message?.contenu || 'Aucun message' }}</p>
            </div>
            <div v-if="conv.messages_non_lus > 0" class="unread-badge">
              {{ conv.messages_non_lus }}
            </div>
          </div>

          <div v-if="!conversationsFiltrees.length" class="empty-state">
            <p>Aucune conversation</p>
          </div>
        </div>
      </div>

      <!-- Zone de conversation -->
      <div class="conversation-panel">
        <div v-if="selectedConversation" class="conversation-active">
          <!-- En-tête -->
          <div class="conversation-header">
            <div class="header-info">
              <h3>{{ selectedConversation.sujet }}</h3>
              <div class="header-meta">
                <span class="statut-badge" :class="selectedConversation.statut">
                  {{ getStatutLabel(selectedConversation.statut) }}
                </span>
                <span class="priorite-badge" :class="selectedConversation.priorite">
                  {{ selectedConversation.priorite }}
                </span>
              </div>
            </div>
          </div>

          <!-- Messages -->
          <div class="messages-container" ref="messagesContainer">
            <div 
              v-for="message in messages" 
              :key="message.id" 
              class="message"
              :class="{ own: isOwnMessage(message) }"
            >
              <div class="message-avatar">
                {{ message.expediteur?.prenom?.[0] || '?' }}{{ message.expediteur?.nom?.[0] || '' }}
              </div>
              <div class="message-content">
                <div class="message-header">
                  <span class="sender-name">
                    {{ message.expediteur?.prenom }} {{ message.expediteur?.nom }}
                  </span>
                  <span class="message-date">{{ formatDateTime(message.created_at) }}</span>
                </div>
                <div class="message-body">
                  {{ message.contenu }}
                </div>
                <!-- Pièces jointes -->
                <div v-if="message.pieces_jointes?.length" class="message-attachments">
                  <a 
                    v-for="pj in message.pieces_jointes" 
                    :key="pj.id" 
                    :href="pj.chemin" 
                    target="_blank"
                    class="attachment-item"
                  >
                    📎 {{ pj.nom_original }}
                  </a>
                </div>
              </div>
            </div>

            <div v-if="!messages.length" class="no-messages">
              Aucun message dans cette conversation
            </div>
          </div>

          <!-- Zone de saisie -->
          <div class="message-input-area">
            <div class="input-container">
              <textarea 
                v-model="newMessage" 
                placeholder="Tapez votre message..."
                @keydown.enter.exact.prevent="envoyerMessage"
                rows="2"
              ></textarea>
              <div class="input-actions">
                <label class="btn-attachment">
                  📎
                  <input type="file" @change="handleAttachment" multiple hidden />
                </label>
                <button class="btn btn-primary btn-send" @click="envoyerMessage" :disabled="!newMessage.trim() || sending">
                  {{ sending ? '...' : '➤' }}
                </button>
              </div>
            </div>
            <div v-if="attachments.length" class="attachments-preview">
              <span v-for="(file, index) in attachments" :key="index" class="attachment-tag">
                {{ file.name }}
                <button @click="removeAttachment(index)">×</button>
              </span>
            </div>
          </div>
        </div>

        <!-- État vide -->
        <div v-else class="no-conversation-selected">
          <div class="empty-icon">💬</div>
          <h3>Sélectionnez une conversation</h3>
          <p>ou créez une nouvelle conversation avec les RH</p>
          <button class="btn btn-primary" @click="showNewConversation = true">
            Nouveau message
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Nouvelle conversation -->
    <div v-if="showNewConversation" class="modal-overlay" @click.self="showNewConversation = false">
      <div class="modal">
        <div class="modal-header">
          <h2>Nouveau message aux RH</h2>
          <button class="btn btn-icon" @click="showNewConversation = false">✕</button>
        </div>
        <form @submit.prevent="creerConversation" class="modal-body">
          <div class="form-group">
            <label>Sujet *</label>
            <input type="text" v-model="newConvForm.sujet" required 
              placeholder="Objet de votre message" />
          </div>

          <div class="form-group">
            <label>Catégorie</label>
            <select v-model="newConvForm.categorie">
              <option value="general">Question générale</option>
              <option value="conges">Congés</option>
              <option value="paie">Paie</option>
              <option value="contrat">Contrat</option>
              <option value="formation">Formation</option>
              <option value="autre">Autre</option>
            </select>
          </div>

          <div class="form-group">
            <label>Priorité</label>
            <select v-model="newConvForm.priorite">
              <option value="basse">Basse</option>
              <option value="normale">Normale</option>
              <option value="haute">Haute</option>
              <option value="urgente">Urgente</option>
            </select>
          </div>

          <div class="form-group">
            <label>Message *</label>
            <textarea v-model="newConvForm.message" required rows="5"
              placeholder="Décrivez votre demande..."></textarea>
          </div>

          <div class="form-group">
            <label>Pièces jointes</label>
            <input type="file" @change="handleNewConvAttachment" multiple />
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="showNewConversation = false">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary" :disabled="creating">
              {{ creating ? 'Envoi...' : 'Envoyer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import messagerieService from '../../services/messagerieService'

const loading = ref(false)
const sending = ref(false)
const creating = ref(false)
const recherche = ref('')
const conversations = ref([])
const selectedConversation = ref(null)
const messages = ref([])
const newMessage = ref('')
const attachments = ref([])
const messagesContainer = ref(null)
const showNewConversation = ref(false)
const currentUserId = ref(null)

const newConvForm = ref({
  sujet: '',
  categorie: 'general',
  priorite: 'normale',
  message: ''
})
const newConvAttachments = ref([])

// Computed
const conversationsFiltrees = computed(() => {
  if (!recherche.value) return conversations.value
  const term = recherche.value.toLowerCase()
  return conversations.value.filter(c => 
    c.sujet.toLowerCase().includes(term) ||
    c.dernier_message?.contenu?.toLowerCase().includes(term)
  )
})

// Helpers
const formatRelativeDate = (date) => {
  const now = new Date()
  const d = new Date(date)
  const diff = Math.floor((now - d) / 1000 / 60)
  
  if (diff < 1) return 'À l\'instant'
  if (diff < 60) return `${diff} min`
  if (diff < 1440) return `${Math.floor(diff / 60)}h`
  if (diff < 2880) return 'Hier'
  return d.toLocaleDateString('fr-FR')
}

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getInitiales = (conv) => {
  if (conv.employe) {
    return (conv.employe.prenom?.[0] || '') + (conv.employe.nom?.[0] || '')
  }
  return 'RH'
}

const getStatutLabel = (statut) => {
  const labels = {
    ouverte: 'Ouverte',
    en_attente: 'En attente',
    en_cours: 'En cours',
    resolue: 'Résolue',
    fermee: 'Fermée'
  }
  return labels[statut] || statut
}

const isOwnMessage = (message) => {
  return message.expediteur_id === currentUserId.value
}

const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

// Load
const loadConversations = async () => {
  loading.value = true
  try {
    const res = await messagerieService.getConversations()
    conversations.value = res.data.data || res.data
    
    // Get current user id from localStorage
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    currentUserId.value = user.id
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    loading.value = false
  }
}

const selectConversation = async (conv) => {
  selectedConversation.value = conv
  
  try {
    const res = await messagerieService.getConversation(conv.id)
    messages.value = res.data.messages || []
    
    // Marquer comme lu
    if (conv.messages_non_lus > 0) {
      await messagerieService.marquerLu(conv.id)
      conv.messages_non_lus = 0
    }
    
    scrollToBottom()
  } catch (error) {
    console.error('Erreur:', error)
  }
}

// Watch for new messages to scroll
watch(messages, () => {
  scrollToBottom()
}, { deep: true })

// Actions
const envoyerMessage = async () => {
  if (!newMessage.value.trim() || !selectedConversation.value) return
  
  sending.value = true
  try {
    const formData = new FormData()
    formData.append('contenu', newMessage.value)
    
    attachments.value.forEach((file, index) => {
      formData.append(`pieces_jointes[${index}]`, file)
    })

    const res = await messagerieService.envoyerMessage(selectedConversation.value.id, formData)
    messages.value.push(res.data.data || res.data)
    newMessage.value = ''
    attachments.value = []
    scrollToBottom()
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  } finally {
    sending.value = false
  }
}

const handleAttachment = (event) => {
  attachments.value = [...attachments.value, ...Array.from(event.target.files)]
}

const removeAttachment = (index) => {
  attachments.value.splice(index, 1)
}

const handleNewConvAttachment = (event) => {
  newConvAttachments.value = Array.from(event.target.files)
}

const creerConversation = async () => {
  creating.value = true
  try {
    const formData = new FormData()
    formData.append('sujet', newConvForm.value.sujet)
    formData.append('categorie', newConvForm.value.categorie)
    formData.append('priorite', newConvForm.value.priorite)
    formData.append('message', newConvForm.value.message)
    
    newConvAttachments.value.forEach((file, index) => {
      formData.append(`pieces_jointes[${index}]`, file)
    })

    const res = await messagerieService.creerConversation(formData)
    const newConv = res.data.data || res.data
    
    conversations.value.unshift(newConv)
    showNewConversation.value = false
    resetNewConvForm()
    
    // Sélectionner la nouvelle conversation
    selectConversation(newConv)
  } catch (error) {
    alert('Erreur: ' + (error.response?.data?.message || error.message))
  } finally {
    creating.value = false
  }
}

const resetNewConvForm = () => {
  newConvForm.value = {
    sujet: '',
    categorie: 'general',
    priorite: 'normale',
    message: ''
  }
  newConvAttachments.value = []
}

onMounted(loadConversations)
</script>

<style scoped>
.messagerie-view {
  padding: 20px;
  height: calc(100vh - 100px);
  display: flex;
  flex-direction: column;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.page-header h1 {
  margin: 0;
}

.messagerie-container {
  flex: 1;
  display: flex;
  gap: 20px;
  min-height: 0;
}

/* Conversations Panel */
.conversations-panel {
  width: 350px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.search-box {
  padding: 15px;
  border-bottom: 1px solid #eee;
}

.search-box input {
  width: 100%;
  padding: 10px 15px;
  border: 1px solid #ddd;
  border-radius: 20px;
  font-size: 0.9rem;
}

.conversations-list {
  flex: 1;
  overflow-y: auto;
}

.conversation-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 15px;
  cursor: pointer;
  transition: background 0.2s;
  border-bottom: 1px solid #f5f5f5;
}

.conversation-item:hover {
  background: #f8f9fa;
}

.conversation-item.active {
  background: #e3f2fd;
}

.conversation-item.unread {
  background: #fff8e1;
}

.conv-avatar {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  background: #667eea;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.conv-avatar.haute,
.conv-avatar.urgente {
  background: #f44336;
}

.conv-content {
  flex: 1;
  min-width: 0;
}

.conv-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 3px;
}

.conv-sujet {
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.conv-date {
  font-size: 0.75rem;
  color: #888;
  flex-shrink: 0;
}

.conv-preview {
  margin: 0;
  font-size: 0.85rem;
  color: #666;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.unread-badge {
  background: #f44336;
  color: white;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: bold;
}

/* Conversation Panel */
.conversation-panel {
  flex: 1;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.conversation-active {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.conversation-header {
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.header-info h3 {
  margin: 0 0 8px 0;
}

.header-meta {
  display: flex;
  gap: 10px;
}

.statut-badge,
.priorite-badge {
  padding: 3px 10px;
  border-radius: 10px;
  font-size: 0.75rem;
  font-weight: 500;
}

.statut-badge.ouverte { background: #e3f2fd; color: #1976d2; }
.statut-badge.en_attente { background: #fff3e0; color: #f57c00; }
.statut-badge.en_cours { background: #e8f5e9; color: #388e3c; }
.statut-badge.resolue { background: #e0f2f1; color: #00897b; }
.statut-badge.fermee { background: #f5f5f5; color: #666; }

.priorite-badge.basse { background: #f5f5f5; color: #666; }
.priorite-badge.normale { background: #e3f2fd; color: #1976d2; }
.priorite-badge.haute { background: #fff3e0; color: #f57c00; }
.priorite-badge.urgente { background: #ffebee; color: #c62828; }

/* Messages */
.messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.message {
  display: flex;
  gap: 12px;
  max-width: 80%;
}

.message.own {
  margin-left: auto;
  flex-direction: row-reverse;
}

.message-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #e8e8e8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: bold;
  flex-shrink: 0;
}

.message.own .message-avatar {
  background: #667eea;
  color: white;
}

.message-content {
  background: #f5f5f5;
  padding: 12px 15px;
  border-radius: 12px;
  border-top-left-radius: 4px;
}

.message.own .message-content {
  background: #667eea;
  color: white;
  border-radius: 12px;
  border-top-right-radius: 4px;
}

.message-header {
  display: flex;
  justify-content: space-between;
  gap: 15px;
  margin-bottom: 5px;
}

.sender-name {
  font-weight: 500;
  font-size: 0.85rem;
}

.message-date {
  font-size: 0.75rem;
  opacity: 0.7;
}

.message-body {
  white-space: pre-wrap;
  word-break: break-word;
}

.message-attachments {
  margin-top: 10px;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.attachment-item {
  font-size: 0.85rem;
  color: inherit;
  text-decoration: underline;
}

.no-messages {
  text-align: center;
  color: #999;
  padding: 50px;
}

/* Input area */
.message-input-area {
  padding: 15px 20px;
  border-top: 1px solid #eee;
}

.input-container {
  display: flex;
  gap: 10px;
  align-items: flex-end;
}

.input-container textarea {
  flex: 1;
  padding: 12px 15px;
  border: 1px solid #ddd;
  border-radius: 20px;
  resize: none;
  font-family: inherit;
}

.input-actions {
  display: flex;
  gap: 8px;
}

.btn-attachment {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 1.1rem;
}

.btn-send {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  padding: 0;
  font-size: 1.1rem;
}

.attachments-preview {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.attachment-tag {
  display: flex;
  align-items: center;
  gap: 5px;
  background: #e8e8e8;
  padding: 5px 10px;
  border-radius: 15px;
  font-size: 0.85rem;
}

.attachment-tag button {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  line-height: 1;
}

/* No conversation selected */
.no-conversation-selected {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #888;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 20px;
}

.no-conversation-selected h3 {
  margin: 0 0 5px 0;
  color: #333;
}

.no-conversation-selected p {
  margin: 0 0 20px 0;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 40px;
  color: #999;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.modal-header h2 {
  margin: 0;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

/* Buttons */
.btn {
  padding: 10px 18px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-secondary {
  background: #e8e8e8;
  color: #333;
}

.btn-icon {
  padding: 6px 10px;
  background: transparent;
}
</style>
