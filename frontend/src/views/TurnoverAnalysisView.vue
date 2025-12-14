<template>
  <div class="turnover-view">
    <div class="page-header">
      <h1>📊 Analyse Prédictive du Turnover</h1>
      <p class="subtitle">Identifiez les risques de départ et prenez des mesures proactives</p>
    </div>

    <!-- Statistiques globales -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon critical">🚨</div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.critique || 0 }}</span>
          <span class="stat-label">Risque Critique</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon high">⚠️</div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.eleve || 0 }}</span>
          <span class="stat-label">Risque Élevé</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon medium">⚡</div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.modere || 0 }}</span>
          <span class="stat-label">Risque Modéré</span>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon low">✅</div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.faible || 0 }}</span>
          <span class="stat-label">Risque Faible</span>
        </div>
      </div>
    </div>

    <!-- Filtres -->
    <div class="card filters-card">
      <div class="filters">
        <div class="filter-group">
          <label>Niveau de risque</label>
          <select v-model="filters.niveau" @change="applyFilters">
            <option value="">Tous</option>
            <option value="critique">Critique</option>
            <option value="eleve">Élevé</option>
            <option value="modere">Modéré</option>
            <option value="faible">Faible</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Département</label>
          <select v-model="filters.departement" @change="applyFilters">
            <option value="">Tous</option>
            <option v-for="dept in departements" :key="dept.id" :value="dept.id">
              {{ dept.nom }}
            </option>
          </select>
        </div>
        <div class="filter-group">
          <label>Afficher</label>
          <select v-model="filters.limit" @change="applyFilters">
            <option value="10">Top 10</option>
            <option value="25">Top 25</option>
            <option value="50">Top 50</option>
            <option value="0">Tous</option>
          </select>
        </div>
        <button class="btn btn-primary" @click="loadData">
          🔄 Actualiser
        </button>
      </div>
    </div>

    <!-- Graphique par département -->
    <div class="card chart-card">
      <div class="card-header">
        <h3>📈 Risque par Département</h3>
      </div>
      <div class="chart-container">
        <canvas ref="chartDepartements"></canvas>
      </div>
    </div>

    <!-- Liste des employés à risque -->
    <div class="card">
      <div class="card-header">
        <h3>👥 Employés à Surveiller</h3>
        <span class="badge">{{ filteredEmployees.length }} employé(s)</span>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Analyse en cours...</p>
      </div>

      <div v-else-if="filteredEmployees.length === 0" class="empty-state">
        <span class="empty-icon">✨</span>
        <p>Aucun employé à risque selon les critères sélectionnés</p>
      </div>

      <div v-else class="employees-list">
        <div 
          v-for="emp in filteredEmployees" 
          :key="emp.employe?.id || emp.id"
          class="employee-card"
          :class="'risk-' + (emp.niveau_risque?.niveau || emp.niveau_risque)"
          @click="showDetails(emp)"
        >
          <div class="employee-info">
            <div class="employee-avatar">
              {{ getInitials(getEmployeNom(emp)) }}
            </div>
            <div class="employee-details">
              <h4>{{ getEmployeNom(emp) }}</h4>
              <p>{{ emp.employe?.poste || emp.poste || 'N/A' }} - {{ emp.employe?.departement || emp.departement || 'N/A' }}</p>
            </div>
          </div>

          <div class="risk-score">
            <div class="score-circle" :style="getScoreStyle(emp.score_global)">
              {{ Math.round(emp.score_global) }}%
            </div>
            <span class="risk-label">{{ getRiskLabel(emp.niveau_risque?.niveau || emp.niveau_risque) }}</span>
          </div>

          <div class="factors-preview">
            <div 
              v-for="(value, key) in getTopFactors(emp.scores_facteurs || emp.facteurs)" 
              :key="key"
              class="factor-pill"
              :class="getFactorClass(value)"
            >
              {{ getFactorLabel(key) }}: {{ Math.round(value) }}
            </div>
          </div>

          <div class="actions">
            <button class="btn btn-sm btn-outline" @click.stop="showDetails(emp)">
              Détails
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de détails -->
    <div v-if="selectedEmployee" class="modal-overlay" @click.self="closeDetails">
      <div class="modal-content">
        <div class="modal-header" :class="'risk-' + selectedEmployee.niveau_risque">
          <h3>Analyse Détaillée</h3>
          <button class="close-btn" @click="closeDetails">✕</button>
        </div>

        <div class="modal-body">
          <div class="employee-header">
            <div class="avatar-large">{{ getInitials(selectedEmployee.employe_nom) }}</div>
            <div>
              <h2>{{ selectedEmployee.employe_nom }}</h2>
              <p>{{ selectedEmployee.poste }} - {{ selectedEmployee.departement }}</p>
            </div>
            <div class="score-large" :style="getScoreStyle(selectedEmployee.score_global)">
              {{ Math.round(selectedEmployee.score_global) }}%
            </div>
          </div>

          <div class="factors-grid">
            <div 
              v-for="(value, key) in selectedEmployee.facteurs" 
              :key="key"
              class="factor-card"
              :class="getFactorClass(value)"
            >
              <div class="factor-icon">{{ getFactorIcon(key) }}</div>
              <div class="factor-info">
                <span class="factor-name">{{ getFactorLabel(key) }}</span>
                <div class="factor-bar">
                  <div class="factor-fill" :style="{ width: value + '%' }"></div>
                </div>
                <span class="factor-value">{{ Math.round(value) }}/100</span>
              </div>
            </div>
          </div>

          <div class="recommendations">
            <h4>💡 Recommandations</h4>
            <ul>
              <li v-for="(rec, index) in selectedEmployee.recommandations" :key="index">
                {{ rec }}
              </li>
            </ul>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline" @click="closeDetails">Fermer</button>
          <button class="btn btn-primary" @click="scheduleInterview(selectedEmployee)">
            📅 Planifier un entretien
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import turnoverService from '@/services/turnoverService'
import api from '@/services/api'
import Chart from 'chart.js/auto'

