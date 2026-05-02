<template>
  <div class="rh-page payroll-followup-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Payroll follow-up</p>
        <h1>Suivi de paie</h1>
        <p class="hero-subtitle">
          Suivez l’avancement mensuel des paies entre prévision, attente de validation, validation et paiement.
        </p>

        <div class="hero-pills">
          <span class="pill">Prévision</span>
          <span class="pill">Validation</span>
          <span class="pill">Paiement</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="refresh" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Chargement...' : 'Actualiser' }}</span>
            </button>
          </div>

          <label class="field-card">
            <span class="field-label">Mode</span>
            <select class="select" v-model="mode" @change="refresh">
              <option value="annee">Année</option>
              <option value="periode">Période</option>
            </select>
          </label>

          <label v-if="mode === 'annee'" class="field-card">
            <span class="field-label">Année</span>
            <input class="input" type="number" min="2000" max="2100" v-model.number="year" @change="refresh" />
          </label>

          <div v-else class="action-row">
            <label class="field-card">
              <span class="field-label">Début</span>
              <input class="input" type="month" v-model="periodStart" @change="refresh" />
            </label>
            <label class="field-card">
              <span class="field-label">Fin</span>
              <input class="input" type="month" v-model="periodEnd" @change="refresh" />
            </label>
          </div>

          <div v-if="error" class="status-banner danger">
            <span class="status-dot"></span>
            <span>{{ error }}</span>
          </div>
          <div v-if="syntheseStatus.text" class="status-banner warning">
            <span class="status-dot"></span>
            <span>{{ syntheseStatus.text }}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="metric-grid">
      <article v-for="metric in metrics" :key="metric.label" class="metric-card">
        <span class="metric-chip">{{ metric.tag }}</span>
        <p class="metric-label">{{ metric.label }}</p>
        <p class="metric-value">{{ metric.value }}</p>
        <p class="metric-caption">{{ metric.caption }}</p>
      </article>
    </section>

    <section class="content-grid">
      <article class="card section-card table-card">
        <div class="section-heading">
          <div>
            <p class="section-kicker">Monthly progress</p>
            <h2>{{ titleLabel }}</h2>
          </div>
          <span class="section-chip">{{ formatInteger(rows.length) }} mois</span>
        </div>

        <div class="table-shell">
          <table class="table">
            <thead>
              <tr>
                <th>Mois</th>
                <th>Origine</th>
                <th>Employés</th>
                <th>À générer</th>
                <th>Attente validation génération</th>
                <th>Non payé</th>
                <th>Attente validation paiement</th>
                <th>Payé</th>
                <th>Net total</th>
                <th>Net payé</th>
                <th>Reste</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.mois">
                <td class="cell-stack">
                  <div class="type-name">{{ monthName(row.mois) }}</div>
                  <div>{{ monthYear(row.mois) }}</div>
                  <div class="muted">{{ row.mois }}</div>
                </td>
                <td>
                  <span class="chip source-chip" :class="sourceClass(row.origine_code)">
                    {{ row.origine_label }}
                  </span>
                </td>
                <td>{{ formatInteger(row.employes) }}</td>
                <td>
                  <StatusRatio class-name="muted" :count="row.a_generer" :total="row.employes" :percent="row.pourcentage_a_generer" />
                </td>
                <td>
                  <StatusRatio :count="row.attente_validation_generer" :total="row.employes" :percent="row.pourcentage_attente_validation_generer" />
                </td>
                <td>
                  <StatusRatio :count="row.non_paye" :total="row.employes" :percent="row.pourcentage_non_paye" />
                </td>
                <td>
                  <StatusRatio :count="row.attente_validation_paye" :total="row.employes" :percent="row.pourcentage_attente_validation_paye" />
                </td>
                <td>
                  <StatusRatio :count="row.paye" :total="row.employes" :percent="row.pourcentage_paye" />
                </td>
                <td>{{ formatMoney(row.net_total) }}</td>
                <td>{{ formatMoney(row.net_paye) }}</td>
                <td class="accent">{{ formatMoney(row.net_restant) }}</td>
                <td>
                  <RouterLink class="btn btn-secondary btn-xs" :to="`/paie-etat?mois=${row.mois}`">
                    Détail
                  </RouterLink>
                </td>
              </tr>
              <tr v-if="!rows.length">
                <td colspan="12" class="muted">Aucun mois à afficher.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </section>
  </div>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'
import { formatMoneyAmount } from '../utils/formatters'

const StatusRatio = defineComponent({
  props: {
    className: { type: String, default: 'muted' },
    count: { type: Number, default: 0 },
    total: { type: Number, default: 0 },
    percent: { type: Number, default: 0 },
  },
  setup(props) {
    return () => h('div', { class: 'ratio-cell' }, [
      h('div', { class: 'ratio-row' }, [
        h('div', { class: 'ratio-copy' }, [
          h('div', { class: 'ratio-percent' }, formatPercent(props.percent)),
          h('div', { class: ['ratio-count', props.className] }, `${formatInteger(props.count)} / ${formatInteger(props.total)}`),
        ]),
      ]),
    ])
  },
})

const now = new Date()
const mode = ref('annee')
const year = ref(now.getFullYear())
const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
const periodStart = ref(`${now.getFullYear()}-01`)
const periodEnd = ref(currentMonth)
const rows = ref([])
const totals = ref({})
const loading = ref(false)
const error = ref('')
const syntheseStatus = ref({ text: '' })
let synthesePollingTimer = null
const SYNTHESE_POLLING_SECONDS = 7

