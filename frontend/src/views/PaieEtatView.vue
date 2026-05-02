<template>
  <div class="rh-page paie-etat-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Payroll reporting</p>
        <h1>État de paie</h1>
        <p class="hero-subtitle">
          Suivi mensuel des employés avec contrat actif, des fiches générées, des validations et des paiements.
        </p>

        <div class="hero-pills">
          <span class="pill">Contrats actifs</span>
          <span class="pill">Validation</span>
          <span class="pill">Paiement</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="refresh" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>{{ loading ? 'Actualisation...' : 'Actualiser' }}</span>
            </button>
          </div>

          <label class="field-card">
            <span class="field-label">Mode</span>
            <select class="select" v-model="mode" @change="resetAndRefresh">
              <option value="mois">Mois</option>
              <option value="annee">Année</option>
              <option value="periode">Période</option>
            </select>
          </label>

          <label class="field-card">
            <span class="field-label">Statut fiche</span>
            <select class="select" v-model="statusFilter" @change="resetAndRefresh">
              <option value="tous">Tous</option>
              <option value="non_genere">Non générée</option>
              <option value="en_attente_validation">En attente de validation</option>
              <option value="non_paye">Non payé</option>
              <option value="paiement_en_validation">Paiement en validation</option>
              <option value="paye">Payé</option>
            </select>
          </label>

          <label class="field-card" v-if="mode === 'mois'">
            <span class="field-label">Matricule</span>
            <input class="input" v-model="detailFilters.matricule" placeholder="EMP-001" list="paie-etat-matricules" />
          </label>

          <label class="field-card" v-if="mode === 'mois'">
            <span class="field-label">Nom employé</span>
            <input class="input" v-model="detailFilters.nom" placeholder="Nom ou prénom" list="paie-etat-noms" />
          </label>

          <label class="field-card" v-if="mode === 'mois'">
            <span class="field-label">Contrat</span>
            <input class="input" v-model="detailFilters.contrat" placeholder="Numéro contrat" list="paie-etat-contrats" />
          </label>

          <div class="action-row" v-if="mode === 'mois'">
            <label class="field-card">
              <span class="field-label">Année</span>
              <input class="input" type="number" min="2000" max="2100" v-model.number="year" @change="resetAndRefresh" />
            </label>
            <label class="field-card">
              <span class="field-label">Mois</span>
              <select class="select" v-model="month" @change="resetAndRefresh">
                <option v-for="m in monthOptions" :key="m.value" :value="m.value">{{ m.label }}</option>
              </select>
            </label>
          </div>

          <label class="field-card" v-else-if="mode === 'annee'">
            <span class="field-label">Année</span>
            <input class="input" type="number" min="2000" max="2100" v-model.number="year" @change="resetAndRefresh" />
          </label>

          <div class="action-row" v-else>
            <label class="field-card">
              <span class="field-label">Début période</span>
              <input class="input" type="month" v-model="periodStart" @change="resetAndRefresh" />
            </label>
            <label class="field-card">
              <span class="field-label">Fin période</span>
              <input class="input" type="month" v-model="periodEnd" @change="resetAndRefresh" />
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

          <datalist id="paie-etat-matricules">
            <option v-for="matricule in optionsMatricules" :key="matricule" :value="matricule" />
          </datalist>
          <datalist id="paie-etat-noms">
            <option v-for="nom in optionsNoms" :key="nom" :value="nom" />
          </datalist>
          <datalist id="paie-etat-contrats">
            <option v-for="contrat in optionsContrats" :key="contrat" :value="contrat" />
          </datalist>
        </div>
      </div>
    </section>

    <section class="metric-grid">
      <article v-for="metric in allMetrics" :key="metric.label" class="metric-card">
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
            <p class="section-kicker">Payroll state</p>
            <h2>{{ titleLabel }}</h2>
          </div>
          <span class="section-chip">{{ formatInteger(totaux.bulletins) }} fiches générées</span>
        </div>

        <div v-if="isSummaryMode" class="table-shell">
          <table class="table">
            <thead>
              <tr>
                <th>Mois</th>
                <th>Fiches</th>
                <th>Payées</th>
                <th>Non payées</th>
                <th>Total brut</th>
                <th>Retenues</th>
                <th>Déjà payé</th>
                <th>Reste à payer</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in parMois" :key="row.mois">
                <td>{{ row.mois }}</td>
                <td>{{ formatInteger(row.bulletins) }}</td>
                <td>{{ formatInteger(row.bulletins_payes) }}</td>
                <td>{{ formatInteger(row.bulletins_non_payes) }}</td>
                <td>{{ formatMoney(row.total_brut) }}</td>
                <td>{{ formatMoney(row.total_retenues) }}</td>
                <td>{{ formatMoney(row.deja_paye) }}</td>
                <td class="accent">{{ formatMoney(row.reste_a_payer) }}</td>
              </tr>
              <tr v-if="!parMois.length">
                <td colspan="8" class="muted">Aucune fiche sur cette période.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="table-shell">
          <table class="table">
            <thead>
              <tr>
                <th>Employé</th>
                <th>Contrat</th>
                <th>Total brut</th>
                <th>Retenues</th>
                <th>Net retenu</th>
                <th>Origine</th>
                <th>Statut</th>
                <th>Demande validation</th>
                <th>Validation paie</th>
                <th>Paiement</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in detailsFiltres" :key="`${row.employe_id}-${row.contrat_id}`">
                <td class="cell-stack">
                  <div class="type-name">{{ row.employe?.matricule || '—' }}</div>
                  <div class="muted">{{ fullName(row.employe) }}</div>
                </td>
                <td class="cell-stack">
                  <div>{{ row.contrat_numero || `#${row.contrat_id}` }}</div>
                  <div class="muted">{{ formatDate(row.contrat_debut) || '—' }} → {{ formatDate(row.contrat_fin) || '—' }}</div>
                </td>
                <td class="cell-stack">
                  <div>{{ formatMoney(row.total_brut) }}</div>
                  <div class="muted">{{ row.source_montants_description || (row.est_prevision ? 'Hypothèse présence' : 'Montant réel') }}</div>
                </td>
                <td>{{ formatMoney(row.total_retenues) }}</td>
                <td class="accent">{{ formatMoney(row.net_a_payer) }}</td>
                <td>
                  <span class="chip source-chip" :class="sourceClass(row.source_montants)">
                    {{ row.source_montants_label || sourceLabel(row.source_montants) }}
                  </span>
                </td>
                <td class="status-col">
                  <div class="status-chip-scroll">
                    <span class="chip status-chip" :class="statusClass(row.statut)">{{ row.statut_label }}</span>
                  </div>
                </td>
                <td>{{ formatDateTime(row.demande_validation_le) || '—' }}</td>
                <td>{{ formatDateTime(row.valide_le) || '—' }}</td>
                <td class="cell-stack">
                  <div>{{ row.caisse_nom || '—' }}</div>
                  <div class="muted">
                    {{ row.paiement_valide_le ? formatDateTime(row.paiement_valide_le) : (row.paiement_demande_le ? `Demandé ${formatDateTime(row.paiement_demande_le)}` : (formatDate(row.paye_le) || '—')) }}
                  </div>
                </td>
                <td class="actions">
                  <div class="actions-stack">
                    <RouterLink class="btn btn-secondary btn-xs" :to="detailRoute(row)">
                      Détail
                    </RouterLink>
                    <button v-if="row.statut === 'non_genere'" class="btn btn-secondary btn-xs" type="button" :disabled="loading" @click="generate(row)">
                      Générer
                    </button>
                    <button v-if="row.statut === 'en_attente_validation'" class="btn btn-secondary btn-xs" type="button" :disabled="loading" @click="validate(row)">
                      Valider
                    </button>
                    <button v-if="row.statut === 'en_attente_validation'" class="btn btn-secondary btn-xs" type="button" :disabled="loading" @click="cancelGeneration(row)">
                      Annuler
                    </button>
                    <div v-if="row.statut === 'non_paye'" class="pay-action">
                      <select class="select select-xs" v-model="selectedCaisseByPaie[row.paie_id]" :disabled="loading">
                        <option disabled value="">Choisir une caisse</option>
                        <option v-for="caisse in caisses" :key="caisse.id" :value="caisse.id">
                          {{ caisse.nom }}
                        </option>
                      </select>
                      <button class="btn btn-secondary btn-xs" type="button" :disabled="loading" @click="pay(row)">
                        Demander paiement
                      </button>
                    </div>
                    <button v-if="['non_paye', 'paiement_en_validation', 'paye'].includes(row.statut)" class="btn btn-secondary btn-xs" type="button" :disabled="loading" @click="downloadPdf(row)">
                      PDF
                    </button>
                    <button v-if="row.statut === 'paye'" class="btn btn-secondary btn-xs" type="button" :disabled="loading" @click="downloadReceipt(row)">
                      Reçu
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!detailsFiltres.length">
                <td colspan="11" class="muted">Aucun employé ne correspond à la période et au statut.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="!isSummaryMode && detailsPagination.total > detailsPagination.per_page" class="pagination-bar">
          <button class="btn btn-secondary btn-xs" type="button" :disabled="loading || detailsPagination.current_page <= 1" @click="previousPage">
            Précédent
          </button>
          <span class="muted">
            Page {{ formatInteger(detailsPagination.current_page) }} / {{ formatInteger(detailsPagination.last_page) }}
            · {{ formatInteger(detailsPagination.total) }} ligne(s)
          </span>
          <button class="btn btn-secondary btn-xs" type="button" :disabled="loading || detailsPagination.current_page >= detailsPagination.last_page" @click="nextPage">
            Suivant
          </button>
        </div>
      </article>
    </section>

  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import api, { getCachedApi, prefetchNextPage } from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'
