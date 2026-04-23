<template>
  <div class="rh-page caisse-types-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Cashbox settings</p>
        <h1>Types de caisse</h1>
        <p class="hero-subtitle">
          Liste des caisses utilisables par l’entreprise. Une caisse désactivée ne peut plus être choisie pour un nouveau paiement ou mouvement.
        </p>

        <div class="hero-pills">
          <span class="pill">{{ activeCount }} active(s)</span>
          <span class="pill">{{ inactiveCount }} inactive(s)</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn btn-secondary" to="/caisses">État caisse</RouterLink>
            <RouterLink class="btn btn-secondary" to="/caisses/mouvements/nouveau">Nouveau mouvement</RouterLink>
          </div>

          <label class="field-card">
            <span class="field-label">Filtrer</span>
            <select class="select" v-model="statusFilter">
              <option value="tous">Tous</option>
              <option value="active">Actives</option>
              <option value="inactive">Inactives</option>
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

    <section class="card section-card table-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Configuration</p>
          <h2>Liste des types de caisse</h2>
        </div>
        <button class="btn btn-secondary btn-xs" type="button" :disabled="loading" @click="fetchTypes">
          Actualiser
        </button>
      </div>

      <div class="table-shell">
        <table class="table">
          <thead>
            <tr>
              <th>Type de caisse</th>
              <th>Description</th>
              <th>Solde actuel</th>
              <th>Statut</th>
              <th>Activation</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="caisse in filteredCaisses" :key="caisse.id">
              <td class="cell-stack">
                <div class="type-name">{{ caisse.nom }}</div>
                <div class="muted">Référence #{{ caisse.id }}</div>
              </td>
              <td class="muted">{{ caisse.description || '—' }}</td>
              <td class="accent">{{ formatMoney(caisse.solde) }}</td>
              <td>
                <span class="chip" :class="caisse.active ? 'success' : 'muted-chip'">
                  {{ caisse.active ? 'Actif' : 'Inactif' }}
                </span>
              </td>
              <td>
                <button
                  class="toggle"
                  type="button"
                  :class="{ active: caisse.active }"
                  :aria-pressed="caisse.active"
                  :disabled="loading"
                  @click="toggleActive(caisse)"
                >
                  <span class="toggle-knob"></span>
                  <span class="toggle-label">{{ caisse.active ? 'Désactiver' : 'Activer' }}</span>
                </button>
              </td>
            </tr>
            <tr v-if="!filteredCaisses.length">
              <td colspan="5" class="muted">Aucun type de caisse ne correspond au filtre.</td>
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

const loading = ref(false)
const error = ref('')
const caisses = ref([])
const statusFilter = ref('tous')

const formatMoney = (amount) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MGA', maximumFractionDigits: 0 }).format(Number(amount || 0))

const activeCount = computed(() => caisses.value.filter((caisse) => caisse.active).length)
const inactiveCount = computed(() => caisses.value.filter((caisse) => !caisse.active).length)

const filteredCaisses = computed(() => {
  if (statusFilter.value === 'active') {
    return caisses.value.filter((caisse) => caisse.active)
  }

  if (statusFilter.value === 'inactive') {
    return caisses.value.filter((caisse) => !caisse.active)
  }

  return caisses.value
})

const metrics = computed(() => [
  { tag: 'Types', label: 'Types enregistrés', value: String(caisses.value.length), caption: 'Caisses configurées' },
  { tag: 'Active', label: 'Actives', value: String(activeCount.value), caption: 'Disponibles au paiement' },
  { tag: 'Inactive', label: 'Inactives', value: String(inactiveCount.value), caption: 'Masquées des nouveaux usages' },
  {
    tag: 'Balance',
    label: 'Solde total',
    value: formatMoney(caisses.value.reduce((sum, caisse) => sum + Number(caisse.solde || 0), 0)),
    caption: 'Toutes caisses confondues',
  },
])

const fetchTypes = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/v1/caisses/types')
    caisses.value = data.caisses || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Erreur chargement types de caisse'
  } finally {
    loading.value = false
  }
}

const toggleActive = async (caisse) => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.patch(`/v1/caisses/${caisse.id}/toggle-active`)
    const index = caisses.value.findIndex((item) => item.id === caisse.id)
    if (index !== -1) {
      caisses.value[index] = data.caisse
    }
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Impossible de modifier le statut'
  } finally {
    loading.value = false
  }
}

onMounted(fetchTypes)
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

.muted-chip {
  background: rgba(148, 163, 184, 0.12);
  color: var(--muted);
}

.toggle {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  min-width: 126px;
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 5px 10px 5px 5px;
  background: var(--panel-soft);
  color: var(--muted);
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s ease;
}

.toggle.active {
  border-color: rgba(34, 197, 94, 0.22);
  background: rgba(34, 197, 94, 0.1);
  color: var(--success-500);
}

.toggle:disabled {
  cursor: wait;
  opacity: 0.7;
}

.toggle-knob {
  width: 28px;
  height: 28px;
  border-radius: 999px;
  background: #94a3b8;
  box-shadow: 0 8px 16px rgba(15, 23, 42, 0.18);
}

.toggle.active .toggle-knob {
  background: var(--success-500);
}

.toggle-label {
  font-size: 0.78rem;
}
</style>
