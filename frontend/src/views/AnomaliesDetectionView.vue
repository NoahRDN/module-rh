<template>
  <div class="anomalies-view">
    <div class="page-header">
      <h1>🔍 Détection d'Anomalies</h1>
      <p class="subtitle">Surveillance automatique des données RH</p>
    </div>

    <!-- Dashboard Stats -->
    <div class="stats-grid">
      <div class="stat-card critical" @click="filterBySeverity('critique')">
        <span class="stat-icon">🚨</span>
        <div class="stat-content">
          <span class="stat-value">{{ dashboard.par_severite?.critique || 0 }}</span>
          <span class="stat-label">Critiques</span>
        </div>
      </div>
      <div class="stat-card high" @click="filterBySeverity('haute')">
        <span class="stat-icon">⚠️</span>
        <div class="stat-content">
          <span class="stat-value">{{ dashboard.par_severite?.haute || 0 }}</span>
          <span class="stat-label">Hautes</span>
        </div>
      </div>
      <div class="stat-card medium" @click="filterBySeverity('moyenne')">
        <span class="stat-icon">⚡</span>
        <div class="stat-content">
          <span class="stat-value">{{ dashboard.par_severite?.moyenne || 0 }}</span>
          <span class="stat-label">Moyennes</span>
        </div>
      </div>
      <div class="stat-card low" @click="filterBySeverity('basse')">
        <span class="stat-icon">ℹ️</span>
        <div class="stat-content">
          <span class="stat-value">{{ dashboard.par_severite?.basse || 0 }}</span>
          <span class="stat-label">Basses</span>
        </div>
      </div>
    </div>

    <!-- Onglets par catégorie -->
    <div class="card">
      <div class="tabs">
        <button 
          v-for="tab in tabs" 
          :key="tab.key"
          class="tab-btn"
          :class="{ active: activeTab === tab.key }"
          @click="setActiveTab(tab.key)"
        >
          <span class="tab-icon">{{ tab.icon }}</span>
          <span class="tab-label">{{ tab.label }}</span>
          <span class="tab-count" v-if="getCategoryCount(tab.key) > 0">
            {{ getCategoryCount(tab.key) }}
          </span>
        </button>
      </div>

      <!-- Filtres -->
      <div class="filters-bar">
        <div class="filter-group">
          <label>Sévérité</label>
          <select v-model="filters.severite" @change="applyFilters">
            <option value="">Toutes</option>
            <option value="critique">Critique</option>
            <option value="haute">Haute</option>
            <option value="moyenne">Moyenne</option>
            <option value="basse">Basse</option>
          </select>
        </div>
        <div class="filter-group" v-if="activeTab === 'pointage' || activeTab === 'paie'">
          <label>Période</label>
          <input type="date" v-model="filters.dateDebut" @change="applyFilters" />
          <span>à</span>
          <input type="date" v-model="filters.dateFin" @change="applyFilters" />
        </div>
        <button class="btn btn-primary" @click="loadData">
          🔄 Actualiser
        </button>
      </div>

      <!-- Contenu des onglets -->
      <div class="tab-content">
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Analyse en cours...</p>
        </div>

        <div v-else-if="filteredAnomalies.length === 0" class="empty-state">
          <span class="empty-icon">✨</span>
          <h3>Aucune anomalie détectée</h3>
          <p>Tout semble en ordre pour cette catégorie</p>
        </div>

        <div v-else class="anomalies-list">
          <div 
            v-for="(anomaly, index) in filteredAnomalies" 
            :key="index"
            class="anomaly-card"
            :class="'severity-' + anomaly.severite"
          >
            <div class="anomaly-icon">
              {{ getSeverityIcon(anomaly.severite) }}
            </div>
            
            <div class="anomaly-content">
              <div class="anomaly-header">
                <h4>{{ anomaly.message || getAnomalyTitle(anomaly.type) }}</h4>
                <span class="severity-badge" :class="anomaly.severite">
                  {{ anomaly.severite }}
                </span>
              </div>
              
              <div class="anomaly-details">
                <span v-if="anomaly.employe">
                  👤 {{ anomaly.employe.nom }} {{ anomaly.employe.prenom }}
                </span>
                <span v-if="anomaly.date">
                  📅 {{ formatDate(anomaly.date) }}
                </span>
                <span v-if="anomaly.valeur">
                  📊 {{ anomaly.valeur }}
                </span>
              </div>

              <p class="anomaly-description" v-if="anomaly.description">
                {{ anomaly.description }}
              </p>
            </div>

            <div class="anomaly-actions">
              <button 
                class="btn btn-sm btn-outline" 
                @click="viewDetails(anomaly)"
                title="Voir détails"
              >
                👁️
              </button>
              <button 
                class="btn btn-sm btn-outline" 
                @click="resolveAnomaly(anomaly)"
                title="Résoudre"
              >
                ✅
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Alertes critiques flottantes -->
    <transition-group name="slide" tag="div" class="critical-alerts">
      <div 
        v-for="alert in criticalAlerts" 
        :key="alert.id"
        class="critical-alert"
      >
        <span class="alert-icon">🚨</span>
        <div class="alert-content">
          <strong>{{ alert.message }}</strong>
          <p>{{ alert.employe?.nom }} {{ alert.employe?.prenom }}</p>
        </div>
        <button @click="dismissAlert(alert)" class="dismiss-btn">✕</button>
      </div>
    </transition-group>
  </div>