import { formatDateValue, formatMoneyAmount } from '../utils/formatters'

const loading = ref(false)
const error = ref('')
const syntheseStatus = ref({ text: '' })
let synthesePollingTimer = null
const SYNTHESE_POLLING_SECONDS = 7
const route = useRoute()
const mode = ref('mois')
const statusFilter = ref('tous')

const now = new Date()
const initialMonth = typeof route.query.mois === 'string' && /^\d{4}-\d{2}$/.test(route.query.mois)
  ? route.query.mois
  : `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
const year = ref(Number(initialMonth.slice(0, 4)))
const month = ref(initialMonth.slice(5, 7))
const currentMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
const periodStart = ref(`${now.getFullYear()}-01`)
const periodEnd = ref(currentMonth)

const totaux = ref({})
const details = ref([])
const parMois = ref([])
const statusCounts = ref({})
const cotisations = ref({})
const paymentSummary = ref({})
const paymentDue = ref({})
const caisses = ref([])
const selectedCaisseByPaie = ref({})
const detailFilters = ref({ matricule: '', nom: '', contrat: '' })
const currentPage = ref(1)
const perPage = ref(50)
const detailsPagination = ref({ current_page: 1, per_page: 50, total: 0, last_page: 1 })

const monthOptions = [
  { value: '01', label: 'Janvier' },
  { value: '02', label: 'Février' },
  { value: '03', label: 'Mars' },
  { value: '04', label: 'Avril' },
  { value: '05', label: 'Mai' },
  { value: '06', label: 'Juin' },
  { value: '07', label: 'Juillet' },
  { value: '08', label: 'Août' },
  { value: '09', label: 'Septembre' },
  { value: '10', label: 'Octobre' },
  { value: '11', label: 'Novembre' },
  { value: '12', label: 'Décembre' },
]

const selectedMonth = computed(() => `${year.value}-${month.value}`)
const isSummaryMode = computed(() => mode.value !== 'mois')
const titleLabel = computed(() => {
  if (mode.value === 'annee') return `Synthèse ${year.value}`
  if (mode.value === 'periode') return `Synthèse ${periodStart.value || '—'} → ${periodEnd.value || '—'}`
  return `Employés éligibles ${selectedMonth.value}`
})

const formatInteger = (value) => new Intl.NumberFormat('fr-FR').format(Number(value || 0))
const formatMoney = (amount) => formatMoneyAmount(amount)
const formatPercent = (value) => `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(Number(value || 0))} %`

const formatDateTime = (value) => {
  if (!value) return ''
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value))
}

const formatDate = (value) => formatDateValue(value)

const detailRoute = (row) => row.paie_id
  ? `/paies/${row.paie_id}`
  : `/paies/prevision/${row.employe_id}/${selectedMonth.value}`

const fullName = (employe) => {
  if (!employe) return '—'
  return `${employe.nom || ''} ${employe.prenom || ''}`.trim() || '—'
}

const metrics = computed(() => [
  {
    tag: 'Active',
    label: isSummaryMode.value ? 'Fiches générées' : 'Employés actifs',
    value: formatInteger(isSummaryMode.value ? totaux.value.bulletins : (totaux.value.employes_actifs || details.value.length)),
    caption: mode.value === 'mois' ? 'Contrat actif sur la période' : 'Fiches générées sur la période',
  },
  {
    tag: 'Forecast',
    label: isSummaryMode.value ? 'Total brut' : 'Montants retenus',
    value: formatMoney(isSummaryMode.value ? totaux.value.total_brut : totaux.value.prevision_salaire_base),
    caption: isSummaryMode.value ? 'Selon la source: réel, réel calculé ou prévision' : 'Selon la source: réel, réel calculé ou réel + prévision',
  },
  {
    tag: 'Payment',
    label: 'À payer / déjà payé',
    value: `${formatMoney(totaux.value.reste_a_payer || totaux.value.net_a_payer)} / ${formatMoney(totaux.value.deja_paye)}`,
    caption: `${formatInteger(paymentSummary.value.non_payes)} restant(s), ${formatInteger(statusCounts.value.paye)} payé(s)`,
  },
])

const contributionMetrics = computed(() => [
  {
    tag: 'CNAPS',
    label: 'Total CNAPS',
    value: formatMoney(Number(cotisations.value.cnaps_salarie || 0) + Number(cotisations.value.cnaps_employeur || 0)),
    caption: `Salarié ${formatMoney(cotisations.value.cnaps_salarie)} + Employeur ${formatMoney(cotisations.value.cnaps_employeur)}`,
  },
  {
    tag: 'OSTIE',
    label: 'Total OSTIE',
    value: formatMoney(cotisations.value.ostie),
    caption: 'Part salarié + part employeur',
  },
  {
    tag: 'IRSA',
    label: 'Total IRSA',
    value: formatMoney(cotisations.value.irsa),
    caption: 'Impôt à reverser',
  },
])

const paymentMetrics = computed(() => [
  {
    tag: 'Employee',
    label: 'À payer employés',
    value: formatMoney(paymentDue.value.employes),
    caption: 'Net employé restant à décaisser',
  },
  {
    tag: 'CNAPS',
    label: 'À payer CNAPS',
    value: formatMoney(paymentDue.value.cnaps),
    caption: 'Part salarié + part employeur',
  },
  {
    tag: 'OSTIE',
    label: 'À payer OSTIE',
    value: formatMoney(paymentDue.value.ostie),
    caption: 'Part salarié + part employeur',
  },
  {
    tag: 'IRSA',
    label: 'À payer IRSA',
    value: formatMoney(paymentDue.value.irsa),
    caption: 'Impôt restant à reverser',
  },
  {
    tag: 'Coverage',
    label: 'Taux payé / non payé',
    value: `${formatPercent(paymentSummary.value.pourcentage_paye)} / ${formatPercent(paymentSummary.value.pourcentage_non_paye)}`,
    caption: `${formatInteger(paymentSummary.value.payes)} payé(s), ${formatInteger(paymentSummary.value.non_payes)} restant(s)`,
  },
])

const allMetrics = computed(() => [
  ...metrics.value,
  ...contributionMetrics.value,
  ...paymentMetrics.value,
])

const optionsMatricules = computed(() =>
  [...new Set(details.value.map((item) => item.employe?.matricule).filter(Boolean))],
)

const optionsNoms = computed(() =>
  [...new Set(details.value.map((item) => fullName(item.employe)).filter((value) => value && value !== '—'))],
)

const optionsContrats = computed(() =>
  [...new Set(details.value.map((item) => item.contrat_numero || (item.contrat_id ? `#${item.contrat_id}` : '')).filter(Boolean))],
)

