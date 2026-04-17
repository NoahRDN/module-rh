<template>
  <div class="dashboard-page">
    <section class="hero">
      <div class="hero-copy">
        <p class="hero-kicker">Executive overview</p>
        <h1>Tableau de bord RH</h1>
        <p class="hero-subtitle">
          Centralisez les effectifs, les alertes, la performance et les répartitions RH dans une vue
          plus calme, plus claire et mieux hiérarchisée.
        </p>

        <div class="hero-pills">
          <span class="pill">Effectifs en direct</span>
          <span class="pill">Alertes prioritaires</span>
          <span class="pill">Tendances RH</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="filters-grid">
            <label class="filter-field">
              <span>Période</span>
              <select v-model="filtre" class="select" @change="loadData">
                <option value="mois">Mois</option>
                <option value="trimestre">Trimestre</option>
                <option value="annee">Année</option>
              </select>
            </label>

            <label class="filter-field">
              <span>Date de référence</span>
              <input v-model="dateRef" class="input" type="date" @change="loadData" />
            </label>
          </div>

          <div class="hero-action-row">
            <button class="btn" type="button" @click="loadData" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>
            <RouterLink to="/alertes" class="btn btn-secondary">
              <AppIcon name="bell" :size="18" />
              <span>Voir alertes</span>
            </RouterLink>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Période active:
              <strong>{{ periodLabel }}</strong>
            </p>
            <p class="hero-meta">
              Dernière synchro:
              <strong>{{ lastSyncedLabel }}</strong>
            </p>
          </div>
        </div>

        <div v-if="loadStatus.text" class="status-banner" :class="loadStatus.type">
          <span class="status-dot"></span>
          <span>{{ loadStatus.text }}</span>
        </div>
      </div>
    </section>

    <section class="metric-grid">
      <article v-for="metric in metricCards" :key="metric.label" class="metric-card">
        <span class="metric-chip">{{ metric.tag }}</span>
        <p class="metric-label">{{ metric.label }}</p>
        <p class="metric-value">{{ metric.value }}</p>
        <p class="metric-caption">{{ metric.caption }}</p>
      </article>
    </section>

    <div v-if="loading" class="card loading-card">
      <p class="loading-title">Chargement du cockpit RH…</p>
      <p class="muted">Les indicateurs, alertes et graphiques sont en cours de synchronisation.</p>
    </div>

    <template v-else>
      <section class="content-grid">
        <div class="main-column">
          <section class="summary-grid">
            <article class="card section-card pulse-card">
              <div class="section-heading">
                <div>
                  <p class="section-kicker">Workforce pulse</p>
                  <h2>Pilotage opérationnel</h2>
                </div>
                <span class="section-chip">Lecture rapide</span>
              </div>

              <p class="section-copy">
                Les principaux signaux RH sont regroupés ici pour donner une lecture immédiate avant
                d’entrer dans les analyses détaillées.
              </p>

              <div class="signal-grid">
                <article
                  v-for="signal in pulseCards"
                  :key="signal.label"
                  class="signal-card"
                  :class="`signal-${signal.tone}`"
                >
                  <span class="signal-badge">{{ signal.badge }}</span>
                  <p class="signal-label">{{ signal.label }}</p>
                  <p class="signal-value">{{ signal.value }}</p>
                  <p class="signal-copy">{{ signal.copy }}</p>
                </article>
              </div>
            </article>

            <article class="card section-card priorities-card">
              <div class="section-heading">
                <div>
                  <p class="section-kicker">Signals</p>
                  <h2>Alertes prioritaires</h2>
                </div>
                <RouterLink to="/alertes" class="inline-link">Ouvrir le centre</RouterLink>
              </div>

              <p class="section-copy">
                Les signalements à traiter en premier sont visibles sans quitter la page principale.
              </p>

              <div v-if="alertes.length" class="priority-list">
                <div
                  v-for="(alerte, index) in alertes.slice(0, 4)"
                  :key="index"
                  class="priority-item"
                  :class="`priority-${alerte.level}`"
                >
                  <div class="priority-visual" :class="`priority-visual-${alerte.level}`">
                    <AppIcon name="bell" :size="18" />
                  </div>
                  <div class="priority-copy">
                    <div class="priority-topline">
                      <span class="priority-pill" :class="`priority-pill-${alerte.level}`">
                        {{ priorityLevelLabel(alerte.level) }}
                      </span>
                      <span class="priority-type">{{ formatAlertType(alerte.type) }}</span>
                    </div>
                    <p class="priority-message">{{ formatAlertMessage(alerte.message) }}</p>
                  </div>
                </div>
              </div>

              <div v-else class="empty-state compact">
                <p>Aucune alerte prioritaire</p>
                <span>Le tableau de bord ne remonte aucun signal critique sur la période.</span>
              </div>
            </article>
          </section>

          <article class="card section-card analytics-card">
            <div class="section-heading">
              <div>
                <p class="section-kicker">Analytics</p>
                <h2>Répartition et tendances</h2>
              </div>
              <span class="section-chip">Vue consolidée</span>
            </div>

            <p class="section-copy">
              Les graphiques sont regroupés dans un seul espace pour éviter la fragmentation visuelle et
              garder la même logique de lecture que sur la page paie.
            </p>

            <section class="charts-grid">
              <article class="chart-card">
                <div class="chart-head">
                  <div>
                    <p class="section-kicker">Répartition</p>
                    <h3>Effectifs par département</h3>
                  </div>
                </div>
                <div class="chart-container">
                  <canvas ref="chartDepartements" :class="{ 'chart-hidden': !hasDepartements }"></canvas>
                  <div v-if="!hasDepartements" class="chart-empty">Aucune répartition disponible</div>
                </div>
              </article>

              <article class="chart-card">
                <div class="chart-head">
                  <div>
                    <p class="section-kicker">Structure</p>
                    <h3>Types de contrat</h3>
                  </div>
                </div>
                <div class="chart-container">
                  <canvas ref="chartContrats" :class="{ 'chart-hidden': !hasContrats }"></canvas>
                  <div v-if="!hasContrats" class="chart-empty">Aucune donnée contrat disponible</div>
                </div>
              </article>

              <article class="chart-card chart-wide">
                <div class="chart-head">
                  <div>
                    <p class="section-kicker">Tendances</p>
                    <h3>Évolution des effectifs</h3>
                  </div>
                </div>
                <div class="chart-container large">
                  <canvas ref="chartTendances" :class="{ 'chart-hidden': !hasTendances }"></canvas>
                  <div v-if="!hasTendances" class="chart-empty">Aucune tendance disponible</div>
                </div>
              </article>

              <article class="chart-card">
                <div class="chart-head">
                  <div>
                    <p class="section-kicker">Démographie</p>
                    <h3>Pyramide des âges</h3>
                  </div>
                </div>
                <div class="chart-container">
                  <canvas ref="chartAges" :class="{ 'chart-hidden': !hasAges }"></canvas>
                  <div v-if="!hasAges" class="chart-empty">Aucune donnée d'âge disponible</div>
                </div>
              </article>
            </section>
          </article>

          <section class="detail-grid">
            <article class="card section-card performers-card">
              <div class="section-heading">
                <div>
                  <p class="section-kicker">Top performers</p>
                  <h2>Talents mis en avant</h2>
                </div>
                <RouterLink to="/performances" class="btn btn-secondary btn-sm">Voir tout</RouterLink>
              </div>

              <p class="section-copy">
                Les meilleures évaluations restent visibles dans un bloc séparé, sans surcharger le
                panneau latéral.
              </p>

              <div class="performers-list" v-if="topPerformers.length">
                <div v-for="(perf, index) in topPerformers" :key="perf.employe?.id || index" class="performer-item">
                  <span class="rank">{{ index + 1 }}</span>
                  <div class="performer-info">
                    <span class="name">{{ normalizeEmployeName(perf.employe) }}</span>
                    <span class="role">{{ normalizePoste(perf.employe) }}</span>
                  </div>
                  <div class="score-badge" :class="getScoreClass(perf.score)">
                    {{ perf.score }}%
                  </div>
                </div>
              </div>

              <div v-else class="empty-state compact">
                <p>Aucune évaluation disponible</p>
                <span>Les meilleurs profils apparaîtront ici dès que des scores seront calculés.</span>
              </div>
            </article>

            <article class="card section-card recent-card">
              <div class="section-heading">
                <div>
                  <p class="section-kicker">Recent hires</p>
                  <h2>Derniers employés</h2>
                </div>
                <RouterLink to="/employes" class="btn btn-secondary btn-sm">Voir l’annuaire</RouterLink>
              </div>

              <p class="section-copy">
                Les derniers mouvements restent accessibles dans un tableau simple, avec une densité
                comparable à la page paie.
              </p>

              <div class="table-shell">
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
                      <td>{{ emp.matricule || '—' }}</td>
                      <td>{{ emp.nom }} {{ emp.prenom }}</td>
                      <td>{{ emp.poste?.nom || '—' }}</td>
                      <td>{{ emp.departement?.nom || '—' }}</td>
                    </tr>
                    <tr v-if="!derniersEmployes.length">
                      <td colspan="4" class="empty-table">Aucun employé disponible pour le moment.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </article>
          </section>
        </div>

        <aside class="card section-card insights-card">
          <div class="section-heading compact">
            <div>
              <p class="section-kicker">Overview</p>
              <h2>Résumé opérationnel</h2>
            </div>
          </div>

          <p class="summary-intro">
            Lecture synthétique de l’organisation pour prioriser les actions RH, les points d’attention
            et les zones stables.
          </p>

          <div class="overview-grid">
            <article v-for="card in overviewCards" :key="card.label" class="overview-card">
              <span class="overview-chip">{{ card.tag }}</span>
              <p class="overview-label">{{ card.label }}</p>
              <p class="overview-value">{{ card.value }}</p>
              <p class="overview-copy">{{ card.copy }}</p>
            </article>
          </div>

          <div class="notes-card">
            <h3>Repères rapides</h3>
            <ul>
              <li v-for="note in dashboardNotes" :key="note">{{ note }}</li>
            </ul>
          </div>
        </aside>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { Chart, registerables } from 'chart.js'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'

