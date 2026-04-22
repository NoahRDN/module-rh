<template>
  <div class="create-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Contract management</p>
        <h1>Nouveau contrat</h1>
        <p class="hero-subtitle">Associez un employe et definissez les dates, la duree et les options de renouvellement.</p>

        <div class="hero-pills">
          <span class="pill">Employe</span>
          <span class="pill">Dates</span>
          <span class="pill">Renouvellement</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn btn-secondary" to="/contrats">Retour</RouterLink>
            <button class="btn" type="button" @click="createContrat" :disabled="saving">
              {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>

          <div v-if="message" class="status-banner" :class="messageType">
            <span class="status-dot"></span>
            <span>{{ message }}</span>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Employes eligibles:
              <strong>{{ employesDisponibles.length }}</strong>
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="card section-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Contract form</p>
          <h2>Informations</h2>
        </div>
        <span class="section-chip">Creation</span>
      </div>

      <form class="fields-grid" @submit.prevent="createContrat">
        <label class="field-card full">
          <span class="field-label">Employe</span>
          <select class="select" v-model="form.employe_id" required>
            <option value="">Selectionner</option>
            <option v-for="emp in employesDisponibles" :key="emp.id" :value="emp.id">
              {{ emp.matricule }} - {{ emp.nom }} {{ emp.prenom }}
            </option>
          </select>
          <span v-if="!employesDisponibles.length" class="muted">Aucun employe disponible (tous ont un contrat actif).</span>
        </label>

        <label class="field-card">
          <span class="field-label">Type de contrat</span>
          <select class="select" v-model="form.type_contrat" required>
            <option v-for="t in typeOptions" :key="t" :value="t">{{ t }}</option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Date de debut</span>
          <input class="input" type="date" v-model="form.date_debut" required />
        </label>

        <label class="field-card">
          <span class="field-label">Salaire de base</span>
          <input class="input" v-model="form.salaire_base" placeholder="Salaire" required type="number" step="0.01" />
        </label>

        <label class="field-card">
          <span class="field-label">Duree (jours)</span>
          <input class="input" type="number" min="0" v-model.number="form.duree_jours" />
        </label>

        <label class="field-card">
          <span class="field-label">Duree (mois)</span>
          <input class="input" type="number" min="0" v-model.number="form.duree_mois" />
        </label>

        <label class="field-card">
          <span class="field-label">Duree (annees)</span>
          <input class="input" type="number" min="0" v-model.number="form.duree_ans" />
        </label>

        <label class="field-card">
          <span class="field-label">Essai debut</span>
          <input class="input" type="date" v-model="form.essai_debut" />
        </label>

        <label class="field-card">
          <span class="field-label">Essai (jours)</span>
          <input class="input" type="number" min="0" v-model.number="form.essai_jours" />
        </label>

        <label class="field-card">
          <span class="field-label">Essai (mois)</span>
          <input class="input" type="number" min="0" v-model.number="form.essai_mois" />
        </label>

        <label class="field-card">
          <span class="field-label">Essai (annees)</span>
          <input class="input" type="number" min="0" v-model.number="form.essai_ans" />
        </label>

        <label class="field-card full">
          <span class="field-label">Options</span>
          <div class="chip-list">
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" v-model="form.renouvelable" />
              <span>Renouvelable</span>
            </label>
          </div>
        </label>

        <div class="submit-row">
          <button class="btn" type="submit" :disabled="saving">{{ saving ? 'Enregistrement...' : 'Enregistrer' }}</button>
          <RouterLink class="btn btn-secondary" to="/contrats">Annuler</RouterLink>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const employes = ref([])
const message = ref('')
const messageType = ref('info')
const saving = ref(false)
const typeOptions = ['CDI', 'CDD', 'Stage', 'Interim', 'Consultant', 'Apprenti']
const employesDisponibles = computed(() => employes.value.filter((e) => !e.actif))

const form = ref({
  employe_id: '',
  type_contrat: 'CDI',
  date_debut: '',
  duree_jours: 0,
  duree_mois: 0,
  duree_ans: 0,
  essai_jours: 0,
  essai_mois: 0,
  essai_ans: 0,
  essai_debut: '',
  salaire_base: '',
  renouvelable: false
})

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes', { params: { all: 1 } })
  employes.value = data.data || data
}

const createContrat = async () => {
  if (saving.value) return
  saving.value = true
  try {
    const payload = { ...form.value }
    // dates de fin calculées côté backend à partir des durées, on n'envoie pas de date_fin
    await api.post('/v1/contrats', payload)
    message.value = 'Contrat créé'
    messageType.value = 'success'
    setTimeout(() => router.push('/contrats'), 500)
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur lors de la création'
    messageType.value = 'danger'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await fetchEmployes()
})

</script>
