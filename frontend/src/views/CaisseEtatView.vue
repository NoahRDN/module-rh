<template>
  <div class="rh-page caisse-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Cash management</p>
        <h1>État de caisse</h1>
        <p class="hero-subtitle">
          Suivi des soldes, des entrées, des sorties et des mouvements validés ou en attente.
        </p>

        <div class="hero-pills">
          <span class="pill">Historique</span>
          <span class="pill">Validation</span>
          <span class="pill">Paie</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn" to="/caisses/mouvements/nouveau">
              <AppIcon name="plus" :size="18" />
              <span>Nouveau mouvement</span>
            </RouterLink>
            <RouterLink class="btn btn-secondary" to="/caisses/validations">
              Validations
            </RouterLink>
            <RouterLink class="btn btn-secondary" to="/caisses/types">
              Types caisse
            </RouterLink>
          </div>

          <div class="action-row">
            <label class="field-card">
              <span class="field-label">Caisse</span>
              <select class="select" v-model="filters.caisse_id" @change="fetchData">
                <option value="">Toutes</option>
                <option v-for="caisse in caisses" :key="caisse.id" :value="caisse.id">{{ caisse.nom }}</option>
              </select>
            </label>
            <label class="field-card">
              <span class="field-label">Type</span>
              <select class="select" v-model="filters.type" @change="fetchData">
                <option value="">Tous</option>
                <option value="entree">Entrée</option>
                <option value="sortie">Sortie</option>
              </select>
            </label>
            <label class="field-card">
              <span class="field-label">Catégorie</span>
              <select class="select" v-model="filters.categorie" @change="fetchData">
                <option value="">Toutes</option>
                <option v-for="category in filterCategories" :key="category.code" :value="category.code">{{ category.label }}</option>
              </select>
            </label>
          </div>

          <label class="field-card">
            <span class="field-label">Statut</span>
            <select class="select" v-model="filters.statut" @change="fetchData">
              <option value="tous">Tous</option>
              <option value="en_attente_validation">En attente de validation</option>
              <option value="valide">Validé</option>
              <option value="rejete">Rejeté</option>
            </select>
          </label>

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

    <section class="content-grid">
      <article class="card section-card table-card">
        <div class="section-heading">
          <div>
            <p class="section-kicker">Cashboxes</p>
            <h2>Caisses de l’entreprise</h2>
          </div>
          <button class="btn btn-secondary btn-xs" type="button" :disabled="loading" @click="fetchData">
            Actualiser
          </button>
        </div>

        <div class="table-shell">
          <table class="table">
            <thead>
              <tr>
                <th>Caisse</th>
                <th>Description</th>
                <th>Solde</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="caisse in caisses" :key="caisse.id">
                <td class="type-name">{{ caisse.nom }}</td>
                <td class="muted">{{ caisse.description || '—' }}</td>
                <td class="accent">{{ formatMoney(caisse.solde) }}</td>
                <td><span class="chip" :class="caisse.active ? 'success' : 'muted-chip'">{{ caisse.active ? 'Active' : 'Inactive' }}</span></td>
              </tr>
              <tr v-if="!caisses.length">
                <td colspan="4" class="muted">Aucune caisse disponible.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="card section-card table-card">
        <div class="section-heading">
          <div>
            <p class="section-kicker">Ledger</p>
            <h2>Historique des mouvements</h2>
          </div>
          <span class="section-chip">{{ mouvements.length }} mouvement(s)</span>
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
                <th>Paie</th>
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
      </article>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'
import { formatMoneyAmount } from '../utils/formatters'

const loading = ref(false)
const error = ref('')
const caisses = ref([])
const mouvements = ref([])
const categories = ref({ entree: [], sortie: [] })
const filters = ref({
  caisse_id: '',
  type: '',
  categorie: '',
  statut: 'tous',
})

const formatMoney = (amount) => formatMoneyAmount(amount)

const formatDateTime = (value) => {
  if (!value) return ''
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value))
}

const metrics = computed(() => {
  const soldeTotal = caisses.value.reduce((sum, caisse) => sum + Number(caisse.solde || 0), 0)
  const entrees = mouvements.value.filter((mouvement) => mouvement.type === 'entree' && mouvement.statut === 'valide')
  const sorties = mouvements.value.filter((mouvement) => mouvement.type === 'sortie' && mouvement.statut === 'valide')
  const attente = mouvements.value.filter((mouvement) => mouvement.statut === 'en_attente_validation')

  return [
    { tag: 'Balance', label: 'Solde total', value: formatMoney(soldeTotal), caption: `${caisses.value.length} caisse(s)` },
    { tag: 'In', label: 'Entrées validées', value: formatMoney(entrees.reduce((sum, item) => sum + Number(item.montant || 0), 0)), caption: `${entrees.length} mouvement(s)` },
    { tag: 'Out', label: 'Sorties validées', value: formatMoney(sorties.reduce((sum, item) => sum + Number(item.montant || 0), 0)), caption: `${sorties.length} mouvement(s)` },
    { tag: 'Pending', label: 'À valider', value: String(attente.length), caption: 'Mouvements non appliqués au solde' },
  ]
})

const filterCategories = computed(() => {
  if (filters.value.type) return categories.value[filters.value.type] || []
  const unique = new Map()
  for (const item of [...(categories.value.entree || []), ...(categories.value.sortie || [])]) {
    if (!unique.has(item.code)) {
      unique.set(item.code, item)
    }
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

const fetchData = async () => {
  loading.value = true
  error.value = ''
  try {
    const params = Object.fromEntries(Object.entries(filters.value).filter(([, value]) => value))
    const { data } = await api.get('/v1/caisses', { params })
    caisses.value = data.caisses || []
    mouvements.value = data.mouvements?.data || data.mouvements || []
    categories.value = data.categories || { entree: [], sortie: [] }
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Erreur chargement caisse'
  } finally {
    loading.value = false
  }
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
.caisse-page .content-grid {
  grid-template-columns: 1fr;
}

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

.muted-chip {
  background: rgba(148, 163, 184, 0.12);
  color: var(--muted);
}
</style>
