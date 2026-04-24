<template>
  <div class="rh-page remuneration-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Payroll setup</p>
        <h1>Indemnités et primes</h1>
        <p class="hero-subtitle">
          Paramétrez les éléments fixes, ciblés et conditionnels pris en compte dans la paie.
        </p>

        <div class="hero-pills">
          <span class="pill">Global, poste, employé, contrat</span>
          <span class="pill">Récurrent ou ponctuel</span>
          <span class="pill">Condition d’ancienneté</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" @click="loadItems" :disabled="loading">
              {{ loading ? 'Chargement...' : 'Actualiser' }}
            </button>
            <RouterLink class="btn" to="/remuneration-items/nouveau">Nouvel élément</RouterLink>
          </div>

          <div v-if="message" class="status-banner" :class="messageType">
            <span class="status-dot"></span>
            <span>{{ message }}</span>
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

    <section class="card section-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Catalog</p>
          <h2>Liste des éléments</h2>
        </div>
      </div>

      <div class="toolbar filters-row">
        <label class="field-card">
          <span class="field-label">Nature</span>
          <select class="select" v-model="filters.nature">
            <option value="">Toutes</option>
            <option value="prime">Prime</option>
            <option value="indemnite">Indemnité</option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Portée</span>
          <select class="select" v-model="filters.scope_type">
            <option value="">Toutes</option>
            <option value="global">Global</option>
            <option value="poste">Poste</option>
            <option value="employe">Employé</option>
            <option value="contrat">Contrat</option>
          </select>
        </label>

        <label class="field-card checkbox-field">
          <span class="field-label">Actifs seulement</span>
          <input v-model="filters.actif" type="checkbox" />
        </label>
      </div>

      <div class="table-shell">
        <table class="table">
          <thead>
            <tr>
              <th>Libellé</th>
              <th>Nature</th>
              <th>Portée</th>
              <th>Récurrence</th>
              <th>Condition</th>
              <th>Fiscalité</th>
              <th>Montant</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in filteredItems" :key="item.id">
              <td class="cell-stack">
                <div class="type-name">{{ item.libelle }}</div>
                <div class="muted">{{ scopeTargetLabel(item) }}</div>
              </td>
              <td>{{ natureLabel(item.nature) }}</td>
              <td>{{ scopeLabel(item.scope_type) }}</td>
              <td>{{ recurrenceLabel(item) }}</td>
              <td>{{ conditionLabel(item) }}</td>
              <td>{{ item.is_taxable ? 'Imposable' : 'Non imposable' }}</td>
              <td>{{ formatMoney(item.montant) }}</td>
              <td>
                <span class="chip" :class="item.actif ? 'success-chip' : 'muted-chip'">
                  {{ item.actif ? 'Actif' : 'Inactif' }}
                </span>
              </td>
              <td class="actions">
                <div class="actions-stack">
                  <RouterLink class="btn btn-secondary btn-xs" :to="`/remuneration-items/${item.id}/modifier`">
                    Modifier
                  </RouterLink>
                  <button class="btn btn-secondary btn-xs btn-danger-soft" type="button" :disabled="deletingId === item.id" @click="remove(item)">
                    {{ deletingId === item.id ? '...' : 'Supprimer' }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!filteredItems.length">
              <td colspan="9" class="empty-state">
                <p>Aucun élément configuré.</p>
                <span>Créez une prime ou une indemnité pour l’intégrer dans la paie.</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import { formatMoneyAmount } from '../utils/formatters'

const loading = ref(false)
const deletingId = ref(null)
const message = ref('')
const messageType = ref('info')
const items = ref([])
const filters = ref({
  nature: '',
  scope_type: '',
  actif: false,
})

const formatInteger = (value) => new Intl.NumberFormat('fr-FR').format(Number(value || 0))
const formatMoney = (value) => formatMoneyAmount(value)

const natureLabel = (value) => value === 'indemnite' ? 'Indemnité' : 'Prime'
const scopeLabel = (value) => ({
  global: 'Global',
  poste: 'Poste',
  employe: 'Employé',
  contrat: 'Contrat',
}[value] || '—')

const recurrenceLabel = (item) => item.recurrence_type === 'ponctuel'
  ? `Ponctuel${item.mois_application ? ` (${item.mois_application})` : ''}`
  : 'Récurrent'

const conditionLabel = (item) => {
  if (!item.condition_type) return 'Aucune'
  return `Ancienneté ${item.condition_operator} ${item.condition_value}`
}

const scopeTargetLabel = (item) => {
  if (item.scope_type === 'poste') return item.poste?.nom || 'Poste non trouvé'
  if (item.scope_type === 'employe') return `${item.employe?.matricule || '—'} ${item.employe?.nom || ''} ${item.employe?.prenom || ''}`.trim()
  if (item.scope_type === 'contrat') return item.contrat?.numero || 'Contrat non trouvé'
  return 'Application globale'
}

const filteredItems = computed(() => items.value.filter((item) => {
  if (filters.value.nature && item.nature !== filters.value.nature) return false
  if (filters.value.scope_type && item.scope_type !== filters.value.scope_type) return false
  if (filters.value.actif && !item.actif) return false
  return true
}))

const metrics = computed(() => [
  {
    tag: 'Items',
    label: 'Éléments',
    value: formatInteger(items.value.length),
    caption: 'Total des primes et indemnités paramétrées.',
  },
  {
    tag: 'Active',
    label: 'Actifs',
    value: formatInteger(items.value.filter((item) => item.actif).length),
    caption: 'Appliqués dans les prochains calculs de paie.',
  },
  {
    tag: 'Prime',
    label: 'Primes',
    value: formatInteger(items.value.filter((item) => item.nature === 'prime').length),
    caption: 'Éléments marqués comme primes.',
  },
  {
    tag: 'Indem.',
    label: 'Indemnités',
    value: formatInteger(items.value.filter((item) => item.nature === 'indemnite').length),
    caption: 'Éléments marqués comme indemnités.',
  },
])

const loadItems = async () => {
  loading.value = true
  message.value = ''
  try {
    const { data } = await api.get('/v1/remuneration-items')
    items.value = data || []
  } catch (e) {
    message.value = e.response?.data?.message || 'Impossible de charger les éléments de rémunération.'
    messageType.value = 'danger'
  } finally {
    loading.value = false
  }
}

const remove = async (item) => {
  if (deletingId.value === item.id) return
  if (!window.confirm(`Supprimer "${item.libelle}" ?`)) return

  deletingId.value = item.id
  message.value = ''
  try {
    await api.delete(`/v1/remuneration-items/${item.id}`)
    items.value = items.value.filter((entry) => entry.id !== item.id)
    message.value = 'Élément supprimé.'
    messageType.value = 'success'
  } catch (e) {
    message.value = e.response?.data?.message || 'Impossible de supprimer cet élément.'
    messageType.value = 'danger'
  } finally {
    deletingId.value = null
  }
}

onMounted(loadItems)
</script>

<style scoped>
.filters-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.checkbox-field {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.cell-stack,
.actions-stack {
  display: grid;
  gap: 6px;
}

.success-chip {
  background: rgba(18, 183, 106, 0.12);
  color: var(--success-500);
}

@media (max-width: 900px) {
  .filters-row {
    grid-template-columns: 1fr;
  }
}
</style>
