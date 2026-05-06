<template>
  <div class="rh-page caisse-history-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Cash ledger</p>
        <h1>Historique complet des caisses</h1>
        <p class="hero-subtitle">
          Consulte l’ensemble des mouvements avec filtres par mois, année, caisse et statut.
        </p>

        <div class="hero-pills">
          <span class="pill">{{ formatInteger(pagination.total || mouvements.length) }} mouvement(s)</span>
          <span class="pill">Historique global</span>
          <span class="pill">Filtres par période</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn btn-secondary" to="/caisses">Retour état caisse</RouterLink>
            <RouterLink class="btn" to="/caisses/mouvements/nouveau">Nouveau mouvement</RouterLink>
          </div>

          <div class="action-row">
            <label class="field-card">
              <span class="field-label">Année</span>
              <select class="select" v-model="filters.annee" @change="onFilterChange">
                <option value="">Toutes</option>
                <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
              </select>
            </label>

            <label class="field-card">
              <span class="field-label">Mois</span>
              <input class="input" v-model="filters.mois" type="month" @change="onMonthChange" />
            </label>
          </div>

          <div class="action-row">
            <label class="field-card">
              <span class="field-label">Caisse</span>
              <select class="select" v-model="filters.caisse_id" @change="onFilterChange">
                <option value="">Toutes</option>
                <option v-for="caisse in caisses" :key="caisse.id" :value="caisse.id">{{ caisse.nom }}</option>
              </select>
            </label>

            <label class="field-card">
              <span class="field-label">Type</span>
              <select class="select" v-model="filters.type" @change="onFilterChange">
                <option value="">Tous</option>
                <option value="entree">Entrée</option>
                <option value="sortie">Sortie</option>
              </select>
            </label>

            <label class="field-card">
              <span class="field-label">Statut</span>
              <select class="select" v-model="filters.statut" @change="onFilterChange">
                <option value="tous">Tous</option>
                <option value="valide">Validé</option>
                <option value="en_attente_validation">En attente</option>
                <option value="rejete">Rejeté</option>
              </select>
            </label>
          </div>

          <div class="action-row">
            <label class="field-card">
              <span class="field-label">Catégorie</span>
              <select class="select" v-model="filters.categorie" @change="onFilterChange">
                <option value="">Toutes</option>
                <option v-for="category in filterCategories" :key="category.code" :value="category.code">{{ category.label }}</option>
              </select>
            </label>

            <label class="field-card">
              <span class="field-label">Lignes</span>
              <select class="select" v-model="pagination.perPage" @change="onFilterChange">
                <option :value="20">20</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
            </label>
          </div>

          <div v-if="error" class="status-banner danger">
            <span class="status-dot"></span>
            <span>{{ error }}</span>
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

    <section class="card section-card table-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Ledger</p>
          <h2>Mouvements filtrés</h2>
        </div>
        <span class="section-chip">{{ formatInteger(pagination.total) }} lignes</span>
      </div>

      <div class="table-shell">
        <table class="table">
          <thead>
            <tr>
              <th>Date demande</th>
              <th>Caisse</th>
              <th>Type</th>
              <th>Catégorie</th>
              <th>Source</th>
              <th>Employé / Paie</th>
              <th>Montant</th>
              <th>Validation</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="mouvement in mouvements" :key="mouvement.id">
              <td>{{ formatDateTime(mouvement.demande_validation_le || mouvement.created_at) }}</td>
              <td>{{ mouvement.caisse?.nom || '—' }}</td>
              <td>
                <span class="chip" :class="mouvement.type === 'entree' ? 'success' : 'danger'">
                  {{ mouvement.type === 'entree' ? 'Entrée' : 'Sortie' }}
                </span>
              </td>
              <td>{{ categoryLabel(mouvement.type, mouvement.categorie) }}</td>
              <td class="cell-stack">
                <div>{{ mouvement.source }}</div>
                <div class="muted">{{ mouvement.description || '—' }}</div>
              </td>
              <td>{{ employeeName(mouvement) }}</td>
              <td class="accent">{{ formatMoney(mouvement.montant) }}</td>
              <td>{{ formatDateTime(mouvement.valide_le) || '—' }}</td>
              <td><span class="chip" :class="statusClass(mouvement.statut)">{{ statusLabel(mouvement.statut) }}</span></td>
            </tr>
            <tr v-if="!mouvements.length">
              <td colspan="9" class="muted">Aucun mouvement ne correspond aux filtres.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="table-footer">
        <span>
          Page <strong>{{ pagination.page }}</strong> sur <strong>{{ pagination.lastPage }}</strong>
          · {{ formatInteger(pagination.total) }} lignes
        </span>
        <div class="action-row">
          <button class="btn btn-secondary btn-sm" :disabled="loading || pagination.page <= 1" @click="prevPage">Précédent</button>
          <button class="btn btn-secondary btn-sm" :disabled="loading || pagination.page >= pagination.lastPage" @click="nextPage">Suivant</button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import { formatDateTimeValue, formatMoneyAmount } from '../utils/formatters'

