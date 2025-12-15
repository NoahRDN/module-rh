<template>
  <div class="dashboard">
    <!-- Filtres de période -->
    <div class="card filters-card">
      <div class="filters">
        <div class="filter-group">
          <label>Période</label>
          <select v-model="filtre" @change="loadData">
            <option value="mois">Mois</option>
            <option value="trimestre">Trimestre</option>
            <option value="annee">Année</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Date de référence</label>
          <input type="date" v-model="dateRef" @change="loadData" />
        </div>
        <button class="btn btn-primary" @click="loadData">
          🔄 Actualiser
        </button>
      </div>
    </div>

    <!-- Cartes KPI -->
    <div class="kpi-grid">
      <div class="card kpi-card">
        <div class="kpi-icon">👥</div>
        <div class="kpi-content">
          <span class="kpi-value">{{ statsData.effectifs?.actifs || 0 }}</span>
          <span class="kpi-label">Employés actifs</span>
          <span class="kpi-meta" v-if="statsData.effectifs?.nouveaux > 0">
            +{{ statsData.effectifs.nouveaux }} cette période
          </span>
        </div>
      </div>
      
      <div class="card kpi-card">
        <div class="kpi-icon">📊</div>
        <div class="kpi-content">
          <span class="kpi-value">{{ statsData.indicateurs?.turnover || 0 }}%</span>
          <span class="kpi-label">Taux de turnover</span>
          <span class="kpi-meta" :class="{'text-success': statsData.indicateurs?.turnover < 10, 'text-warning': statsData.indicateurs?.turnover >= 10}">
            {{ statsData.indicateurs?.turnover < 10 ? 'Stable' : 'À surveiller' }}
          </span>
        </div>
      </div>
      
      <div class="card kpi-card">
        <div class="kpi-icon">📅</div>
        <div class="kpi-content">
          <span class="kpi-value">{{ statsData.indicateurs?.absenteisme || 0 }}%</span>
          <span class="kpi-label">Taux d'absentéisme</span>
          <span class="kpi-meta">Sur la période</span>
        </div>
      </div>
      
      <div class="card kpi-card">
        <div class="kpi-icon">⭐</div>
        <div class="kpi-content">
          <span class="kpi-value">{{ statsData.indicateurs?.performance_moyenne || 0 }}%</span>
          <span class="kpi-label">Performance moyenne</span>
          <span class="kpi-meta">Score d'évaluation</span>
        </div>
      </div>
      
      <div class="card kpi-card">
        <div class="kpi-icon">🎂</div>
        <div class="kpi-content">
          <span class="kpi-value">{{ statsData.indicateurs?.anciennete_moyenne || 0 }}</span>
          <span class="kpi-label">Ancienneté moyenne</span>
          <span class="kpi-meta">En années</span>
        </div>
      </div>
      
      <div class="card kpi-card alert-kpi" @click="$router.push('/alertes')">
        <div class="kpi-icon">🔔</div>
        <div class="kpi-content">
          <span class="kpi-value">{{ alertes.length }}</span>
          <span class="kpi-label">Alertes actives</span>
          <span class="kpi-meta text-danger" v-if="alertesCritiques > 0">
            {{ alertesCritiques }} critique(s)
          </span>
        </div>
      </div>
    </div>

    <!-- Graphiques -->
    <div class="charts-grid">
      <!-- Répartition par département -->
      <div class="card chart-card">
        <div class="chart-header">
          <h3>Répartition par département</h3>
        </div>
        <div class="chart-container">
          <canvas ref="chartDepartements"></canvas>
        </div>
      </div>

      <!-- Répartition par type de contrat -->
      <div class="card chart-card">
        <div class="chart-header">
          <h3>Types de contrat</h3>
        </div>
        <div class="chart-container">
          <canvas ref="chartContrats"></canvas>
        </div>
      </div>

      <!-- Évolution des effectifs -->
      <div class="card chart-card chart-wide">
        <div class="chart-header">
          <h3>Évolution des effectifs</h3>
        </div>
        <div class="chart-container">
          <canvas ref="chartTendances"></canvas>
        </div>
      </div>

      <!-- Répartition par tranche d'âge -->
      <div class="card chart-card">
        <div class="chart-header">
          <h3>Pyramide des âges</h3>
        </div>
        <div class="chart-container">
          <div v-if="!hasAges" class="chart-empty">Aucune donnée d'âge disponible</div>
          <canvas v-else ref="chartAges"></canvas>
        </div>
      </div>

      <!-- Top performers -->
      <div class="card chart-card">
        <div class="chart-header">
          <h3>Top Performers</h3>
          <RouterLink to="/performances" class="link">Voir tout →</RouterLink>
        </div>
        <div class="performers-list" v-if="topPerformers.length">
          <div class="performer-item" v-for="(perf, index) in topPerformers" :key="perf.employe?.id || index">
            <span class="rank">{{ index + 1 }}</span>
            <div class="performer-info">
              <span class="name">{{ perf.employe?.nom }} {{ perf.employe?.prenom }}</span>
              <span class="role">{{ perf.employe?.poste || 'Non défini' }}</span>
            </div>
            <div class="score-badge" :class="getScoreClass(perf.score)">
              {{ perf.score }}%
            </div>
          </div>
        </div>
        <div class="empty-state" v-else>
          <p>Aucune évaluation pour cette période</p>
        </div>
      </div>
    </div>

    <!-- Alertes récentes -->
    <div class="card" v-if="alertes.length > 0">
      <div class="page-header">
        <div class="page-title">
          <h1>Alertes récentes</h1>
          <span>{{ alertes.length }} alerte(s) active(s)</span>
        </div>
        <RouterLink to="/alertes" class="btn btn-secondary">Voir toutes</RouterLink>
      </div>
      <div class="alerts-preview">
        <div 
          v-for="(alerte, index) in alertes.slice(0, 5)" 
          :key="index"
          class="alert-item"
          :class="'alert-' + alerte.level"
        >
          <span class="alert-icon">
            {{ alerte.level === 'danger' ? '🚨' : alerte.level === 'warning' ? '⚠️' : 'ℹ️' }}
          </span>
          <span class="alert-message">{{ alerte.message }}</span>
          <span class="alert-type">{{ formatAlertType(alerte.type) }}</span>
        </div>
      </div>
    </div>

    <!-- Derniers employés et Actions rapides -->
    <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 18px;">
      <div class="card">
        <div class="page-header">
          <div class="page-title">
            <h1>Derniers employés</h1>
            <span>Les 5 derniers profils créés</span>
          </div>
        </div>
        <table class="table">
          <thead>
            <tr>
              <th>Matricule</th>
              <th>Nom</th>
              <th>Poste</th>
              <th>Département</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="emp in derniersEmployes" :key="emp.id">
              <td>{{ emp.matricule }}</td>
              <td>{{ emp.nom }} {{ emp.prenom }}</td>
              <td>{{ emp.poste?.nom || '—' }}</td>
              <td>{{ emp.departement?.nom || '—' }}</td>
            </tr>
            <tr v-if="!derniersEmployes.length">
              <td colspan="4" class="muted">Aucun employé encore.</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Chart, registerables } from 'chart.js'