Chart.register(...registerables)

const filtre = ref('annee')
const dateRef = ref(new Date().toISOString().split('T')[0])
const statsData = ref({})
const alertes = ref([])
const topPerformers = ref([])
const derniersEmployes = ref([])
const loading = ref(false)
const loadStatus = ref({ type: '', text: '' })
const lastRefreshedAt = ref(null)

const chartDepartements = ref(null)
const chartContrats = ref(null)
const chartTendances = ref(null)
const chartAges = ref(null)

const hasDepartements = computed(() => Boolean(statsData.value.repartitions?.departements?.length))
const hasContrats = computed(() => Boolean(statsData.value.repartitions?.types_contrat?.length))
const hasTendances = computed(() => Boolean(statsData.value.tendances?.length))
const hasAges = computed(() => {
  const arr = statsData.value.repartitions?.tranches_age || []
  return arr.some((item) => Number(item.value) > 0)
})

const alertesCritiques = computed(() => alertes.value.filter((item) => item.level === 'danger').length)
const activeHeadcount = computed(() => Number(statsData.value.effectifs?.actifs || 0))
const newEmployees = computed(() => Number(statsData.value.effectifs?.nouveaux || 0))
const turnover = computed(() => Number(statsData.value.indicateurs?.turnover || 0))
const absenteisme = computed(() => Number(statsData.value.indicateurs?.absenteisme || 0))
const performance = computed(() => Number(statsData.value.indicateurs?.performance_moyenne || 0))
const anciennete = computed(() => Number(statsData.value.indicateurs?.anciennete_moyenne || 0))

