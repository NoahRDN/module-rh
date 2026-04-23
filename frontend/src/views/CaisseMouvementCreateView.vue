<template>
  <div class="rh-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Cash request</p>
        <h1>Nouveau mouvement de caisse</h1>
        <p class="hero-subtitle">
          Enregistre une entrée ou une sortie avec sa source. Le solde ne change qu’après validation.
        </p>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <RouterLink class="btn btn-secondary" to="/caisses">Retour état caisse</RouterLink>
          <RouterLink class="btn btn-secondary" to="/caisses/validations">Validations caisse</RouterLink>
        </div>
      </div>
    </section>

    <section class="card section-card form-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Request</p>
          <h2>Informations du mouvement</h2>
        </div>
        <span class="section-chip">Soumis à validation</span>
      </div>

      <form class="form-grid" @submit.prevent="submit">
        <label class="field-card">
          <span class="field-label">Caisse</span>
          <select class="select" v-model="form.caisse_id" required>
            <option value="">Choisir une caisse</option>
            <option v-for="caisse in caisses" :key="caisse.id" :value="caisse.id">
              {{ caisse.nom }} — {{ formatMoney(caisse.solde) }}
            </option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Type</span>
          <select class="select" v-model="form.type" required>
            <option value="entree">Entrée d’argent</option>
            <option value="sortie">Sortie d’argent</option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Montant</span>
          <input class="input" type="number" min="0.01" step="0.01" v-model.number="form.montant" required />
        </label>

        <label class="field-card">
          <span class="field-label">Source</span>
          <input class="input" type="text" v-model="form.source" placeholder="Ex: Apport capital, règlement client, achat fournitures" required />
        </label>

        <label class="field-card full">
          <span class="field-label">Description</span>
          <textarea class="input textarea" rows="4" v-model="form.description" placeholder="Précision utile pour la validation"></textarea>
        </label>

        <div v-if="error" class="status-banner danger full">
          <span class="status-dot"></span>
          <span>{{ error }}</span>
        </div>
        <div v-if="success" class="status-banner success full">
          <span class="status-dot"></span>
          <span>{{ success }}</span>
        </div>

        <div class="form-actions full">
          <button class="btn" type="submit" :disabled="loading">
            <AppIcon name="save" :size="18" />
            <span>{{ loading ? 'Envoi...' : 'Soumettre à validation' }}</span>
          </button>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'
import { formatMoneyAmount } from '../utils/formatters'

const router = useRouter()
const loading = ref(false)
const error = ref('')
const success = ref('')
const caisses = ref([])
const form = ref({
  caisse_id: '',
  type: 'entree',
  montant: null,
  source: '',
  description: '',
})

const formatMoney = (amount) => formatMoneyAmount(amount)

const loadCaisses = async () => {
  try {
    const { data } = await api.get('/v1/caisses', { params: { active: 1 } })
    caisses.value = data.caisses || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Erreur chargement caisses'
  }
}

const submit = async () => {
  loading.value = true
  error.value = ''
  success.value = ''
  try {
    await api.post('/v1/caisses/mouvements', form.value)
    success.value = 'Mouvement soumis à validation'
    setTimeout(() => router.push('/caisses/validations'), 500)
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Erreur création mouvement'
  } finally {
    loading.value = false
  }
}

onMounted(loadCaisses)
</script>

<style scoped>
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.full {
  grid-column: 1 / -1;
}

.textarea {
  min-height: 110px;
  resize: vertical;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
}

@media (max-width: 760px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
