<template>
  <div class="self-service-dashboard">
    <!-- En-tête de bienvenue -->
    <div class="welcome-header">
      <div class="welcome-text">
        <h1>Bonjour, {{ profil?.prenom || 'Employé' }} 👋</h1>
        <p>{{ formatDate(new Date()) }} • {{ profil?.poste?.titre || 'Mon espace personnel' }}</p>
      </div>
      <div class="quick-actions">
        <router-link to="/self-service/demande-conge" class="btn btn-primary">
          📅 Demander un congé
        </router-link>
        <router-link to="/self-service/nouvelle-demande" class="btn btn-secondary">
          📝 Nouvelle demande
        </router-link>
      </div>
    </div>

    <!-- Cartes KPI -->
    <div class="kpi-grid">
      <div class="card kpi-card conges">
        <div class="kpi-icon">🏖️</div>
        <div class="kpi-content">
          <span class="kpi-value">{{ soldeConges?.solde_jours || 0 }}</span>
          <span class="kpi-label">Jours de congés restants</span>
        </div>
        <router-link to="/self-service/conges" class="kpi-link">Voir mes congés →</router-link>
      </div>

      <div class="card kpi-card demandes">
        <div class="kpi-icon">📋</div>
        <div class="kpi-content">
          <span class="kpi-value">{{ demandesEnAttente }}</span>
          <span class="kpi-label">Demandes en attente</span>
        </div>
        <router-link to="/self-service/demandes" class="kpi-link">Voir mes demandes →</router-link>
      </div>

      <div class="card kpi-card messages">
        <div class="kpi-icon">💬</div>
        <div class="kpi-content">
          <span class="kpi-value">{{ messagesNonLus }}</span>
          <span class="kpi-label">Messages non lus</span>
        </div>
        <router-link to="/self-service/messagerie" class="kpi-link">Ouvrir messagerie →</router-link>
      </div>

      <div class="card kpi-card formations">
        <div class="kpi-icon">🎓</div>
        <div class="kpi-content">
          <span class="kpi-value">{{ formationsEnCours }}</span>
          <span class="kpi-label">Formations en cours</span>
        </div>
        <router-link to="/self-service/formations" class="kpi-link">Voir mes formations →</router-link>
      </div>
    </div>

    <!-- Section principale -->
    <div class="main-grid">
      <!-- Dernières demandes -->
      <div class="card section-card">
        <div class="section-header">
          <h3>📋 Mes dernières demandes</h3>
          <router-link to="/self-service/demandes" class="link">Tout voir</router-link>
        </div>
        <div class="demandes-list">
          <div v-for="demande in dernieresDemandes" :key="demande.id" class="demande-item">
            <div class="demande-info">
              <span class="demande-type">{{ demande.type?.nom || demande.type_demande }}</span>
              <span class="demande-date">{{ formatDate(demande.created_at) }}</span>
            </div>
            <span class="demande-statut" :class="demande.statut">
              {{ getStatutLabel(demande.statut) }}
            </span>
          </div>
          <div v-if="!dernieresDemandes.length" class="empty-state">
            Aucune demande récente
          </div>
        </div>
      </div>

      <!-- Mes compétences -->
      <div class="card section-card">
        <div class="section-header">
          <h3>🎯 Mes compétences</h3>
          <router-link to="/self-service/competences" class="link">Voir tout</router-link>
        </div>
        <div class="competences-list">
          <div v-for="comp in topCompetences" :key="comp.id" class="competence-item">
            <div class="competence-info">
              <span class="competence-nom">{{ comp.competence?.nom || comp.nom }}</span>
              <span class="competence-categorie">{{ comp.competence?.categorie?.nom }}</span>
            </div>
            <div class="niveau-stars">
              <span v-for="i in 5" :key="i" :class="{ filled: i <= (comp.niveau || comp.niveau_competence_id) }">★</span>
            </div>
          </div>
          <div v-if="!topCompetences.length" class="empty-state">
            Aucune compétence enregistrée
          </div>
        </div>
      </div>

      <!-- Notifications récentes -->
      <div class="card section-card">
        <div class="section-header">
          <h3>🔔 Notifications</h3>
          <button class="link" @click="marquerToutesLues" v-if="notifications.length">Tout marquer lu</button>
        </div>
        <div class="notifications-list">
          <div 
            v-for="notif in notifications.slice(0, 5)" 
            :key="notif.id" 
            class="notification-item"
            :class="{ unread: !notif.lu }"
            @click="marquerLue(notif)"
          >
            <span class="notif-icon">{{ getNotifIcon(notif.type) }}</span>
            <div class="notif-content">
              <span class="notif-titre">{{ notif.titre }}</span>
              <span class="notif-date">{{ formatRelativeDate(notif.created_at) }}</span>
            </div>
          </div>
          <div v-if="!notifications.length" class="empty-state">
            Aucune notification
          </div>
        </div>
      </div>

      <!-- Prochains événements -->
      <div class="card section-card">
        <div class="section-header">
          <h3>📅 Événements à venir</h3>
        </div>
        <div class="events-list">
          <div v-for="event in prochainEvenements" :key="event.id" class="event-item">
            <div class="event-date">
              <span class="day">{{ getDay(event.date_debut) }}</span>
              <span class="month">{{ getMonth(event.date_debut) }}</span>
            </div>
            <div class="event-info">
              <span class="event-titre">{{ event.titre }}</span>
              <span class="event-type">{{ event.type }}</span>
            </div>
          </div>
          <div v-if="!prochainEvenements.length" class="empty-state">
            Aucun événement prévu
          </div>
        </div>
      </div>
    </div>

    <!-- Accès rapides -->
    <div class="quick-access">
      <h3>Accès rapides</h3>
      <div class="access-grid">
        <router-link to="/self-service/profil" class="access-item">
          <span class="icon">👤</span>
          <span>Mon profil</span>
        </router-link>
        <router-link to="/self-service/bulletins" class="access-item">
          <span class="icon">📄</span>
          <span>Mes bulletins</span>
        </router-link>
        <router-link to="/self-service/conges" class="access-item">
          <span class="icon">🏖️</span>
          <span>Mes congés</span>
        </router-link>
        <router-link to="/self-service/demandes" class="access-item">
          <span class="icon">📝</span>
          <span>Mes demandes</span>
        </router-link>
        <router-link to="/self-service/competences" class="access-item">
          <span class="icon">🎯</span>
          <span>Compétences</span>
        </router-link>
        <router-link to="/self-service/formations" class="access-item">
          <span class="icon">🎓</span>
          <span>Formations</span>
        </router-link>
        <router-link to="/self-service/messagerie" class="access-item">
          <span class="icon">💬</span>
          <span>Messagerie RH</span>
        </router-link>
        <router-link to="/self-service/documents" class="access-item">
          <span class="icon">📁</span>
          <span>Documents</span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import selfServiceService from '../../services/selfServiceService'