const topDepartment = computed(() => {
  const departments = [...(statsData.value.repartitions?.departements || [])]
  if (!departments.length) return null
  return departments.sort((left, right) => Number(right.value) - Number(left.value))[0]
})

const periodLabel = computed(() => {
  const map = {
    mois: 'Analyse mensuelle',
    trimestre: 'Analyse trimestrielle',
    annee: 'Analyse annuelle',
  }
  return map[filtre.value] || filtre.value
})

const lastSyncedLabel = computed(() => {
  if (!lastRefreshedAt.value) return 'Jamais'
  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(lastRefreshedAt.value)
})

const metricCards = computed(() => [
  {
    label: 'Employés actifs',
    value: formatInteger(activeHeadcount.value),
    caption: newEmployees.value > 0 ? `+${formatInteger(newEmployees.value)} nouvelles entrées` : 'Aucune nouvelle entrée',
    tag: 'People',
  },
  {
    label: 'Turnover',
    value: `${formatDecimal(turnover.value)}%`,
    caption: turnover.value < 10 ? 'Niveau stable' : 'Point de vigilance',
    tag: 'Retention',
  },
  {
    label: 'Absentéisme',
    value: `${formatDecimal(absenteisme.value)}%`,
    caption: 'Lecture consolidée de la période',
    tag: 'Attendance',
  },
  {
    label: 'Alertes actives',
    value: formatInteger(alertes.value.length),
    caption: alertesCritiques.value > 0 ? `${alertesCritiques.value} critique(s)` : 'Aucune critique détectée',
    tag: 'Signals',
  },
])