</template>

<script>
import anomalyService from '@/services/anomalyService'

export default {
  name: 'AnomaliesView',
  data() {
    return {
      loading: false,
      dashboard: {},
      anomalies: {
        pointage: [],
        paie: [],
        conges: [],
        contrats: [],
        heures: []
      },
      activeTab: 'pointage',
      filters: {
        severite: '',
        dateDebut: '',
        dateFin: ''
      },
      criticalAlerts: [],
      tabs: [
        { key: 'pointage', label: 'Pointage', icon: '⏰' },
        { key: 'paie', label: 'Paie', icon: '💰' },
        { key: 'conges', label: 'Congés', icon: '🏖️' },
        { key: 'contrats', label: 'Contrats', icon: '📝' },
        { key: 'heures', label: 'Heures', icon: '⏱️' }
      ]
    }
  },
  computed: {
    filteredAnomalies() {
      let list = this.anomalies[this.activeTab] || []
      
      if (this.filters.severite) {
        list = list.filter(a => a.severite === this.filters.severite)
      }
      
      return list.sort((a, b) => {
        const order = { critique: 0, haute: 1, moyenne: 2, basse: 3 }
        return (order[a.severite] || 4) - (order[b.severite] || 4)
      })
    }
  },
  mounted() {
    this.loadData()
    this.loadCriticalAlerts()
  },
  methods: {
    async loadData() {
      this.loading = true
      try {
        const [dashboardRes, pointageRes, paieRes, congesRes, contratsRes, heuresRes] = await Promise.all([
          anomalyService.getDashboard(),
          anomalyService.detecterPointage(this.getDateParams()),
          anomalyService.detecterPaie(this.getDateParams()),
          anomalyService.detecterConges(),
          anomalyService.detecterContrats(),
          anomalyService.detecterHeures()
        ])
        
        this.dashboard = dashboardRes.data
        this.anomalies.pointage = pointageRes.data.anomalies || []
        this.anomalies.paie = paieRes.data.anomalies || []
        this.anomalies.conges = congesRes.data.anomalies || []
        this.anomalies.contrats = contratsRes.data.anomalies || []
        this.anomalies.heures = heuresRes.data.anomalies || []
      } catch (error) {
        console.error('Erreur chargement anomalies:', error)
      } finally {
        this.loading = false
      }
    },
    async loadCriticalAlerts() {
      try {
        const response = await anomalyService.getAlertesCritiques()
        this.criticalAlerts = (response.data.anomalies || [])
          .slice(0, 3)
          .map((a, i) => ({ ...a, id: i }))
      } catch (error) {
        console.error('Erreur chargement alertes:', error)
      }
    },
    getDateParams() {
      const params = {}
      if (this.filters.dateDebut) params.date_debut = this.filters.dateDebut
      if (this.filters.dateFin) params.date_fin = this.filters.dateFin
      return params
    },
    setActiveTab(key) {
      this.activeTab = key
    },
    filterBySeverity(severity) {
      this.filters.severite = severity
    },
    applyFilters() {
      this.loadData()
    },
    getCategoryCount(key) {
      return (this.anomalies[key] || []).length
    },
    getSeverityIcon(severity) {
      const icons = {
        critique: '🚨',
        haute: '⚠️',
        moyenne: '⚡',
        basse: 'ℹ️'
      }
      return icons[severity] || '📋'
    },
    getAnomalyTitle(type) {
      const titles = {
        pointage_incomplet: 'Pointage incomplet',
        retard_significatif: 'Retard significatif',
        depart_anticipe: 'Départ anticipé',
        heures_excessives: 'Heures excessives',
        pointage_weekend: 'Pointage weekend',
        absence_non_justifiee: 'Absence non justifiée',
        variation_salaire_importante: 'Variation de salaire importante',
        heures_sup_excessives: 'Heures supplémentaires excessives',
        salaire_negatif: 'Salaire négatif',
        retenues_excessives: 'Retenues excessives',
        paie_manquante: 'Paie manquante',
        conge_passe_non_traite: 'Congé passé non traité',
        conge_tres_long: 'Congé très long',
        chevauchement_conges: 'Chevauchement de congés',
        contrats_multiples_actifs: 'Contrats multiples actifs',
        contrat_expire: 'Contrat expiré',
        periode_essai_non_validee: 'Période d\'essai non validée',
        cdd_sans_date_fin: 'CDD sans date de fin',
        employe_sans_contrat_actif: 'Employé sans contrat actif',
        heures_hebdo_excessives: 'Heures hebdomadaires excessives',
        heures_insuffisantes: 'Heures insuffisantes'
      }
      return titles[type] || type
    },
    formatDate(date) {
      if (!date) return ''
      return new Date(date).toLocaleDateString('fr-FR')
    },
    viewDetails(anomaly) {
      // Naviguer vers la ressource concernée
      if (anomaly.employe_id) {
        this.$router.push(`/employes/${anomaly.employe_id}`)
      }
    },
    resolveAnomaly(anomaly) {
      // Marquer comme résolu (à implémenter)
      console.log('Résoudre:', anomaly)
    },
    dismissAlert(alert) {
      const index = this.criticalAlerts.indexOf(alert)
      if (index > -1) {
        this.criticalAlerts.splice(index, 1)
      }
    }
  }
}
</script>