import api from '../services/api'

Chart.register(...registerables)

// Refs pour les données
const filtre = ref('annee')
const dateRef = ref(new Date().toISOString().split('T')[0])
const statsData = ref({})
const alertes = ref([])
const topPerformers = ref([])
const derniersEmployes = ref([])
const loading = ref(false)

// Refs pour les graphiques
const chartDepartements = ref(null)
const chartContrats = ref(null)
const chartTendances = ref(null)
const chartAges = ref(null)
const hasAges = computed(() => {
  const arr = statsData.value.repartitions?.tranches_age || []
  return arr.some((a) => Number(a.value) > 0)
})

// Instances de graphiques
let charts = {}

const alertesCritiques = computed(() => 
  alertes.value.filter(a => a.level === 'danger').length
)

// Couleurs pour les graphiques
const colors = {
  primary: ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'],
  success: '#22c55e',
  warning: '#f59e0b',
  danger: '#ef4444',
  info: '#3b82f6'
}

const loadData = async () => {
  loading.value = true
  try {
    const [statsRes, alertesRes, perfRes, empRes] = await Promise.all([
      api.get('/v1/dashboard/statistiques', {
        params: { filtre: filtre.value, date: dateRef.value }
      }),
      api.get('/v1/alertes'),
      api.get('/v1/dashboard/top-performers', { params: { limite: 5 } }),
      api.get('/v1/employes')
    ])

    statsData.value = statsRes.data
    alertes.value = alertesRes.data.data || []
    topPerformers.value = perfRes.data.data || []
    derniersEmployes.value = (empRes.data.data || []).slice(0, 5)

    updateCharts()
  } catch (e) {
    console.error('Erreur chargement dashboard:', e)
    // Fallback: charger les données de base
    try {
      const [emp, dep, postes] = await Promise.all([
        api.get('/v1/employes'),
        api.get('/v1/departements'),
        api.get('/v1/postes')
      ])
      derniersEmployes.value = (emp.data.data || []).slice(0, 5)
      statsData.value = {
        effectifs: { actifs: emp.data.total ?? emp.data.data?.length ?? 0 },
        indicateurs: {}
      }
    } catch (e2) {
      console.error('Erreur fallback:', e2)
    }
  } finally {
    loading.value = false
  }
}

