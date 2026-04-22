<template>
  <div class="create-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Organization structure</p>
        <h1>Nouveau département</h1>
        <p class="hero-subtitle">
          Ajoutez un département et sa description pour structurer les rattachements métiers (postes,
          employés, contrats).
        </p>

        <div class="hero-pills">
          <span class="pill">Structure RH</span>
          <span class="pill">Référentiel</span>
          <span class="pill">Organisation</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink to="/departements" class="btn btn-secondary">Retour</RouterLink>
            <button class="btn" type="button" @click="submit" :disabled="loading">
              {{ loading ? 'Envoi...' : 'Enregistrer' }}
            </button>
          </div>

          <div v-if="message" class="status-banner danger">
            <span class="status-dot"></span>
            <span>{{ message }}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="card section-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Department form</p>
          <h2>Informations</h2>
        </div>
        <span class="section-chip">Création</span>
      </div>

      <form class="fields-grid" @submit.prevent="submit">
        <label class="field-card">
          <span class="field-label">Nom</span>
          <input class="input" v-model="form.nom" placeholder="Nom" required />
        </label>

        <label class="field-card full">
          <span class="field-label">Description</span>
          <textarea class="input" rows="4" v-model="form.description" placeholder="Description"></textarea>
        </label>

        <div class="submit-row">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? 'Envoi...' : 'Enregistrer' }}</button>
          <RouterLink to="/departements" class="btn btn-secondary">Annuler</RouterLink>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const loading = ref(false)
const message = ref('')
const form = ref({ nom: '', description: '' })

const submit = async () => {
  loading.value = true
  message.value = ''
  try {
    await api.post('/v1/departements', form.value)
    router.push('/departements')
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur lors de la création'
  } finally {
    loading.value = false
  }
}
</script>