const detailsFiltres = computed(() => {
  const f = detailFilters.value
  const toStr = (value) => String(value || '').toLowerCase()

  return details.value.filter((item) => {
    const contratLabel = item.contrat_numero || (item.contrat_id ? `#${item.contrat_id}` : '')
    return (
      toStr(item.employe?.matricule).includes(toStr(f.matricule)) &&
      toStr(fullName(item.employe)).includes(toStr(f.nom)) &&
      toStr(contratLabel).includes(toStr(f.contrat))
    )
  })
})

const statusClass = (statut) => ({
  'muted-chip': statut === 'non_genere',
  warning: ['en_attente_validation', 'paiement_en_validation'].includes(statut),
  danger: statut === 'non_paye',
  success: statut === 'paye',
})

const sourceClass = (source) => ({
  warning: source === 'prevision' || source === 'mixte',
  success: source === 'reel' || source === 'reel_calcule',
})

const sourceLabel = (source) => {
  if (source === 'reel') return 'Réel validé'
  if (source === 'reel_calcule') return 'Réel calculé'
  if (source === 'mixte') return 'Réel + prévision'
  return 'Prévision présence'
}

const loadCaisses = async () => {
  try {
    const { data } = await api.get('/v1/caisses', { params: { active: 1 } })
    caisses.value = data.caisses || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Erreur chargement caisses'
  }
}