const loading = ref(false)
const error = ref('')
const caisses = ref([])
const mouvements = ref([])
const categories = ref({ entree: [], sortie: [] })
const metricsData = ref({
  solde_total: 0,
  entrees_validees: 0,
  entrees_count: 0,
  sorties_validees: 0,
  sorties_count: 0,
  attente_count: 0,
  mouvements_count: 0,
})
const pagination = ref({
  page: 1,
  lastPage: 1,
  total: 0,
  perPage: 20,
})
const filters = ref({
  annee: '',
  mois: '',
  caisse_id: '',
  type: '',
  categorie: '',
  statut: 'tous',
})

const yearOptions = ['2026', '2025', '2024']

const formatMoney = (amount) => formatMoneyAmount(amount)
const formatInteger = (value) => new Intl.NumberFormat('fr-FR').format(Number(value || 0))
const formatDateTime = (value) => formatDateTimeValue(value)

const metrics = computed(() => [
  { tag: 'Balance', label: 'Solde total', value: formatMoney(metricsData.value.solde_total), caption: `${caisses.value.length} caisse(s)` },
  { tag: 'In', label: 'Entrées validées', value: formatMoney(metricsData.value.entrees_validees), caption: `${metricsData.value.entrees_count} mouvement(s)` },
  { tag: 'Out', label: 'Sorties validées', value: formatMoney(metricsData.value.sorties_validees), caption: `${metricsData.value.sorties_count} mouvement(s)` },
  { tag: 'Pending', label: 'À valider', value: String(metricsData.value.attente_count), caption: `${metricsData.value.mouvements_count} mouvement(s) filtrés` },
])

const filterCategories = computed(() => {
  if (filters.value.type) return categories.value[filters.value.type] || []
  const unique = new Map()
  for (const item of [...(categories.value.entree || []), ...(categories.value.sortie || [])]) {
    if (!unique.has(item.code)) unique.set(item.code, item)
  }
  return Array.from(unique.values())
})

const employeeName = (mouvement) => {
  const employe = mouvement.paie?.employe
  if (!employe) return '—'
  return `${employe.nom || ''} ${employe.prenom || ''}`.trim() || employe.matricule || '—'
}

const categoryLabel = (type, code) => {
  const items = categories.value[type] || []
  return items.find((item) => item.code === code)?.label || code || '—'
}

const statusLabel = (statut) => ({
  en_attente_validation: 'En attente',
  valide: 'Validé',
  rejete: 'Rejeté',
}[statut] || statut)

const statusClass = (statut) => ({
  warning: statut === 'en_attente_validation',
  success: statut === 'valide',
  danger: statut === 'rejete',
})

const buildParams = () => {
  const params = {
    page: pagination.value.page,
    per_page: pagination.value.perPage,
  }

  for (const [key, value] of Object.entries(filters.value)) {
    if (value) params[key] = value
  }

  return params
}

const fetchData = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/v1/caisses/historique', { params: buildParams() })
    caisses.value = data.caisses || []
    mouvements.value = data.mouvements?.data || []
    categories.value = data.categories || { entree: [], sortie: [] }
    metricsData.value = data.metrics || metricsData.value
    pagination.value = {
      ...pagination.value,
      page: data.mouvements?.current_page || 1,
      lastPage: data.mouvements?.last_page || 1,
      total: data.mouvements?.total || mouvements.value.length,
    }
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Erreur chargement historique caisse'
  } finally {
    loading.value = false
  }
}

const onFilterChange = () => {
  pagination.value.page = 1
  fetchData()
}

const onMonthChange = () => {
  if (filters.value.mois) {
    filters.value.annee = String(filters.value.mois).slice(0, 4)
  }
  onFilterChange()
}

const prevPage = () => {
  if (loading.value || pagination.value.page <= 1) return
  pagination.value.page -= 1
  fetchData()
}

const nextPage = () => {
  if (loading.value || pagination.value.page >= pagination.value.lastPage) return
  pagination.value.page += 1
  fetchData()
}

watch(() => filters.value.type, (type) => {
  const options = type ? (categories.value[type] || []) : filterCategories.value
  if (!options.some((item) => item.code === filters.value.categorie)) {
    filters.value.categorie = ''
  }
})

onMounted(fetchData)
</script>

<style scoped>
.accent {
  color: var(--brand-600);
  font-weight: 800;
}

.chip.success {
  background: var(--success-100);
  color: var(--success-500);
}

.chip.warning {
  background: var(--warning-100);
  color: var(--warning-500);
}

.chip.danger {
  background: var(--danger-100);
  color: var(--danger-500);
}

.table-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-top: 18px;
  flex-wrap: wrap;
}
</style>