export default {
  name: 'TurnoverAnalysisView',
  data() {
    return {
      loading: false,
      employees: [],
      stats: {},
      departements: [],
      filters: {
        niveau: '',
        departement: '',
        limit: 25
      },
      selectedEmployee: null,
      chart: null
    }
  },
  computed: {
    filteredEmployees() {
      let result = [...this.employees]
      
      if (this.filters.niveau) {
        result = result.filter(e => e.niveau_risque?.niveau === this.filters.niveau)
      }
      
      if (this.filters.departement) {
        result = result.filter(e => e.employe?.departement_id == this.filters.departement || e.departement_id == this.filters.departement)
      }
      
      result.sort((a, b) => b.score_global - a.score_global)
      
      if (this.filters.limit > 0) {
        result = result.slice(0, this.filters.limit)
      }
      
      return result
    }
  },
  mounted() {
    this.loadData()
    this.loadDepartements()
  },
  methods: {
    async loadData() {
      this.loading = true
      try {
        const [employeesRes, statsRes, deptRes] = await Promise.all([
          turnoverService.analyserTous(),
          turnoverService.getStatistiques(),
          turnoverService.getParDepartement()
        ])
        
        this.employees = employeesRes.data.employes || []
        this.stats = statsRes.data.statistiques?.par_niveau || {}
        
        this.updateChart(deptRes.data.departements || [])
      } catch (error) {
        console.error('Erreur chargement données:', error)
      } finally {
        this.loading = false
      }
    },
    async loadDepartements() {
      try {
        const response = await api.get('/departements')
        this.departements = response.data.data || response.data || []
      } catch (error) {
        console.error('Erreur chargement départements:', error)
      }
    },
    applyFilters() {
      // Les filtres sont appliqués via computed
    },
    updateChart(deptData) {
      if (this.chart) {
        this.chart.destroy()
      }
      
      const ctx = this.$refs.chartDepartements
      if (!ctx) return
      
      const labels = deptData.map(d => d.nom)
      const scores = deptData.map(d => d.risque_moyen || 0)
      const colors = scores.map(s => {
        if (s >= 70) return '#ef4444'
        if (s >= 50) return '#f97316'
        if (s >= 30) return '#eab308'
        return '#22c55e'
      })
      
      this.chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Score de risque moyen',
            data: scores,
            backgroundColor: colors,
            borderRadius: 8
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              max: 100
            }
          },
          plugins: {
            legend: { display: false }
          }
        }
      })
    },
    showDetails(employee) {
      this.selectedEmployee = employee
    },
    closeDetails() {
      this.selectedEmployee = null
    },
    scheduleInterview(employee) {
      // Rediriger vers le calendrier ou créer un événement
      this.$router.push({
        path: '/calendrier-evenements',
        query: { 
          action: 'create',
          type: 'entretien',
          employe_id: employee.employe?.id || employee.employe_id
        }
      })
    },
    getEmployeNom(emp) {
      if (emp.employe_nom) return emp.employe_nom
      if (emp.employe) {
        return `${emp.employe.nom || ''} ${emp.employe.prenom || ''}`.trim()
      }
      return emp.nom || 'Inconnu'
    },
    getInitials(name) {
      if (!name) return '?'
      return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
    },
    getScoreStyle(score) {
      let color = '#22c55e'
      if (score >= 70) color = '#ef4444'
      else if (score >= 50) color = '#f97316'
      else if (score >= 30) color = '#eab308'
      
      return {
        background: `conic-gradient(${color} ${score * 3.6}deg, #e5e7eb 0deg)`
      }
    },
    getRiskLabel(niveau) {
      const labels = {
        critique: 'Critique',
        eleve: 'Élevé',
        modere: 'Modéré',
        faible: 'Faible'
      }
      return labels[niveau] || niveau
    },
    getTopFactors(facteurs) {
      if (!facteurs) return {}
      const sorted = Object.entries(facteurs)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 3)
      return Object.fromEntries(sorted)
    },
    getFactorLabel(key) {
      const labels = {
        anciennete: 'Ancienneté',
        satisfaction: 'Satisfaction',
        absences: 'Absences',
        retards: 'Retards',
        formations: 'Formations',
        promotion: 'Évolution',
        salaire: 'Salaire'
      }
      return labels[key] || key
    },
    getFactorIcon(key) {
      const icons = {
        anciennete: '📅',
        satisfaction: '😊',
        absences: '🏠',
        retards: '⏰',
        formations: '📚',
        promotion: '📈',
        salaire: '💰'
      }
      return icons[key] || '📊'
    },
    getFactorClass(value) {
      if (value >= 70) return 'factor-high'
      if (value >= 40) return 'factor-medium'
      return 'factor-low'
    }
  },
  beforeUnmount() {
    if (this.chart) {
      this.chart.destroy()
    }
  }
}
</script>