const updateCharts = () => {
  Object.values(charts).forEach(chart => chart?.destroy())
  charts = {}

  if (!statsData.value.repartitions) return

  // Graphique Départements (Doughnut)
  if (chartDepartements.value && statsData.value.repartitions.departements?.length) {
    const data = statsData.value.repartitions.departements
    charts.departements = new Chart(chartDepartements.value, {
      type: 'doughnut',
      data: {
        labels: data.map(d => d.label),
        datasets: [{
          data: data.map(d => d.value),
          backgroundColor: colors.primary.slice(0, data.length),
          borderWidth: 0,
          hoverOffset: 10
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 11 } } }
        },
        cutout: '65%'
      }
    })
  }

  // Graphique Contrats (Pie)
  if (chartContrats.value && statsData.value.repartitions.types_contrat?.length) {
    const data = statsData.value.repartitions.types_contrat
    charts.contrats = new Chart(chartContrats.value, {
      type: 'pie',
      data: {
        labels: data.map(d => d.label),
        datasets: [{
          data: data.map(d => d.value),
          backgroundColor: [colors.success, colors.info, colors.warning, colors.danger],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 11 } } }
        }
      }
    })
  }

  // Graphique Tendances (Line)
  if (chartTendances.value && statsData.value.tendances?.length) {
    const data = statsData.value.tendances
    charts.tendances = new Chart(chartTendances.value, {
      type: 'line',
      data: {
        labels: data.map(d => d.label),
        datasets: [
          {
            label: 'Effectif',
            data: data.map(d => d.effectif),
            borderColor: colors.success,
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            fill: true,
            tension: 0.4
          },
          {
            label: 'Entrées',
            data: data.map(d => d.entrees),
            borderColor: colors.info,
            backgroundColor: 'transparent',
            borderDash: [5, 5],
            tension: 0.4
          },
          {
            label: 'Sorties',
            data: data.map(d => d.sorties),
            borderColor: colors.danger,
            backgroundColor: 'transparent',
            borderDash: [5, 5],
            tension: 0.4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top', labels: { color: '#94a3b8' } } },
        scales: {
          x: { grid: { color: 'rgba(148, 163, 184, 0.1)' }, ticks: { color: '#94a3b8' } },
          y: { grid: { color: 'rgba(148, 163, 184, 0.1)' }, ticks: { color: '#94a3b8' }, beginAtZero: true }
        }
      }
    })
  }

  // Graphique Âges (Bar horizontal)
  if (chartAges.value && statsData.value.repartitions.tranches_age?.length && hasAges.value) {
    const data = statsData.value.repartitions.tranches_age
    charts.ages = new Chart(chartAges.value, {
      type: 'bar',
      data: {
        labels: data.map(d => d.label),
        datasets: [{
          label: 'Employés',
          data: data.map(d => d.value),
          backgroundColor: colors.primary.slice(0, data.length),
          borderRadius: 6
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: 'rgba(148, 163, 184, 0.1)' }, ticks: { color: '#94a3b8' }, beginAtZero: true },
          y: { grid: { display: false }, ticks: { color: '#94a3b8' } }
        }
      }
    })
  }
}