const pulseCards = computed(() => [
  {
    label: 'Nouvelles entrées',
    value: formatInteger(newEmployees.value),
    copy: newEmployees.value > 0 ? 'Intégrations confirmées sur la période analysée.' : 'Aucune nouvelle intégration détectée.',
    badge: 'Recrutement',
    tone: newEmployees.value > 0 ? 'accent' : 'neutral',
  },
  {
    label: 'Alertes critiques',
    value: formatInteger(alertesCritiques.value),
    copy: alertesCritiques.value > 0 ? 'Des points sensibles demandent une revue rapide.' : 'Aucun signal critique à traiter immédiatement.',
    badge: 'Priorité',
    tone: alertesCritiques.value > 0 ? 'warning' : 'calm',
  },
  {
    label: 'Performance moyenne',
    value: `${formatDecimal(performance.value)}%`,
    copy: performance.value >= 75 ? 'Le niveau global reste bien orienté.' : 'Un suivi managérial plus fin est recommandé.',
    badge: 'Managers',
    tone: performance.value >= 75 ? 'calm' : 'warning',
  },
  {
    label: 'Département dominant',
    value: topDepartment.value?.label || 'Non disponible',
    copy: topDepartment.value ? `${formatInteger(topDepartment.value.value)} collaborateurs représentés.` : 'La répartition n’est pas encore disponible.',
    badge: 'Structure',
    tone: 'neutral',
  },
])

const overviewCards = computed(() => [
  {
    label: 'Effectif actif',
    value: formatInteger(activeHeadcount.value),
    copy: newEmployees.value > 0 ? `${formatInteger(newEmployees.value)} nouvelles entrées sur la période.` : 'Pas de nouvelles entrées sur la période.',
    tag: 'People',
  },
  {
    label: 'Performance moyenne',
    value: `${formatDecimal(performance.value)}%`,
    copy: performance.value >= 75 ? 'Niveau global satisfaisant.' : 'Suivi managérial recommandé.',
    tag: 'Performance',
  },
  {
    label: 'Ancienneté moyenne',
    value: `${formatDecimal(anciennete.value)} an${anciennete.value > 1 ? 's' : ''}`,
    copy: 'Repère de stabilité et de maturité organisationnelle.',
    tag: 'Tenure',
  },
  {
    label: 'Département dominant',
    value: topDepartment.value?.label || 'Non disponible',
    copy: topDepartment.value ? `${formatInteger(topDepartment.value.value)} collaborateurs représentés.` : 'Aucune répartition disponible.',
    tag: 'Structure',
  },
])

const dashboardNotes = computed(() => [
  alertesCritiques.value > 0
    ? `${formatInteger(alertesCritiques.value)} alerte(s) critique(s) demandent une revue rapide.`
    : 'Aucune alerte critique n’est remontée sur la période analysée.',
  topDepartment.value
    ? `${topDepartment.value.label} reste le pôle le plus représenté avec ${formatInteger(topDepartment.value.value)} collaborateurs.`
    : 'La répartition par département est encore indisponible.',
  turnover.value < 10 && absenteisme.value < 5
    ? 'Le turnover et l’absentéisme restent dans une zone globalement maîtrisée.'
    : 'Le turnover ou l’absentéisme mérite une lecture plus attentive.',
])

let charts = {}
let themeObserver = null