<style scoped>
.turnover-view {
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
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.stat-icon.critical { background: #fef2f2; }
.stat-icon.high { background: #fff7ed; }
.stat-icon.medium { background: #fefce8; }
.stat-icon.low { background: #f0fdf4; }

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

/* Cards */
.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  margin-bottom: 20px;
}

.card-header {
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  font-size: 18px;
  color: #1e293b;
}

.badge {
  background: #e0e7ff;
  color: #4f46e5;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 13px;
}

/* Filters */
.filters-card {
  padding: 20px;
}

.filters {
  display: flex;
  gap: 20px;
  align-items: flex-end;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.filter-group label {
  font-size: 13px;
  color: #64748b;
}

.filter-group select {
  padding: 8px 12px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  min-width: 150px;
}

/* Chart */
.chart-card .card-header {
  border: none;
}

.chart-container {
  height: 300px;
  padding: 20px;
}

/* Employee List */
.employees-list {
  padding: 20px;
}

.employee-card {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 15px;
  border-radius: 12px;
  margin-bottom: 12px;
  cursor: pointer;
  transition: all 0.2s;
  border-left: 4px solid transparent;
}

.employee-card:hover {
  background: #f8fafc;
}

.employee-card.risk-critique {
  border-left-color: #ef4444;
  background: #fef2f2;
}

.employee-card.risk-eleve {
  border-left-color: #f97316;
  background: #fff7ed;
}

.employee-card.risk-modere {
  border-left-color: #eab308;
  background: #fefce8;
}

.employee-card.risk-faible {
  border-left-color: #22c55e;
  background: #f0fdf4;
}

.employee-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.employee-avatar {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  background: #e0e7ff;
  color: #4f46e5;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.employee-details h4 {
  margin: 0;
  font-size: 15px;
  color: #1e293b;
}

.employee-details p {
  margin: 0;
  font-size: 13px;
  color: #64748b;
}

/* Score Circle */
.risk-score {
  text-align: center;
}

.score-circle {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 14px;
  color: #1e293b;
  position: relative;
}

.score-circle::before {
  content: '';
  position: absolute;
  inset: 4px;
  background: white;
  border-radius: 50%;
  z-index: 0;
}

.score-circle::after {
  position: relative;
  z-index: 1;
}

.risk-label {
  font-size: 12px;
  color: #64748b;
  display: block;
  margin-top: 4px;
}

/* Factors */
.factors-preview {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  flex: 1;
}

.factor-pill {
  padding: 4px 10px;
  border-radius: 15px;
  font-size: 12px;
}

.factor-pill.factor-high {
  background: #fef2f2;
  color: #dc2626;
}

.factor-pill.factor-medium {
  background: #fefce8;
  color: #ca8a04;
}

.factor-pill.factor-low {
  background: #f0fdf4;
  color: #16a34a;
}

/* Buttons */
.btn {
  padding: 10px 20px;
  border-radius: 8px;
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
  background: transparent;
  border: 1px solid #e5e7eb;
  color: #64748b;
}

.btn-outline:hover {
  background: #f8fafc;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
}

/* Loading & Empty States */
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

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
  border-radius: 16px 16px 0 0;
}

.modal-header.risk-critique { background: #ef4444; }
.modal-header.risk-eleve { background: #f97316; }
.modal-header.risk-modere { background: #eab308; }
.modal-header.risk-faible { background: #22c55e; }

.close-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 16px;
  color: white;
}

.modal-body {
  padding: 25px;
}

.employee-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 30px;
}

.avatar-large {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  background: #e0e7ff;
  color: #4f46e5;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: bold;
}

.score-large {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 18px;
  margin-left: auto;
}

/* Factors Grid */
.factors-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
  margin-bottom: 25px;
}

.factor-card {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  border-radius: 10px;
  background: #f8fafc;
}

.factor-card.factor-high { background: #fef2f2; }
.factor-card.factor-medium { background: #fefce8; }
.factor-card.factor-low { background: #f0fdf4; }

.factor-icon {
  font-size: 24px;
}

.factor-info {
  flex: 1;
}

.factor-name {
  display: block;
  font-size: 13px;
  color: #64748b;
  margin-bottom: 5px;
}

.factor-bar {
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 3px;
}

.factor-fill {
  height: 100%;
  background: #2563eb;
  border-radius: 3px;
}

.factor-card.factor-high .factor-fill { background: #ef4444; }
.factor-card.factor-medium .factor-fill { background: #eab308; }
.factor-card.factor-low .factor-fill { background: #22c55e; }

.factor-value {
  font-size: 12px;
  color: #94a3b8;
}

/* Recommendations */
.recommendations {
  background: #f0f9ff;
  border-radius: 10px;
  padding: 20px;
}

.recommendations h4 {
  margin: 0 0 15px 0;
  color: #0369a1;
}

.recommendations ul {
  margin: 0;
  padding-left: 20px;
}

.recommendations li {
  margin-bottom: 8px;
  color: #334155;
}

.modal-footer {
  padding: 20px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* Responsive */
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .filters {
    flex-direction: column;
    align-items: stretch;
  }
  
  .employee-card {
    flex-direction: column;
    text-align: center;
  }
  
  .factors-grid {
    grid-template-columns: 1fr;
  }
}
</style>
