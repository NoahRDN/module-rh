<template>
  <div class="create-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Role taxonomy</p>
        <h1>{{ isEditing ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}</h1>
        <p class="hero-subtitle">
          Centralisez les familles de postes pour rendre les fonctions plus lisibles et homogènes dans les rapports RH.
        </p>

        <div class="hero-pills">
          <span class="pill">Nom</span>
          <span class="pill">Code</span>
          <span class="pill">Description</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn btn-secondary" to="/categories-postes">Retour</RouterLink>
            <button class="btn" type="button" @click="save" :disabled="loading || loadingInit">
              {{ loading ? 'Enregistrement...' : isEditing ? 'Mettre à jour' : 'Créer' }}
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
          <p class="section-kicker">Category form</p>
          <h2>Informations</h2>
        </div>
        <span class="section-chip">{{ isEditing ? 'Modification' : 'Création' }}</span>
      </div>

      <form class="fields-grid" @submit.prevent="save">
        <label class="field-card full">
          <span class="field-label">Nom</span>
          <input v-model="form.nom" class="input" placeholder="Ex: Cadres" required />
        </label>

        <label class="field-card">
          <span class="field-label">Code</span>
          <input v-model="form.code" class="input" placeholder="Optionnel" />
        </label>

        <label class="field-card full">
          <span class="field-label">Description</span>
          <textarea v-model="form.description" class="textarea" rows="4" placeholder="Optionnel"></textarea>
        </label>

        <div class="submit-row">
          <button class="btn" type="submit" :disabled="loading || loadingInit">
            {{ loading ? 'Enregistrement...' : isEditing ? 'Mettre à jour' : 'Créer' }}
          </button>
          <RouterLink class="btn btn-secondary" to="/categories-postes">Annuler</RouterLink>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import api from '../services/api'

const route = useRoute()
const router = useRouter()

const loadingInit = ref(false)
const loading = ref(false)
const message = ref('')
const messageType = ref('info')

const form = ref({ id: null, nom: '', code: '', description: '' })
const isEditing = computed(() => Boolean(route.params.id))

const loadExisting = async () => {
  if (!isEditing.value) return
  loadingInit.value = true
  message.value = ''
  try {
    const { data } = await api.get(`/v1/categories-postes/${route.params.id}`)
    form.value = {
      id: data?.id ?? route.params.id,
      nom: data?.nom ?? '',
      code: data?.code ?? '',
      description: data?.description ?? '',
    }
  } catch (e) {
    message.value = e.response?.data?.message || 'Impossible de charger la catégorie'
    messageType.value = 'danger'
  } finally {
    loadingInit.value = false
  }
}

const save = async () => {
  if (loading.value || loadingInit.value) return
  loading.value = true
  message.value = ''
  try {
    const payload = { ...form.value }
    if (isEditing.value) {
      await api.put(`/v1/categories-postes/${route.params.id}`, payload)
      message.value = 'Catégorie mise à jour.'
    } else {
      await api.post('/v1/categories-postes', payload)
      message.value = 'Catégorie créée.'
    }
    messageType.value = 'success'
    setTimeout(() => router.push('/categories-postes'), 450)
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur lors de l’enregistrement'
    messageType.value = 'danger'
  } finally {
    loading.value = false
  }
}

onMounted(loadExisting)
</script>