const titleLabel = computed(() => {
  if (mode.value === 'annee') return `Suivi ${year.value}`
  return `Suivi ${periodStart.value || '—'} → ${periodEnd.value || '—'}`
})

const metrics = computed(() => [
  {
    tag: 'Generate',
    label: 'À générer',
    value: `${formatPercent(totals.value.pourcentage_a_generer)} · ${formatInteger(totals.value.a_generer)} / ${formatInteger(totals.value.employes_mois)}`,
    caption: 'Aucune fiche générée sur la période',
  },
  {
    tag: 'Pending',
    label: 'Attente validation génération',
    value: `${formatPercent(totals.value.pourcentage_attente_validation_generer)} · ${formatInteger(totals.value.attente_validation_generer)} / ${formatInteger(totals.value.employes_mois)}`,
    caption: 'Fiches générées à valider',
  },
  {
    tag: 'Unpaid',
    label: 'Non payé',
    value: `${formatPercent(totals.value.pourcentage_non_paye)} · ${formatInteger(totals.value.non_paye)} / ${formatInteger(totals.value.employes_mois)}`,
    caption: `${formatMoney(totals.value.net_restant)} restant hors payé`,
  },
  {
    tag: 'Paid',
    label: 'Payé',
    value: `${formatPercent(totals.value.pourcentage_paye)} · ${formatInteger(totals.value.paye)} / ${formatInteger(totals.value.employes_mois)}`,
    caption: `${formatMoney(totals.value.net_paye)} déjà payé`,
  },
])

const formatInteger = (value) => new Intl.NumberFormat('fr-FR').format(Number(value || 0))
const formatPercent = (value) => `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(Number(value || 0))} %`
const formatMoney = (value) => formatMoneyAmount(value)

const monthLabel = (month) => {
  if (!month) return '—'
  const [yearPart, monthPart] = month.split('-').map(Number)
  return new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' }).format(new Date(yearPart, monthPart - 1, 1))
}

const monthName = (month) => {
  if (!month) return '—'
  const [yearPart, monthPart] = month.split('-').map(Number)
  return new Intl.DateTimeFormat('fr-FR', { month: 'long' }).format(new Date(yearPart, monthPart - 1, 1))
}

const monthYear = (month) => month?.slice(0, 4) || '—'

const sourceClass = (source) => ({
  success: source === 'reel' || source === 'reel_calcule',
  warning: source === 'mixte',
  muted: source === 'prevision',
})

const refresh = async () => {
  loading.value = true
  error.value = ''
  syntheseStatus.value = { text: '' }
  clearSynthesePolling()
  try {
    const params = mode.value === 'annee'
      ? { annee: String(year.value) }
      : { debut: periodStart.value, fin: periodEnd.value }
    const { data } = await api.get('/v1/paies/suivi', { params })
    rows.value = data.mois || []
    totals.value = data.totaux || {}
    syntheseStatus.value = paieSyntheseStatus(data.synthese)
    if (data.synthese?.status === 'generating') {
      scheduleSynthesePolling()
    }
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Erreur chargement suivi de paie'
  } finally {
    loading.value = false
  }
}

const scheduleSynthesePolling = () => {
  clearSynthesePolling()
  synthesePollingTimer = window.setTimeout(() => {
    refresh()
  }, SYNTHESE_POLLING_SECONDS * 1000)
}

const clearSynthesePolling = () => {
  if (!synthesePollingTimer) return
  window.clearTimeout(synthesePollingTimer)
  synthesePollingTimer = null
}

const paieSyntheseStatus = (synthese) => {
  if (synthese?.status === 'generating') {
    return { text: withSyntheseRefreshDelay(synthese.message || 'La synthèse de paie est en cours de génération.') }
  }

  if (synthese?.status === 'stale') {
    return { text: synthese.message || 'La synthèse de paie est affichée, mais une mise à jour est en cours.' }
  }

  if (synthese?.status === 'missing_snapshot') {
    return { text: synthese.message || 'La synthèse de paie n’est pas encore générée pour cette période.' }
  }

  if (synthese?.status === 'error') {
    return { text: synthese.error_message || synthese.message || 'La génération de la synthèse de paie a échoué.' }
  }

  return { text: '' }
}

const withSyntheseRefreshDelay = (message) =>
  `${message} Rafraîchissement automatique dans ${SYNTHESE_POLLING_SECONDS} secondes.`

onMounted(refresh)
onUnmounted(clearSynthesePolling)
</script>

<style scoped>
.payroll-followup-page .content-grid {
  grid-template-columns: 1fr;
}

.payroll-followup-page .table-card,
.payroll-followup-page .table-shell {
  width: 100%;
  min-width: 0;
}

.ratio-cell {
  min-width: 150px;
}

.ratio-row {
  display: block;
}

.ratio-copy {
  min-width: 0;
}

.ratio-percent {
  color: var(--ink);
  font-weight: 500;
  font-size: 0.96rem;
}

.ratio-count {
  margin-top: 4px;
  color: var(--muted);
  font-size: 0.84rem;
}

.source-chip {
  white-space: nowrap;
}

.chip.success {
  background: var(--success-100);
  color: var(--success-500);
}

.chip.warning {
  background: var(--warning-100);
  color: var(--warning-500);
}

.chip.muted {
  background: rgba(148, 163, 184, 0.12);
  color: var(--muted);
}

.accent {
  color: var(--brand-600);
  font-weight: 800;
}
</style>