<style scoped>
.anomalies-view {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 30px;
}

.page-header h1 {
  font-size: 28px;
  color: #1e293b;
  margin: 0;
}

.subtitle {
  color: #64748b;
  margin-top: 5px;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  border-left: 4px solid transparent;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-card.critical { border-left-color: #ef4444; background: #fef2f2; }
.stat-card.high { border-left-color: #f97316; background: #fff7ed; }
.stat-card.medium { border-left-color: #eab308; background: #fefce8; }
.stat-card.low { border-left-color: #3b82f6; background: #eff6ff; }

.stat-icon {
  font-size: 28px;
}

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #1e293b;
}

.stat-label {
  font-size: 14px;
  color: #64748b;
}

/* Card */
.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

/* Tabs */
.tabs {
  display: flex;
  border-bottom: 1px solid #e5e7eb;
  overflow-x: auto;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 15px 25px;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.tab-btn:hover {
  background: #f8fafc;
}

.tab-btn.active {
  border-bottom-color: #2563eb;
  color: #2563eb;
}

.tab-icon {
  font-size: 18px;
}

.tab-label {
  font-size: 14px;
  font-weight: 500;
}

.tab-count {
  background: #ef4444;
  color: white;
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 10px;
}

/* Filters */
.filters-bar {
  display: flex;
  align-items: flex-end;
  gap: 20px;
  padding: 20px;
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.filter-group label {
  font-size: 12px;
  color: #64748b;
}

.filter-group select,
.filter-group input {
  padding: 8px 12px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 14px;
}

.filter-group span {
  color: #94a3b8;
  align-self: flex-end;
  margin-bottom: 8px;
}

/* Tab Content */
.tab-content {
  min-height: 400px;
}

.loading-state,
.empty-state {
  padding: 60px;
  text-align: center;
  color: #64748b;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e5e7eb;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-icon {
  font-size: 48px;
  display: block;
  margin-bottom: 15px;
}

/* Anomalies List */
.anomalies-list {
  padding: 20px;
}

.anomaly-card {
  display: flex;
  align-items: flex-start;
  gap: 15px;
  padding: 15px;
  border-radius: 10px;
  margin-bottom: 12px;
  transition: all 0.2s;
  border-left: 4px solid transparent;
}

.anomaly-card.severity-critique {
  background: #fef2f2;
  border-left-color: #ef4444;
}

.anomaly-card.severity-haute {
  background: #fff7ed;
  border-left-color: #f97316;
}

.anomaly-card.severity-moyenne {
  background: #fefce8;
  border-left-color: #eab308;
}

.anomaly-card.severity-basse {
  background: #eff6ff;
  border-left-color: #3b82f6;
}

.anomaly-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.anomaly-content {
  flex: 1;
}

.anomaly-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.anomaly-header h4 {
  margin: 0;
  font-size: 15px;
  color: #1e293b;
}

.severity-badge {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 4px;
  text-transform: uppercase;
  font-weight: 600;
}

.severity-badge.critique { background: #ef4444; color: white; }
.severity-badge.haute { background: #f97316; color: white; }
.severity-badge.moyenne { background: #eab308; color: white; }
.severity-badge.basse { background: #3b82f6; color: white; }

.anomaly-details {
  display: flex;
  gap: 15px;
  font-size: 13px;
  color: #64748b;
  margin-bottom: 5px;
}

.anomaly-description {
  margin: 0;
  font-size: 13px;
  color: #64748b;
}

.anomaly-actions {
  display: flex;
  gap: 8px;
}

/* Buttons */
.btn {
  padding: 10px 20px;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-primary {
  background: #2563eb;
  color: white;
}

.btn-primary:hover {
  background: #1d4ed8;
}

.btn-outline {
  background: white;
  border: 1px solid #e5e7eb;
  color: #64748b;
}

.btn-outline:hover {
  background: #f8fafc;
}

.btn-sm {
  padding: 6px 10px;
  font-size: 13px;
}

/* Critical Alerts */
.critical-alerts {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 100;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.critical-alert {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 10px;
  padding: 12px 15px;
  box-shadow: 0 4px 15px rgba(239, 68, 68, 0.2);
  max-width: 350px;
}

.alert-icon {
  font-size: 24px;
}

.alert-content {
  flex: 1;
}

.alert-content strong {
  display: block;
  font-size: 14px;
  color: #dc2626;
}

.alert-content p {
  margin: 5px 0 0;
  font-size: 12px;
  color: #64748b;
}

.dismiss-btn {
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  font-size: 16px;
  padding: 5px;
}

/* Animations */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
}

.slide-enter-from {
  opacity: 0;
  transform: translateX(50px);
}

.slide-leave-to {
  opacity: 0;
  transform: translateX(50px);
}

/* Responsive */
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .filters-bar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .anomaly-card {
    flex-direction: column;
  }
  
  .anomaly-actions {
    align-self: flex-end;
  }
}
</style>
