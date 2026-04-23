<template>
  <div class="rh-page caisse-validation-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Cash approval</p>
        <h1>Validation des mouvements de caisse</h1>
        <p class="hero-subtitle">
          Valide ou rejette les entrées/sorties avant impact réel sur la caisse et sur le paiement des fiches.
        </p>

        <div class="hero-pills">
          <span class="pill">{{ mouvements.length }} en attente</span>
          <span class="pill">Contrôle caisse</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn btn-secondary" to="/caisses">État caisse</RouterLink>
            <RouterLink class="btn" to="/caisses/mouvements/nouveau">
              <AppIcon name="plus" :size="18" />
              <span>Nouveau</span>
            </RouterLink>
          </div>
          <button class="btn btn-secondary" type="button" :disabled="loading" @click="fetchData">
            Actualiser
          </button>
          <div v-if="error" class="status-banner danger">
            <span class="status-dot"></span>
            <span>{{ error }}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="card section-card table-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Pending</p>
          <h2>Mouvements en attente</h2>
        </div>
        <span class="section-chip">Non appliqués au solde</span>
      </div>

      <div class="table-shell">
        <table class="table">
          <thead>
            <tr>
              <th>Demande</th>
              <th>Caisse</th>
              <th>Type</th>
              <th>Source</th>
              <th>Employé / Paie</th>
              <th>Montant</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="mouvement in mouvements" :key="mouvement.id">
              <td>{{ formatDateTime(mouvement.demande_validation_le || mouvement.created_at) }}</td>
              <td class="cell-stack">
                <div>{{ mouvement.caisse?.nom || '—' }}</div>
                <div class="muted">Solde: {{ formatMoney(mouvement.caisse?.solde) }}</div>
              </td>
              <td>
                <span class="chip" :class="mouvement.type === 'entree' ? 'success' : 'danger'">
                  {{ mouvement.type === 'entree' ? 'Entrée' : 'Sortie' }}
                </span>
              </td>
              <td class="cell-stack">
                <div>{{ mouvement.source }}</div>
                <div class="muted">{{ mouvement.description || '—' }}</div>
              </td>
              <td>{{ employeeName(mouvement) }}</td>
              <td class="accent">{{ formatMoney(mouvement.montant) }}</td>
              <td class="actions">
                <button class="btn btn-secondary btn-xs" type="button" :disabled="loading" @click="validateMovement(mouvement)">
                  Valider
                </button>
                <button class="btn btn-secondary btn-xs" type="button" :disabled="loading" @click="rejectMovement(mouvement)">
                  Rejeter
                </button>
              </td>
            </tr>
            <tr v-if="!mouvements.length">
              <td colspan="7" class="muted">Aucun mouvement en attente de validation.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'
import { formatMoneyAmount } from '../utils/formatters'

const loading = ref(false)
const error = ref('')
const mouvements = ref([])

const formatMoney = (amount) => formatMoneyAmount(amount)

const formatDateTime = (value) => {
  if (!value) return ''
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value))
}

const employeeName = (mouvement) => {
  const employe = mouvement.paie?.employe
  if (!employe) return '—'
  return `${employe.nom || ''} ${employe.prenom || ''}`.trim() || employe.matricule || '—'
}

const fetchData = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/v1/caisses/en-attente-validation')
    mouvements.value = data.mouvements || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Erreur chargement validations'
  } finally {
    loading.value = false
  }
}

const validateMovement = async (mouvement) => {
  await runAction(() => api.post(`/v1/caisses/mouvements/${mouvement.id}/valider`))
}

const rejectMovement = async (mouvement) => {
  await runAction(() => api.post(`/v1/caisses/mouvements/${mouvement.id}/rejeter`))
}

const runAction = async (request) => {
  loading.value = true
  error.value = ''
  try {
    await request()
    await fetchData()
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Action impossible'
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>

<style scoped>
.accent {
  color: var(--brand-600);
  font-weight: 800;
}

.actions {
  display: flex;
  gap: 8px;
  white-space: nowrap;
}

.chip.success {
  background: var(--success-100);
  color: var(--success-500);
}

.chip.danger {
  background: var(--danger-100);
  color: var(--danger-500);
}
</style>
