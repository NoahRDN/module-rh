<template>
  <div class="create-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Roles and functions</p>
        <h1>Nouveau poste</h1>
        <p class="hero-subtitle">
          Ajoutez un poste, son rattachement départemental et sa catégorie pour alimenter les fiches
          employé et la gestion des contrats.
        </p>

        <div class="hero-pills">
          <span class="pill">Référentiel</span>
          <span class="pill">Département</span>
          <span class="pill">Catégorie</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn btn-secondary" to="/postes">Retour</RouterLink>
            <button class="btn" type="button" @click="submit" :disabled="loading">
              {{ loading ? 'Enregistrement...' : 'Enregistrer' }}
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
          <p class="section-kicker">Role form</p>
          <h2>Informations</h2>
        </div>
        <span class="section-chip">Création</span>
      </div>

      <form class="fields-grid" @submit.prevent="submit">
        <label class="field-card">
          <span class="field-label">Nom</span>
          <input class="input" v-model="form.nom" placeholder="Nom" required />
        </label>

        <label class="field-card">
          <span class="field-label">Département</span>
          <select class="select" v-model="form.departement_id" required>
            <option value="">Sélectionner</option>
            <option v-for="dep in departements" :key="dep.id" :value="dep.id">{{ dep.nom }}</option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Catégorie</span>
          <select class="select" v-model="form.categorie">
            <option value="">(optionnel)</option>
            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
          </select>
        </label>

        <label class="field-card full">
          <span class="field-label">Description</span>
          <textarea class="input" rows="4" v-model="form.description" placeholder="Description"></textarea>
        </label>

        <div class="submit-row">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? 'Enregistrement...' : 'Enregistrer' }}</button>
          <RouterLink class="btn btn-secondary" to="/postes">Annuler</RouterLink>
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
const loading = ref(false)
const message = ref('')
const departements = ref([])
const categories = ref([])
const defaultCategories = ['Ouvriers', 'Employés', 'TAM', 'Cadres', 'Dirigeants']
const form = ref({ nom: '', description: '', departement_id: '', categorie: '' })

const loadDeps = async () => {
  const { data } = await api.get('/v1/departements')
  departements.value = data.data || data || []
}

const loadCategories = async () => {
  try {
    const { data } = await api.get('/v1/categories-postes')
    const payload = data || []
    categories.value = payload.length ? payload.map((c) => c.nom) : defaultCategories
  } catch (e) {
    categories.value = defaultCategories
  }
}

const submit = async () => {
  loading.value = true
  message.value = ''
  try {
    await api.post('/v1/postes', form.value)
    router.push('/postes')
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur lors de la création'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadDeps()
  await loadCategories()
})
</script>