import messagerieService from '../../services/messagerieService'

const loading = ref(false)
const profil = ref(null)
const soldeConges = ref(null)
const demandes = ref([])
const competences = ref([])
const formations = ref([])
const notifications = ref([])
const conversations = ref([])
const evenements = ref([])

// Computed
const demandesEnAttente = computed(() => 
  demandes.value.filter(d => ['brouillon', 'soumise', 'en_cours'].includes(d.statut)).length
)

const messagesNonLus = computed(() => {
  return conversations.value.reduce((total, c) => total + (c.messages_non_lus || 0), 0)
})

const formationsEnCours = computed(() =>
  formations.value.filter(f => f.statut === 'en_cours').length
)

const dernieresDemandes = computed(() => demandes.value.slice(0, 4))
const topCompetences = computed(() => competences.value.slice(0, 5))
const prochainEvenements = computed(() => evenements.value.slice(0, 3))

// Helpers
const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  })
}

const formatRelativeDate = (date) => {
  const now = new Date()
  const d = new Date(date)
  const diff = Math.floor((now - d) / 1000 / 60)
  
  if (diff < 60) return `Il y a ${diff} min`
  if (diff < 1440) return `Il y a ${Math.floor(diff / 60)}h`
  return d.toLocaleDateString('fr-FR')
}

const getDay = (date) => new Date(date).getDate()
const getMonth = (date) => new Date(date).toLocaleDateString('fr-FR', { month: 'short' })

const getStatutLabel = (statut) => {
  const labels = {
    brouillon: 'Brouillon',
    soumise: 'Soumise',
    en_cours: 'En cours',
    approuvee: 'Approuvée',
    rejetee: 'Rejetée',
    annulee: 'Annulée'
  }
  return labels[statut] || statut
}

const getNotifIcon = (type) => {
  const icons = {
    demande: '📋',
    conge: '🏖️',
    message: '💬',
    formation: '🎓',
    alerte: '⚠️'
  }
  return icons[type] || '🔔'
}

// Actions
const marquerLue = async (notif) => {
  if (notif.lu) return
  try {
    await messagerieService.marquerLue(notif.id)
    notif.lu = true
  } catch (error) {
    console.error('Erreur:', error)
  }
}

const marquerToutesLues = async () => {
  try {
    await messagerieService.marquerToutesLues()
    notifications.value.forEach(n => n.lu = true)
  } catch (error) {
    console.error('Erreur:', error)
  }
}