const chartColors = () => {
  const styles = getComputedStyle(document.documentElement)
  return {
    muted: styles.getPropertyValue('--muted').trim() || '#64748b',
    border: styles.getPropertyValue('--border').trim() || '#e2e8f0',
    palette: ['#4f46e5', '#14b8a6', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16'],
    success: '#16a34a',
    warning: '#f59e0b',
    danger: '#ef4444',
    info: '#3b82f6',
  }
}

const destroyCharts = () => {
  Object.values(charts).forEach((chart) => chart?.destroy())
  charts = {}
}

const loadData = async () => {
  loading.value = true
  loadStatus.value = { type: '', text: '' }

  try {
    const [statsRes, alertesRes, perfRes, empRes] = await Promise.all([
      api.get('/v1/dashboard/statistiques', {
        params: { filtre: filtre.value, date: dateRef.value },
      }),
      api.get('/v1/alertes'),
      api.get('/v1/dashboard/top-performers', { params: { limite: 5 } }),
      api.get('/v1/employes', { params: { per_page: 5, sort: 'recent' } }),
    ])

    statsData.value = statsRes.data || {}
    alertes.value = alertesRes.data.data || []
    topPerformers.value = perfRes.data.data || []

    const empData = empRes.data.data || empRes.data || []
    derniersEmployes.value = Array.isArray(empData) ? empData : []

    lastRefreshedAt.value = new Date()
  } catch (error) {
    console.error('Erreur chargement dashboard:', error)
    loadStatus.value = {
      type: 'warning',
      text: 'Certaines données n’ont pas pu être chargées. Le tableau de bord utilise un mode dégradé.',
    }

    try {
      const [emp] = await Promise.all([api.get('/v1/employes')])
      derniersEmployes.value = (emp.data.data || []).slice(0, 5)
      statsData.value = {
        effectifs: { actifs: emp.data.total ?? emp.data.data?.length ?? 0, nouveaux: 0 },
        indicateurs: { turnover: 0, absenteisme: 0, performance_moyenne: 0, anciennete_moyenne: 0 },
        repartitions: {},
        tendances: [],
      }
      alertes.value = []
      topPerformers.value = []
      lastRefreshedAt.value = new Date()
    } catch (fallbackError) {
      console.error('Erreur fallback dashboard:', fallbackError)
      statsData.value = {
        effectifs: { actifs: 0, nouveaux: 0 },
        indicateurs: { turnover: 0, absenteisme: 0, performance_moyenne: 0, anciennete_moyenne: 0 },
        repartitions: {},
        tendances: [],
      }
      alertes.value = []
      topPerformers.value = []
      derniersEmployes.value = []
      lastRefreshedAt.value = new Date()
    }
  }

  loading.value = false
  await nextTick()
  updateCharts()
}

const updateCharts = () => {
  if (!chartDepartements.value || !chartContrats.value || !chartTendances.value || !chartAges.value) {
    return
  }

  destroyCharts()

  if (!statsData.value.repartitions) return

  const colors = chartColors()

  if (chartDepartements.value && statsData.value.repartitions.departements?.length) {
    const data = statsData.value.repartitions.departements
    charts.departements = new Chart(chartDepartements.value, {
      type: 'doughnut',
      data: {
        labels: data.map((item) => item.label),
        datasets: [
          {
            data: data.map((item) => item.value),
            backgroundColor: colors.palette.slice(0, data.length),
            borderWidth: 0,
            hoverOffset: 10,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { color: colors.muted, font: { size: 11 } },
          },
        },
        cutout: '68%',
      },
    })
  }

  if (chartContrats.value && statsData.value.repartitions.types_contrat?.length) {
    const data = statsData.value.repartitions.types_contrat
    charts.contrats = new Chart(chartContrats.value, {
      type: 'doughnut',
      data: {
        labels: data.map((item) => item.label),
        datasets: [
          {
            data: data.map((item) => item.value),
            backgroundColor: [colors.success, colors.info, colors.warning, colors.danger, '#8b5cf6'],
            borderWidth: 0,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { color: colors.muted, font: { size: 11 } },
          },
        },
        cutout: '62%',
      },
    })
  }

  if (chartTendances.value && statsData.value.tendances?.length) {
    const data = statsData.value.tendances
    charts.tendances = new Chart(chartTendances.value, {
      type: 'line',
      data: {
        labels: data.map((item) => item.label),
        datasets: [
          {
            label: 'Effectif',
            data: data.map((item) => item.effectif),
            borderColor: colors.palette[0],
            backgroundColor: 'rgba(79, 70, 229, 0.12)',
            fill: true,
            tension: 0.4,
          },
          {
            label: 'Entrées',
            data: data.map((item) => item.entrees),
            borderColor: colors.success,
            backgroundColor: 'transparent',
            borderDash: [5, 5],
            tension: 0.35,
          },
          {
            label: 'Sorties',
            data: data.map((item) => item.sorties),
            borderColor: colors.danger,
            backgroundColor: 'transparent',
            borderDash: [5, 5],
            tension: 0.35,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: { color: colors.muted },
          },
        },
        scales: {
          x: {
            grid: { color: colors.border },
            ticks: { color: colors.muted },
          },
          y: {
            beginAtZero: true,
            grid: { color: colors.border },
            ticks: { color: colors.muted },
          },
        },
      },
    })
  }

  if (chartAges.value && statsData.value.repartitions.tranches_age?.length && hasAges.value) {
    const data = statsData.value.repartitions.tranches_age
    charts.ages = new Chart(chartAges.value, {
      type: 'bar',
      data: {
        labels: data.map((item) => item.label),
        datasets: [
          {
            label: 'Employés',
            data: data.map((item) => item.value),
            backgroundColor: colors.palette.slice(0, data.length),
            borderRadius: 8,
          },
        ],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
        },
        scales: {
          x: {
            beginAtZero: true,
            grid: { color: colors.border },
            ticks: { color: colors.muted },
          },
          y: {
            grid: { display: false },
            ticks: { color: colors.muted },
          },
        },
      },
    })
  }
}

