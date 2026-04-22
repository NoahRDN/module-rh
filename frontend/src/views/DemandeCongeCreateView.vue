<template>
  <div class="create-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Leave workflow</p>
        <h1>Nouvelle demande de conge</h1>
        <p class="hero-subtitle">Creation cote administrateur/manager avec selection employe, type et dates.</p>

        <div class="hero-pills">
          <span class="pill">Employe</span>
          <span class="pill">Type</span>
          <span class="pill">Periode</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn btn-secondary" to="/demandes-conges">Retour</RouterLink>
            <button class="btn" type="button" @click="createDemande" :disabled="saving">
              {{ saving ? 'Creation...' : 'Creer' }}
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
          <p class="section-kicker">Leave request</p>
          <h2>Informations</h2>
        </div>
        <span class="section-chip">Creation</span>
      </div>

      <form class="fields-grid" @submit.prevent="createDemande">
        <label class="field-card full">
          <span class="field-label">Employe</span>
          <select class="select" v-model="form.employe_id" required>
            <option value="">Selectionner</option>
            <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
          </select>
        </label>

        <label class="field-card full">
          <span class="field-label">Type de conge</span>
          <select class="select" v-model="form.type_conge_id" required>
            <option value="">Selectionner</option>
            <option v-for="t in types" :key="t.id" :value="t.id">{{ t.libelle }}</option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Date debut</span>
          <input class="input" type="date" v-model="form.date_debut" required />
        </label>

        <label class="field-card" v-if="showDateFin">
          <span class="field-label">Date fin</span>
          <input class="input" type="date" v-model="form.date_fin" />
        </label>

        <label class="field-card full">
          <span class="field-label">Motif</span>
          <textarea class="input" rows="4" v-model="form.motif" placeholder="(optionnel)"></textarea>
        </label>

        <label class="field-card">
          <span class="field-label">Type de document</span>
          <input class="input" placeholder="Justificatif conge" v-model="form.type_document" />
        </label>

        <label class="field-card full">
          <span class="field-label">Justificatif</span>
          <input class="input" type="file" accept=".pdf,image/*" @change="onFileChange" />
        </label>

        <div class="submit-row">
          <button class="btn" type="submit" :disabled="saving">{{ saving ? 'Creation...' : 'Creer' }}</button>
          <RouterLink class="btn btn-secondary" to="/demandes-conges">Annuler</RouterLink>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()

const types = ref([])
const employes = ref([])
const message = ref('')
const saving = ref(false)
const form = ref({
  employe_id: '',
  type_conge_id: '',
  date_debut: '',
  date_fin: '',
  motif: '',
  type_document: '',
  justificatif: null
})

const showDateFin = computed(() => {
  const t = types.value.find((x) => x.id === form.value.type_conge_id)
  if (!t) return false
  const utiliseSolde = !!t.utilise_solde
  const flexible = t.jours_forfait === null || t.jours_forfait === undefined
  return utiliseSolde || flexible
})

const fetchRefs = async () => {
  const [t, e] = await Promise.all([
    api.get('/v1/types-conges'),
    api.get('/v1/employes', { params: { active_only: true } })
  ])
  types.value = t.data.data || []
  employes.value = e.data.data || []
}

const createDemande = async () => {
  if (saving.value) return
  saving.value = true
  try {
    const fd = new FormData()
    Object.entries(form.value).forEach(([key, val]) => {
      if (key === 'date_fin' && !showDateFin.value) return
      if (val !== null && val !== '' && key !== 'justificatif') {
        fd.append(key, val)
      }
    })
    if (form.value.justificatif) {
      fd.append('justificatif', form.value.justificatif)
    }
    await api.post('/v1/demandes-conges', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    router.push('/demandes-conges')
  } catch (e) {
    const errMsg = e.response?.data?.message || e.message || 'Erreur lors de la création'
    const valErrors = e.response?.data?.errors
    message.value = valErrors ? `${errMsg} : ${Object.values(valErrors).flat().join(' | ')}` : errMsg
  } finally {
    saving.value = false
  }
}

const onFileChange = (e) => {
  const file = e.target.files?.[0]
  form.value.justificatif = file || null
}

onMounted(fetchRefs)
</script>
