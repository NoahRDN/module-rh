<template>
  <div class="create-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Career mobility</p>
        <h1>Nouvelle mobilite</h1>
        <p class="hero-subtitle">Enregistrez une mobilite interne ou une promotion pour suivre l'historique de poste.</p>

        <div class="hero-pills">
          <span class="pill">Employe</span>
          <span class="pill">Poste</span>
          <span class="pill">Motif</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn btn-secondary" to="/historiques">Retour</RouterLink>
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
          <p class="section-kicker">Mobility form</p>
          <h2>Informations</h2>
        </div>
        <span class="section-chip">Creation</span>
      </div>

      <form class="fields-grid" @submit.prevent="createHistorique">
        <label class="field-card full">
          <span class="field-label">Employe</span>
          <select class="select" v-model="form.employe_id" required>
            <option value="">Selectionner</option>
            <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }} {{ emp.prenom }}</option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Nouveau poste</span>
          <select class="select" v-model="form.poste_id" required>
            <option value="">Selectionner</option>
            <option v-for="p in postes" :key="p.id" :value="p.id">{{ p.nom }}</option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Departement</span>
          <select class="select" v-model="form.departement_id" required>
            <option value="">Selectionner</option>
            <option v-for="d in departements" :key="d.id" :value="d.id">{{ d.nom }}</option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Date de changement</span>
          <input class="input" type="date" v-model="form.date_changement" required />
        </label>

        <label class="field-card full">
          <span class="field-label">Motif</span>
          <input class="input" v-model="form.motif" placeholder="Promotion, mobilite interne..." />
        </label>

        <div class="submit-row">
          <button class="btn" type="submit" :disabled="saving">{{ saving ? 'Enregistrement...' : 'Enregistrer' }}</button>
          <RouterLink class="btn btn-secondary" to="/historiques">Annuler</RouterLink>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const employes = ref([])
const postes = ref([])
const departements = ref([])
const message = ref('')
const messageType = ref('info')
const saving = ref(false)

const form = ref({
  employe_id: '',
  poste_id: '',
  departement_id: '',
  date_changement: '',
  motif: ''
})

const fetchRefs = async () => {
  const [emps, pos, deps] = await Promise.all([
    api.get('/v1/employes'),
    api.get('/v1/postes'),
    api.get('/v1/departements')
  ])
  employes.value = emps.data.data || []
  postes.value = pos.data.data || []
  departements.value = deps.data.data || []
}

const createHistorique = async () => {
  if (saving.value) return
  saving.value = true
  try {
    const payload = { ...form.value }
    await api.post('/v1/historiques-postes', payload)
    message.value = 'Mouvement enregistré'
    messageType.value = 'success'
    setTimeout(() => router.push('/historiques'), 500)
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur lors de l’enregistrement'
    messageType.value = 'danger'
  } finally {
    saving.value = false
  }
}

onMounted(fetchRefs)
</script>