const observeTheme = () => {
  const callback = () => nextTick(() => updateCharts())
  themeObserver = new MutationObserver(callback)

  if (document.documentElement) {
    themeObserver.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['data-theme'],
    })
  }

  if (document.body) {
    themeObserver.observe(document.body, {
      attributes: true,
      attributeFilter: ['data-theme'],
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
    fin_contrat: 'Contrat',
    conges_non_pris: 'Congés',
    absences_maladie: 'Maladie',
    absences_exceptionnelles: 'Absences',
    conge_en_attente: 'Demande',
    conge_proche: 'Congé urgent',
  }
  return types[type] || type
}

const priorityLevelLabel = (level) => {
  if (level === 'danger') return 'Critique'
  if (level === 'warning') return 'À traiter'
  return 'Information'
}

const formatAlertMessage = (message) => {
  if (!message) return 'Signal RH'

  return String(message).replace(/(\d+(?:\.\d+)?)(?=\s+jours?\b)/gi, (value) => {
    const parsed = Number(value)
    if (Number.isNaN(parsed)) return value
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 0,
      maximumFractionDigits: parsed % 1 === 0 ? 0 : 1,
    }).format(parsed)
  })
}

const normalizeEmployeName = (employe) => {
  if (!employe) return 'Profil non défini'
  return `${employe.nom || ''} ${employe.prenom || ''}`.trim() || employe.name || 'Profil non défini'
}

const normalizePoste = (employe) => employe?.poste?.nom || employe?.poste || 'Poste non défini'

const formatInteger = (value) => new Intl.NumberFormat('fr-FR').format(Number(value || 0))
const formatDecimal = (value) =>
  new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 1,
  }).format(Number(value || 0))

onMounted(() => {
  loadData()
  observeTheme()
})

onUnmounted(() => {
  destroyCharts()
  themeObserver?.disconnect()
})
</script>

<style scoped>
.dashboard-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding-bottom: 24px;
}

.hero {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 18px;
  padding: 28px;
  border: 1px solid rgba(79, 70, 229, 0.14);
  border-radius: 30px;
  background:var(--purple-100);
  box-shadow: var(--shadow-lg);
}

body[data-theme='dark'] .hero {
  background:
    linear-gradient(135deg, rgba(79, 70, 229, 0.18), rgba(15, 23, 42, 0)),
    rgba(15, 23, 42, 0.88);
}

.hero-copy {
  max-width: 760px;
}

.hero-kicker,
.section-kicker,
.metric-label,
.metric-caption,
.hero-meta,
.summary-intro,
.overview-label,
.overview-copy,
.priority-type,
.empty-state span {
  margin: 0;
}

.hero-kicker,
.section-kicker {
  color: var(--brand-600);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.hero h1,
.section-heading h2,
.chart-head h2 {
  margin: 8px 0 0;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.hero h1 {
  font-size: clamp(2rem, 3vw, 2.9rem);
}

.hero-subtitle {
  margin: 12px 0 0;
  max-width: 700px;
  color: var(--muted);
  font-size: 1rem;
  line-height: 1.7;
}

.hero-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 18px;
}

.hero-actions {
  display: flex;
  min-width: 320px;
  max-width: 360px;
  flex-direction: column;
  gap: 12px;
}

.filters-panel {
  display: grid;
  gap: 14px;
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.76);
}

body[data-theme='dark'] .filters-panel {
  background: rgba(15, 23, 42, 0.72);
}

.filters-grid {
  display: grid;
  gap: 12px;
}

.filter-field {
  display: grid;
  gap: 6px;
}

.filter-field span {
  color: var(--muted);
  font-size: 0.82rem;
  font-weight: 600;
}

.hero-action-row {
  display: flex;
  gap: 10px;
}

.hero-action-row > * {
  flex: 1;
}

.hero-meta-list {
  display: grid;
  gap: 6px;
}