const getScoreClass = (score) => {
  if (score >= 90) return 'score-excellent'
  if (score >= 75) return 'score-good'
  if (score >= 60) return 'score-average'
  return 'score-low'
}

const formatAlertType = (type) => {
  const types = {
    'fin_contrat': 'Contrat',
    'conges_non_pris': 'Congés',
    'absences_maladie': 'Maladie',
    'absences_exceptionnelles': 'Absences',
    'conge_en_attente': 'Demande',
    'conge_proche': 'Congé urgent'
  }
  return types[type] || type
}

onMounted(() => {
  loadData()
})

onUnmounted(() => {
  Object.values(charts).forEach(chart => chart?.destroy())
})
</script>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.filters-card { padding: 16px 20px; }

.filters {
  display: flex;
  align-items: flex-end;
  gap: 16px;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.filter-group label {
  font-size: 12px;
  color: var(--muted);
  font-weight: 600;
}

.filter-group select,
.filter-group input {
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--text);
  min-width: 150px;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
}

.kpi-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.alert-kpi {
  cursor: pointer;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.kpi-icon { font-size: 32px; }

.kpi-content {
  display: flex;
  flex-direction: column;
}

.kpi-value {
  font-size: 28px;
  font-weight: 800;
  color: var(--text);
}

.kpi-label {
  font-size: 13px;
  color: var(--muted);
  font-weight: 500;
}

.kpi-meta {
  font-size: 11px;
  color: var(--accent);
  margin-top: 4px;
}

.text-success { color: #22c55e !important; }
.text-warning { color: #f59e0b !important; }
.text-danger { color: #ef4444 !important; }

.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.chart-card { padding: 20px; }
.chart-wide { grid-column: span 2; }

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.chart-header h3 {
  font-size: 16px;
  font-weight: 700;
  margin: 0;
}

.chart-header .link {
  font-size: 12px;
  color: var(--accent);
}

.chart-container {
  height: 250px;
  position: relative;
}
.chart-empty {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--muted);
  border: 1px dashed var(--border);
  border-radius: 12px;
}

.performers-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.performer-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 8px;
}

.rank {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--accent);
  color: white;
  border-radius: 50%;
  font-weight: 700;
  font-size: 12px;
}

.performer-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.performer-info .name { font-weight: 600; font-size: 14px; }
.performer-info .role { font-size: 12px; color: var(--muted); }

.score-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 12px;
}

.score-excellent { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.score-good { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.score-average { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
.score-low { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.alerts-preview {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 16px;
}

.alert-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.02);
}

.alert-danger { border-left: 3px solid #ef4444; }
.alert-warning { border-left: 3px solid #f59e0b; }
.alert-info { border-left: 3px solid #3b82f6; }

.alert-icon { font-size: 18px; }
.alert-message { flex: 1; font-size: 13px; }
.alert-type {
  font-size: 11px;
  padding: 3px 8px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 4px;
  color: var(--muted);
}

.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 12px;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: var(--muted);
}

@media (max-width: 1024px) {
  .charts-grid { grid-template-columns: 1fr; }
  .chart-wide { grid-column: span 1; }
}

@media (max-width: 768px) {
  .kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
