<template>
  <div class="create-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Legal calendar</p>
        <h1>Nouveau jour ferie</h1>
        <p class="hero-subtitle">Ajoutez une date de reference utilisee par les absences, la presence et la paie.</p>

        <div class="hero-pills">
          <span class="pill">Calendrier</span>
          <span class="pill">Presence</span>
          <span class="pill">Paie</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn btn-secondary" to="/jours-feries">Retour</RouterLink>
            <button class="btn" type="button" @click="save" :disabled="loading">
              {{ loading ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>

          <div v-if="message" class="status-banner" :class="messageType">
            <span class="status-dot"></span>
            <span>{{ message }}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="card section-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Holiday form</p>
          <h2>Informations</h2>
        </div>
        <span class="section-chip">Creation</span>
      </div>

      <form class="fields-grid" @submit.prevent="save">
        <label class="field-card">
          <span class="field-label">Nom</span>
          <input class="input" v-model="form.nom" placeholder="Ex: Nouvel an" required />
        </label>

        <label class="field-card">
          <span class="field-label">Date</span>
          <input class="input" type="date" v-model="form.date" required />
        </label>

        <label class="field-card full">
          <span class="field-label">Options</span>
          <div class="chip-list">
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" v-model="form.recurrent" />
              <span>Recurrent chaque annee</span>
            </label>
          </div>
        </label>

        <div class="submit-row">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? 'Enregistrement...' : 'Enregistrer' }}</button>
          <button class="btn btn-secondary" type="button" @click="resetForm">Annuler</button>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const loading = ref(false)
const message = ref('')
const messageType = ref('info')

const form = ref({
  nom: '',
  date: '',
  recurrent: false
})

const resetForm = () => {
  form.value = { nom: '', date: '', recurrent: false }
  message.value = ''
}

const save = async () => {
  message.value = ''
  messageType.value = 'info'
  if (!form.value.nom || !form.value.date) {
    message.value = 'Nom et date sont requis.'
    messageType.value = 'danger'
    return
  }
  loading.value = true
  try {
    await api.post('/v1/jours-feries', form.value)
    router.push('/jours-feries')
  } catch (e) {
    messageType.value = 'danger'
    message.value = e.response?.data?.message || 'Erreur lors de la sauvegarde.'
  } finally {
    loading.value = false
  }
}
</script>