.hero-meta {
  color: var(--muted);
  font-size: 0.85rem;
}

.status-banner {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: rgba(255, 255, 255, 0.78);
  color: var(--text);
  font-weight: 600;
}

body[data-theme='dark'] .status-banner {
  background: rgba(15, 23, 42, 0.78);
}

.status-banner.warning {
  border-color: rgba(247, 144, 9, 0.18);
  background: var(--warning-100);
  color: var(--warning-500);
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: currentColor;
}

.metric-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.metric-card {
  padding: 18px 20px;
  border: 1px solid var(--border);
  border-radius: 24px;
  background: var(--panel);
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 10px;
}

.metric-chip,
.overview-chip,
.section-chip,
.signal-badge,
.alert-type {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  padding: 7px 12px;
  border-radius: 999px;
  border: 1px solid rgba(79, 70, 229, 0.12);
  background: rgba(79, 70, 229, 0.1);
  color: var(--brand-600);
  font-size: 0.76rem;
  font-weight: 700;
}

.metric-label {
  color: var(--muted);
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.metric-value {
  margin: 10px 0 8px;
  font-size: 1.82rem;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.metric-caption {
  color: var(--muted);
  font-size: 0.9rem;
  line-height: 1.5;
}

.loading-card {
  min-height: 180px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8px;
}

.loading-title,
.overview-value,
.priority-message,
.empty-state p {
  margin: 0;
}

.loading-title {
  font-size: 1.1rem;
  font-weight: 800;
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.8fr) minmax(320px, 0.9fr);
  gap: 18px;
  align-items: start;
}

.main-column {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.summary-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.25fr) minmax(320px, 0.95fr);
  gap: 18px;
}

.section-card {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.section-heading,
.chart-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.section-heading.compact {
  margin-bottom: 2px;
}

.section-heading h2,
.chart-head h3 {
  font-size: 1.48rem;
}

.chart-head h3 {
  margin: 8px 0 0;
  font-weight: 800;
  letter-spacing: -0.03em;
  font-size: 1.2rem;
}

.section-copy {
  margin: 0;
  color: var(--muted);
  font-size: 0.95rem;
  line-height: 1.65;
}

.signal-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.signal-card,
.chart-card {
  display: grid;
  gap: 12px;
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: 22px;
  background: rgba(248, 250, 252, 0.78);
}

body[data-theme='dark'] .signal-card,
body[data-theme='dark'] .chart-card,
body[data-theme='dark'] .priority-item,
body[data-theme='dark'] .performer-item,
body[data-theme='dark'] .overview-card {
  background: rgba(15, 23, 42, 0.46);
}

.signal-card.signal-warning {
  border-color: rgba(247, 144, 9, 0.24);
}

.signal-card.signal-calm {
  border-color: rgba(20, 184, 166, 0.18);
}

.signal-card.signal-accent {
  border-color: rgba(79, 70, 229, 0.2);
}

.signal-label,
.signal-copy {
  margin: 0;
}

.signal-label {
  color: var(--muted);
  font-size: 0.82rem;
  font-weight: 700;
}

.signal-value {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.signal-copy {
  color: var(--muted);
  font-size: 0.84rem;
  line-height: 1.55;
}

.analytics-card {
  gap: 20px;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.chart-wide {
  grid-column: span 2;
}

.chart-container {
  position: relative;
  height: 265px;
}

.chart-container.large {
  height: 300px;
}

.chart-container canvas {
  width: 100% !important;
  height: 100% !important;
  display: block;
}

.chart-hidden {
  opacity: 0;
}

.chart-empty {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--muted);
  border: 1px dashed var(--border);
  border-radius: 18px;
}

.priority-list,
.performers-list {
  display: grid;
  gap: 12px;
}

.priority-item,
.performer-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: rgba(248, 250, 252, 0.8);
}

.priority-item {
  align-items: flex-start;
  gap: 14px;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.58), rgba(248, 250, 252, 0.92)),
    rgba(248, 250, 252, 0.8);
}

body[data-theme='dark'] .priority-item {
  background:
    linear-gradient(180deg, rgba(15, 23, 42, 0.62), rgba(15, 23, 42, 0.88)),
    rgba(15, 23, 42, 0.46);
}

.priority-danger,
.signal-warning {
  border-color: rgba(239, 68, 68, 0.18);
}

.priority-warning {
  border-color: rgba(247, 144, 9, 0.18);
}

