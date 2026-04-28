<template>
  <div class="create-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Attendance tracking</p>
        <h1>Nouveau pointage</h1>
        <p class="hero-subtitle">Ajoutez une entree/sortie ou un pointage de pause pour un employe.</p>

        <div class="hero-pills">
          <span class="pill">Employe</span>
          <span class="pill">Type</span>
          <span class="pill">Horodatage</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn btn-secondary" to="/pointages">Retour</RouterLink>
            <button class="btn" type="button" @click="createPointage" :disabled="saving">
              {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>

          <div v-if="error.title" class="status-banner" :class="error.variant">
            <span class="status-dot"></span>
            <div class="status-copy">
              <strong>{{ error.title }}</strong>
              <span>{{ error.detail }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="card section-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Punch form</p>
          <h2>Informations</h2>
        </div>
        <span class="section-chip">Creation</span>
      </div>

      <form class="fields-grid" @submit.prevent="createPointage">
        <label class="field-card full">
          <span class="field-label">Employe</span>
          <select class="select" v-model="form.employe_id" required>
            <option value="">Selectionner</option>
            <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Type</span>
          <select class="select" v-model="form.type" required>
            <option value="entree">Entree</option>
            <option value="sortie">Sortie</option>
            <option value="pause_debut">Pause debut</option>
            <option value="pause_fin">Pause fin</option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Date/heure</span>
          <input class="input" type="datetime-local" v-model="form.pointe_a" required />
        </label>

        <label class="field-card">
          <span class="field-label">Source</span>
          <input class="input" v-model="form.source" placeholder="Badgeuse, manuel..." />
        </label>

        <label class="field-card full">
          <span class="field-label">Commentaire</span>
          <input class="input" v-model="form.commentaire" placeholder="Commentaire" />
        </label>

        <div class="submit-row">
          <button class="btn" type="submit" :disabled="saving">{{ saving ? 'Enregistrement...' : 'Enregistrer' }}</button>
          <RouterLink class="btn btn-secondary" to="/pointages">Annuler</RouterLink>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const employes = ref([])
const error = ref({
  title: '',
  detail: '',
  variant: 'danger'
})
const saving = ref(false)

const form = ref({
  employe_id: '',
  type: 'entree',
  pointe_a: '',
  source: '',
  commentaire: ''
})

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes', { params: { active_only: true } })
  employes.value = data.data || []
}

const setError = (status, detail) => {
  const message = detail || 'Erreur lors de l’enregistrement'
  let title = 'Erreur serveur'
  let variant = status === 422 ? 'warning' : 'danger'

  if (detail?.startsWith('Double pointage')) {
    title = 'Double pointage'
    variant = 'danger'
  } else if (detail?.startsWith('Sortie sans entree')) {
    title = 'Sortie sans entree'
    variant = 'danger'
  } else if (detail?.startsWith('Chevauchement')) {
    title = 'Chevauchement'
    variant = 'danger'
  } else if (status === 422) {
    title = 'Erreur de validation'
  }

  error.value = {
    title,
    detail: message,
    variant
  }
}

const clearError = () => {
  error.value = {
    title: '',
    detail: '',
    variant: 'danger'
  }
}

const createPointage = async () => {
  if (saving.value) return
  saving.value = true
  clearError()
  try {
    await api.post('/v1/pointages', form.value)
    router.push('/pointages')
  } catch (e) {
    const status = e.response?.status
    const detail = e.response?.data?.message
    setError(status, detail)
  } finally {
    saving.value = false
  }
}

onMounted(fetchEmployes)
</script>