const syncSelectedCaisses = (rows) => {
  const nextSelections = {}

  for (const row of rows) {
    if (!row?.paie_id || row.statut !== 'non_paye') continue
    nextSelections[row.paie_id] = selectedCaisseByPaie.value[row.paie_id] ?? ''
  }

  selectedCaisseByPaie.value = nextSelections
}

const refresh = async () => {
  loading.value = true
  error.value = ''
  syntheseStatus.value = { text: '' }
  clearSynthesePolling()
  details.value = []
  parMois.value = []

  try {
    const params = {
      statut: statusFilter.value,
      ...(mode.value === 'annee' ? { annee: String(year.value) } : {}),
      ...(mode.value === 'periode' ? { debut: periodStart.value, fin: periodEnd.value } : {}),
      ...(mode.value === 'mois' ? {
        mois: selectedMonth.value,
        page: currentPage.value,
        per_page: perPage.value,
        ...(detailFilters.value.matricule ? { matricule: detailFilters.value.matricule } : {}),
        ...(detailFilters.value.nom ? { nom: detailFilters.value.nom } : {}),
        ...(detailFilters.value.contrat ? { contrat: detailFilters.value.contrat } : {}),
      } : {}),
    }

    const { data } = await getCachedApi('/v1/paies/etat', { params, timeout: 60000 })
    totaux.value = data.totaux || {}
    statusCounts.value = data.status_counts || {}
    cotisations.value = data.cotisations || {}
    paymentSummary.value = data.payment_summary || {}
    paymentDue.value = data.payment_due || {}
    syntheseStatus.value = paieSyntheseStatus(data.synthese)
    if (data.synthese?.status === 'generating') {
      scheduleSynthesePolling()
    }
    parMois.value = data.par_mois || []
    details.value = data.details || []
    detailsPagination.value = data.details_pagination || { current_page: 1, per_page: perPage.value, total: details.value.length, last_page: 1 }
    currentPage.value = detailsPagination.value.current_page || 1
    syncSelectedCaisses(details.value)
    if (mode.value === 'mois') {
      prefetchNextPage('/v1/paies/etat', params, detailsPagination.value, { timeout: 60000 })
    }
  } catch (e) {
    error.value = e.code === 'ECONNABORTED'
      ? 'Le calcul de l’état de paie prend plus de temps que prévu avec le grand jeu de données. Réessaie après le rafraîchissement des synthèses.'
      : e.response?.data?.message || e.message || 'Erreur chargement état de paie'
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

const resetAndRefresh = () => {
  currentPage.value = 1
  refresh()
}

const previousPage = () => {
  if (loading.value || currentPage.value <= 1) return
  currentPage.value -= 1
  refresh()
}

const nextPage = () => {
  if (loading.value || currentPage.value >= detailsPagination.value.last_page) return
  currentPage.value += 1
  refresh()
}

const generate = async (row) => {
  await runAction(() => api.post('/v1/paies/generer', { employe_id: row.employe_id, mois: selectedMonth.value }))
}

const validate = async (row) => {
  if (!row.paie_id) return
  await runAction(() => api.post(`/v1/paies/${row.paie_id}/valider`))
}

const cancelGeneration = async (row) => {
  if (!row.paie_id) return
  await runAction(() => api.post(`/v1/paies/${row.paie_id}/annuler`))
}

const pay = async (row) => {
  if (!row.paie_id) return
  const caisseId = selectedCaisseByPaie.value[row.paie_id]
  if (!caisseId) {
    error.value = 'Choisis une caisse avant de demander le paiement'
    return
  }
  await runAction(() => api.post(`/v1/paies/${row.paie_id}/payer`, { caisse_id: caisseId }))
}

const downloadPdf = async (row) => {
  if (!row.paie_id) return
  loading.value = true
  error.value = ''
  try {
    const { data, headers } = await api.get(`/v1/paies/${row.paie_id}/pdf`, { responseType: 'blob' })
    const blob = new Blob([data], { type: headers['content-type'] || 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `bulletin_paie_${row.employe_id}_${selectedMonth.value}.pdf`)
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Erreur téléchargement PDF'
  } finally {
    loading.value = false
  }
}

const downloadReceipt = async (row) => {
  if (!row.paie_id) return
  loading.value = true
  error.value = ''
  try {
    const { data, headers } = await api.get(`/v1/paies/${row.paie_id}/recu-paiement`, { responseType: 'blob' })
    const blob = new Blob([data], { type: headers['content-type'] || 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `recu_paiement_${row.employe_id}_${selectedMonth.value}.pdf`)
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Erreur téléchargement reçu'
  } finally {
    loading.value = false
  }
}

const runAction = async (request) => {
  loading.value = true
  error.value = ''
  try {
    await request()
    await refresh()
  } catch (e) {
    error.value = e.response?.data?.message || e.message || "Action impossible"
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  refresh()
  loadCaisses()
})

watch(detailFilters, () => {
  if (mode.value !== 'mois') return
  currentPage.value = 1
  refresh()
}, { deep: true })

onUnmounted(clearSynthesePolling)
</script>

<style scoped>
.paie-etat-page .content-grid {
  grid-template-columns: 1fr;
}

.paie-etat-page .table-card,
.paie-etat-page .table-shell {
  width: 100%;
  min-width: 0;
}

.accent {
  color: var(--brand-600);
  font-weight: 800;
}

.status-col {
  min-width: 190px;
}

.status-chip-scroll {
  max-width: 100%;
  overflow-x: auto;
  overflow-y: hidden;
  padding-bottom: 4px;
  scrollbar-width: thin;
  scrollbar-color: rgba(79, 70, 229, 0.28) transparent;
}

.status-chip-scroll::-webkit-scrollbar {
  height: 6px;
}

.status-chip-scroll::-webkit-scrollbar-thumb {
  background: rgba(79, 70, 229, 0.28);
  border-radius: 999px;
}

.status-chip {
  display: inline-flex;
  align-items: center;
  flex-wrap: nowrap;
  min-width: max-content;
  white-space: nowrap;
}

.source-chip {
  white-space: nowrap;
}

.actions {
  min-width: 260px;
  white-space: nowrap;
}

.actions-stack {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.pay-action {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 14px;
}

.select-xs {
  min-height: 32px;
  min-width: 140px;
  padding: 6px 10px;
  border-radius: 10px;
  font-size: 0.78rem;
}

.muted-chip {
  background: rgba(148, 163, 184, 0.12);
  color: var(--muted);
}

.chip.warning {
  background: var(--warning-100);
  color: var(--warning-500);
}

.chip.danger {
  background: var(--danger-100);
  color: var(--danger-500);
}

.chip.success {
  background: var(--success-100);
  color: var(--success-500);
}

</style>