// Load data
const loadData = async () => {
  loading.value = true
  try {
    const res = await selfServiceService.getDashboard()
    const data = res.data
    
    profil.value = data.profil
    soldeConges.value = data.solde_conges
    demandes.value = data.demandes || []
    competences.value = data.competences || []
    formations.value = data.formations || []
    notifications.value = data.notifications || []
    conversations.value = data.conversations || []
    evenements.value = data.evenements || []
  } catch (error) {
    console.error('Erreur chargement dashboard:', error)
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>

<style scoped>
.self-service-dashboard {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

.welcome-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
}

.welcome-text h1 {
  margin: 0 0 5px 0;
  font-size: 1.8rem;
}

.welcome-text p {
  margin: 0;
  color: #666;
}

.quick-actions {
  display: flex;
  gap: 10px;
}

/* KPI Cards */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 25px;
}

.kpi-card {
  padding: 20px;
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
}

.kpi-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
}

.kpi-card.conges::before { background: #11998e; }
.kpi-card.demandes::before { background: #667eea; }
.kpi-card.messages::before { background: #f5576c; }
.kpi-card.formations::before { background: #f093fb; }

.kpi-icon {
  font-size: 2rem;
  margin-bottom: 10px;
}

.kpi-value {
  font-size: 2.5rem;
  font-weight: bold;
  color: #333;
}

.kpi-label {
  color: #666;
  font-size: 0.9rem;
}

.kpi-link {
  margin-top: 12px;
  color: #667eea;
  font-size: 0.85rem;
  text-decoration: none;
}

.kpi-link:hover {
  text-decoration: underline;
}

/* Main Grid */
.main-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  margin-bottom: 30px;
}

@media (max-width: 1024px) {
  .main-grid {
    grid-template-columns: 1fr;
  }
}

.section-card {
  padding: 20px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.section-header h3 {
  margin: 0;
  font-size: 1.1rem;
}

.section-header .link {
  color: #667eea;
  text-decoration: none;
  font-size: 0.85rem;
  background: none;
  border: none;
  cursor: pointer;
}

/* Demandes list */
.demandes-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.demande-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 8px;
}

.demande-info {
  display: flex;
  flex-direction: column;
}

.demande-type {
  font-weight: 500;
}

.demande-date {
  font-size: 0.8rem;
  color: #888;
}

.demande-statut {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.demande-statut.brouillon { background: #e8e8e8; color: #666; }
.demande-statut.soumise { background: #e3f2fd; color: #1976d2; }
.demande-statut.en_cours { background: #fff3e0; color: #f57c00; }
.demande-statut.approuvee { background: #e8f5e9; color: #388e3c; }
.demande-statut.rejetee { background: #ffebee; color: #c62828; }
.demande-statut.annulee { background: #f5f5f5; color: #999; }

/* Compétences list */
.competences-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.competence-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  background: #f8f9fa;
  border-radius: 8px;
}

.competence-nom {
  font-weight: 500;
}

.competence-categorie {
  font-size: 0.8rem;
  color: #888;
}

.niveau-stars {
  color: #ddd;
}

.niveau-stars .filled {
  color: #ffc107;
}

/* Notifications list */
.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.notification-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}

.notification-item:hover {
  background: #f5f5f5;
}

.notification-item.unread {
  background: #e3f2fd;
}

.notif-icon {
  font-size: 1.2rem;
}

.notif-content {
  display: flex;
  flex-direction: column;
}

.notif-titre {
  font-size: 0.9rem;
}

.notif-date {
  font-size: 0.75rem;
  color: #888;
}

/* Events list */
.events-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.event-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 10px;
}

.event-date {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.event-date .day {
  font-size: 1.2rem;
  font-weight: bold;
  line-height: 1;
}

.event-date .month {
  font-size: 0.7rem;
  text-transform: uppercase;
}

.event-info {
  display: flex;
  flex-direction: column;
}

.event-titre {
  font-weight: 500;
}

.event-type {
  font-size: 0.8rem;
  color: #888;
}

/* Quick access */
.quick-access {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 20px;
}

.quick-access h3 {
  margin: 0 0 15px 0;
}

.access-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 15px;
}

.access-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 20px;
  background: white;
  border-radius: 10px;
  text-decoration: none;
  color: #333;
  transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.access-item:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.access-item .icon {
  font-size: 1.5rem;
}

.access-item span:last-child {
  font-size: 0.85rem;
  text-align: center;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 20px;
  color: #999;
}

/* Buttons */
.btn {
  padding: 10px 18px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5a6fd6;
}

.btn-secondary {
  background: #e8e8e8;
  color: #333;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
</style>
