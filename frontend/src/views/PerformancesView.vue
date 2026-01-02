<template>
  <div class="performances">
    <!-- En-tête avec filtres -->
    <div class="card">
      <div class="page-header">
        <div class="page-title">
          <h1>Gestion des Performances</h1>
          <span>Évaluations mensuelles des employés</span>
        </div>
        <div class="header-actions">
          <RouterLink to="/performances/nouvelle" class="btn btn-primary">
            ➕ Nouvelle évaluation
          </RouterLink>
        </div>
      </div>

      <div class="filters">
        <div class="filter-group">
          <label>Période</label>
          <input type="month" v-model="periode" @change="loadData" />
        </div>
        <div class="filter-group">
          <label>Département</label>
          <select v-model="departementId" @change="loadData">
            <option value="">Tous</option>
            <option v-for="dep in departements" :key="dep.id" :value="dep.id">
              {{ dep.nom }}
            </option>
          </select>
        </div>
        <div class="filter-group">
          <label>Statut</label>
          <select v-model="statut" @change="loadData">
            <option value="">Tous</option>
            <option value="brouillon">Brouillon</option>
            <option value="valide">Validé</option>
            <option value="archive">Archivé</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Statistiques de la période -->
    <div class="stats-grid" v-if="stats.stats">
      <div class="card stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.stats.total }}</span>
          <span class="stat-label">Évaluations</span>
        </div>
      </div>
      <div class="card stat-card">
        <div class="stat-icon">⭐</div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.stats.moyenne }}%</span>
          <span class="stat-label">Moyenne</span>
        </div>
      </div>
      <div class="card stat-card">
        <div class="stat-icon">📈</div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.stats.maximum }}%</span>
          <span class="stat-label">Maximum</span>
        </div>
      </div>
      <div class="card stat-card">
        <div class="stat-icon">📉</div>
        <div class="stat-content">
          <span class="stat-value">{{ stats.stats.minimum }}%</span>
          <span class="stat-label">Minimum</span>
        </div>
      </div>
    </div>

    <!-- Graphiques -->
    <div class="charts-row">
      <div class="card chart-card">
        <h3>Distribution des scores</h3>
        <div class="chart-container">
          <canvas ref="chartDistribution"></canvas>
        </div>
      </div>
      <div class="card chart-card">
        <h3>Évolution mensuelle</h3>
        <div class="chart-container">
          <canvas ref="chartEvolution"></canvas>
        </div>
      </div>
    </div>

    <!-- Liste des évaluations -->
    <div class="card">
      <div class="page-header">
        <div class="page-title">
          <h1>Liste des évaluations</h1>
          <span>{{ evaluations.length }} résultat(s)</span>
        </div>
      </div>

      <table class="table" v-if="evaluations.length">
        <thead>
          <tr>
            <th>Employé</th>
            <th>Département</th>
            <th>Période</th>
            <th>Score</th>
            <th>Niveau</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in evaluations" :key="item.id">
            <td>
              <div class="employe-cell">
                <span class="name">{{ item.employe?.nom }} {{ item.employe?.prenom }}</span>
                <span class="matricule">{{ item.employe?.matricule }}</span>
              </div>
            </td>
            <td>{{ item.employe?.departement?.nom || '—' }}</td>
            <td>{{ formatPeriode(item.periode) }}</td>
            <td>
              <div class="score-cell">
                <div class="score-bar">
                  <div class="score-fill" :style="{ width: item.score_global + '%' }" :class="getScoreClass(item.score_global)"></div>
                </div>
                <span class="score-value">{{ item.score_global }}%</span>
              </div>
            </td>
            <td>
              <span class="niveau-badge" :class="getScoreClass(item.score_global)">
                {{ getNiveauLabel(item.score_global) }}
              </span>
            </td>
            <td>
              <span class="statut-badge" :class="'statut-' + item.statut">
                {{ item.statut }}
              </span>
            </td>
            <td>
              <div class="actions">
                <button class="btn btn-small btn-secondary" @click="voirDetail(item.id)" title="Voir">
                  👁️
                </button>
                <button class="btn btn-small btn-secondary" @click="telechargerPdf(item.id)" title="PDF">
                  📄
                </button>
                <button 
                  v-if="item.statut === 'brouillon'"
                  class="btn btn-small btn-primary" 
                  @click="validerEvaluation(item.id)"
                  title="Valider"
                >
                  ✓
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="empty-state" v-else>
        <p>Aucune évaluation pour cette période</p>
        <RouterLink to="/performances/nouvelle" class="btn btn-primary">
          Créer une évaluation
        </RouterLink>
      </div>
    </div>

    <!-- Modal détail -->
    <div class="modal-overlay" v-if="showModal" @click.self="showModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>Détail de l'évaluation</h2>
          <button class="btn-close" @click="showModal = false">✕</button>
        </div>
        <div class="modal-body" v-if="selectedEval">
          <div class="detail-header">
            <div>
              <h3>{{ selectedEval.employe?.nom }} {{ selectedEval.employe?.prenom }}</h3>
              <p>{{ selectedEval.employe?.poste?.nom }} - {{ selectedEval.employe?.departement?.nom }}</p>
            </div>
            <div class="score-big" :class="getScoreClass(selectedEval.score_global)">
              {{ selectedEval.score_global }}%
            </div>
          </div>

          <h4>Détail par critère</h4>
          <div class="criteres-list">
            <div v-for="detail in selectedEval.details" :key="detail.id" class="critere-item">
              <div class="critere-info">
                <span class="critere-libelle">{{ detail.critere?.libelle }}</span>
                <span class="critere-poids">Poids: {{ detail.critere?.poids }}%</span>
              </div>
              <div class="critere-score">
                <div class="score-bar large">
                  <div class="score-fill" :style="{ width: detail.note + '%' }" :class="getScoreClass(detail.note)"></div>
                </div>
                <span>{{ detail.note }}%</span>
              </div>
              <p class="critere-comment" v-if="detail.commentaire">{{ detail.commentaire }}</p>
            </div>
          </div>

          <div class="detail-sections">
            <div v-if="selectedEval.points_forts">
              <h4>Points forts</h4>
              <p>{{ selectedEval.points_forts }}</p>
            </div>
            <div v-if="selectedEval.axes_amelioration">
              <h4>Axes d'amélioration</h4>
              <p>{{ selectedEval.axes_amelioration }}</p>
            </div>
            <div v-if="selectedEval.objectifs">
              <h4>Objectifs</h4>
              <p>{{ selectedEval.objectifs }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * @remarks Feature prête mais désactivée temporairement.
 * @deprecated Activation ultérieure (phase 2).
 * 
 * Vue pour la gestion des performances
 * Features: Évaluations périodiques automatisées (scoring), Génération de rapports de performance
 */
import { ref, onMounted, onUnmounted } from 'vue'
import { Chart, registerables } from 'chart.js'
import api from '../services/api'

Chart.register(...registerables)

const periode = ref(new Date().toISOString().slice(0, 7))
const departementId = ref('')
const statut = ref('')
const evaluations = ref([])
const departements = ref([])
const stats = ref({})
const showModal = ref(false)
const selectedEval = ref(null)

const chartDistribution = ref(null)
const chartEvolution = ref(null)
let charts = {}

const loadData = async () => {
  try {
    const params = { periode: periode.value }
    if (departementId.value) params.departement_id = departementId.value
    if (statut.value) params.statut = statut.value

    const [evalsRes, statsRes, depsRes] = await Promise.all([
      api.get('/v1/evaluations', { params }),
      api.get('/v1/evaluations-statistiques', { params: { periode: periode.value } }),
      api.get('/v1/departements')
    ])

    evaluations.value = evalsRes.data.data || []
    stats.value = statsRes.data || {}
    departements.value = depsRes.data.data || []

    updateCharts()
  } catch (e) {
    console.error('Erreur chargement:', e)
  }
}

const updateCharts = () => {
  Object.values(charts).forEach(c => c?.destroy())
  charts = {}

  // Distribution
  if (chartDistribution.value && stats.value.distribution?.length) {
    const data = stats.value.distribution
    charts.distribution = new Chart(chartDistribution.value, {
      type: 'bar',
      data: {
        labels: data.map(d => d.label),
        datasets: [{
          label: 'Employés',
          data: data.map(d => d.value),
          backgroundColor: ['#22c55e', '#3b82f6', '#06b6d4', '#f59e0b', '#f97316', '#ef4444'],
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { color: '#94a3b8' } },
          x: { ticks: { color: '#94a3b8', font: { size: 10 } } }
        }
      }
    })
  }

  // Évolution
  if (chartEvolution.value && stats.value.evolution_mensuelle?.length) {
    const data = stats.value.evolution_mensuelle
    charts.evolution = new Chart(chartEvolution.value, {
      type: 'line',
      data: {
        labels: data.map(d => d.periode),
        datasets: [{
          label: 'Moyenne',
          data: data.map(d => d.moyenne),
          borderColor: '#22c55e',
          backgroundColor: 'rgba(34, 197, 94, 0.1)',
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { min: 0, max: 100, ticks: { color: '#94a3b8' } },
          x: { ticks: { color: '#94a3b8' } }
        }
      }
    })
  }
}

const voirDetail = async (id) => {
  try {
    const res = await api.get(`/v1/evaluations/${id}`)
    selectedEval.value = res.data.data
    showModal.value = true
  } catch (e) {
    console.error('Erreur:', e)
  }
}

const validerEvaluation = async (id) => {
  if (!confirm('Valider cette évaluation ?')) return
  try {
    await api.put(`/v1/evaluations/${id}`, { statut: 'valide' })
    loadData()
  } catch (e) {
    console.error('Erreur:', e)
  }
}

const telechargerPdf = async (id) => {
  try {
    const res = await api.get(`/v1/evaluations/${id}/pdf`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `evaluation_${id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (e) {
    console.error('Erreur téléchargement:', e)
  }
}

const formatPeriode = (p) => {
  const [year, month] = p.split('-')
  const months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc']
  return `${months[parseInt(month) - 1]} ${year}`
}

const getScoreClass = (score) => {
  if (score >= 90) return 'excellent'
  if (score >= 75) return 'good'
  if (score >= 60) return 'average'
  if (score >= 50) return 'satisfactory'
  return 'low'
}

const getNiveauLabel = (score) => {
  if (score >= 90) return 'Excellent'
  if (score >= 75) return 'Très bien'
  if (score >= 60) return 'Bien'
  if (score >= 50) return 'Satisfaisant'
  if (score >= 40) return 'À améliorer'
  return 'Insuffisant'
}

onMounted(loadData)
onUnmounted(() => Object.values(charts).forEach(c => c?.destroy()))
</script>

<style scoped>
.performances {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.filters {
  display: flex;
  gap: 16px;
  margin-top: 16px;
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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
}

.stat-icon { font-size: 28px; }
.stat-value { font-size: 24px; font-weight: 800; }
.stat-label { font-size: 12px; color: var(--muted); }

.charts-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.chart-card {
  padding: 20px;
}

.chart-card h3 {
  margin: 0 0 16px;
  font-size: 16px;
}

.chart-container {
  height: 200px;
}

.employe-cell {
  display: flex;
  flex-direction: column;
}

.employe-cell .name { font-weight: 600; }
.employe-cell .matricule { font-size: 12px; color: var(--muted); }

.score-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.score-bar {
  width: 80px;
  height: 8px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  overflow: hidden;
}

.score-bar.large {
  width: 120px;
  height: 10px;
}

.score-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.3s;
}

.score-fill.excellent { background: #22c55e; }
.score-fill.good { background: #3b82f6; }
.score-fill.average { background: #06b6d4; }
.score-fill.satisfactory { background: #f59e0b; }
.score-fill.low { background: #ef4444; }

.score-value { font-weight: 600; font-size: 13px; }

.niveau-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
}

.niveau-badge.excellent { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.niveau-badge.good { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.niveau-badge.average { background: rgba(6, 182, 212, 0.2); color: #06b6d4; }
.niveau-badge.satisfactory { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
.niveau-badge.low { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.statut-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: capitalize;
}

.statut-brouillon { background: rgba(148, 163, 184, 0.2); color: #94a3b8; }
.statut-valide { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.statut-archive { background: rgba(107, 114, 128, 0.2); color: #6b7280; }

.actions {
  display: flex;
  gap: 6px;
}

.btn-small {
  padding: 6px 10px;
  font-size: 12px;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: var(--muted);
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: var(--card);
  border-radius: 16px;
  width: 90%;
  max-width: 700px;
  max-height: 85vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid var(--border);
}

.modal-header h2 { margin: 0; }

.btn-close {
  background: none;
  border: none;
  color: var(--muted);
  font-size: 20px;
  cursor: pointer;
}

.modal-body {
  padding: 20px;
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.detail-header h3 { margin: 0; }
.detail-header p { margin: 4px 0 0; color: var(--muted); }

.score-big {
  font-size: 36px;
  font-weight: 800;
  padding: 10px 20px;
  border-radius: 12px;
}

.score-big.excellent { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.score-big.good { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.score-big.average { background: rgba(6, 182, 212, 0.2); color: #06b6d4; }
.score-big.satisfactory { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
.score-big.low { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.criteres-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin: 16px 0;
}

.critere-item {
  padding: 16px;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 8px;
}

.critere-info {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.critere-libelle { font-weight: 600; }
.critere-poids { font-size: 12px; color: var(--muted); }

.critere-score {
  display: flex;
  align-items: center;
  gap: 12px;
}

.critere-comment {
  margin: 8px 0 0;
  font-size: 13px;
  color: var(--muted);
  font-style: italic;
}

.detail-sections {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 24px;
}

.detail-sections h4 {
  margin: 0 0 8px;
  color: var(--accent);
}

.detail-sections p {
  margin: 0;
  padding: 12px;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 8px;
}

@media (max-width: 768px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .charts-row { grid-template-columns: 1fr; }
}
</style>