.priority-visual {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  flex: none;
  border-radius: 14px;
  border: 1px solid var(--border);
  color: var(--brand-600);
  background: rgba(79, 70, 229, 0.08);
}

.priority-visual-warning {
  color: #d97706;
  background: rgba(245, 158, 11, 0.12);
  border-color: rgba(245, 158, 11, 0.2);
}

.priority-visual-danger {
  color: #dc2626;
  background: rgba(239, 68, 68, 0.12);
  border-color: rgba(239, 68, 68, 0.2);
}

.priority-copy {
  min-width: 0;
  display: grid;
  gap: 8px;
}

.priority-topline {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.priority-message {
  font-size: 0.98rem;
  font-weight: 700;
  line-height: 1.4;
}

.priority-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--brand-600);
  background: rgba(79, 70, 229, 0.1);
  border: 1px solid rgba(79, 70, 229, 0.16);
}

.priority-pill-warning {
  color: #b45309;
  background: rgba(245, 158, 11, 0.12);
  border-color: rgba(245, 158, 11, 0.2);
}

.priority-pill-danger {
  color: #b42318;
  background: rgba(239, 68, 68, 0.12);
  border-color: rgba(239, 68, 68, 0.2);
}

.priority-type {
  color: var(--muted);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.table-shell {
  overflow: auto;
}

.empty-table {
  color: var(--muted);
  text-align: center;
  padding: 22px 14px;
}

.insights-card {
  position: sticky;
  top: 18px;
}

.summary-intro {
  color: var(--muted);
  font-size: 0.92rem;
  line-height: 1.6;
}

.overview-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.overview-card {
  display: grid;
  gap: 10px;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: rgba(248, 250, 252, 0.82);
}

.overview-label {
  color: var(--muted);
  font-size: 0.82rem;
}

.overview-value {
  font-size: 1.22rem;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.overview-copy {
  color: var(--muted);
  font-size: 0.84rem;
  line-height: 1.5;
}

.notes-card {
  padding: 18px 18px 20px;
  border: 1px solid var(--border);
  border-radius: 24px;
  background: rgba(248, 250, 252, 0.84);
}

body[data-theme='dark'] .notes-card {
  background: rgba(15, 23, 42, 0.56);
}

.notes-card h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.notes-card ul {
  margin: 14px 0 0;
  padding-left: 18px;
  color: var(--muted);
  display: grid;
  gap: 10px;
}

.inline-link {
  color: var(--brand-600);
  font-size: 0.84rem;
  font-weight: 700;
}

.rank {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--brand-500), var(--brand-700));
  color: #fff;
  font-size: 0.8rem;
  font-weight: 800;
}

.performer-info {
  flex: 1;
  display: grid;
  gap: 3px;
}

.performer-info .name {
  font-weight: 700;
}

.performer-info .role {
  color: var(--muted);
  font-size: 0.8rem;
}

.score-badge {
  padding: 6px 10px;
  border-radius: 999px;
  font-weight: 800;
  font-size: 0.8rem;
}

.score-excellent {
  background: rgba(34, 197, 94, 0.18);
  color: #16a34a;
}

.score-good {
  background: rgba(59, 130, 246, 0.18);
  color: #2563eb;
}

.score-average {
  background: rgba(245, 158, 11, 0.18);
  color: #d97706;
}

.score-low {
  background: rgba(239, 68, 68, 0.18);
  color: #dc2626;
}

.empty-state {
  display: grid;
  gap: 6px;
  padding: 24px 10px 8px;
  text-align: center;
}

.empty-state p {
  font-weight: 700;
}

.empty-state.compact {
  padding: 18px 0 4px;
}

@media (max-width: 1200px) {
  .metric-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .content-grid {
    grid-template-columns: 1fr;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }

  .insights-card {
    position: static;
  }
}

@media (max-width: 900px) {
  .hero {
    padding: 22px;
  }

  .hero-actions {
    min-width: 100%;
    max-width: none;
  }

  .charts-grid,
  .signal-grid,
  .detail-grid,
  .overview-grid {
    grid-template-columns: 1fr;
  }

  .chart-wide {
    grid-column: span 1;
  }
}

@media (max-width: 680px) {
  .metric-grid {
    grid-template-columns: 1fr;
  }

  .hero-action-row {
    flex-direction: column;
  }

  .section-heading,
  .chart-head {
    flex-direction: column;
  }

  .priority-item,
  .performer-item {
    align-items: flex-start;
  }

  .score-badge {
    white-space: normal;
  }
}
</style>
