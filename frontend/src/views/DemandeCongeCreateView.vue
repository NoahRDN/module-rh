<template>
  <div class="max-w-3xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold">Nouvelle demande de congé</h1>
        <p class="text-sm text-slate-500">Création côté administrateur/manager</p>
      </div>
      <RouterLink class="btn btn-secondary" to="/demandes-conges">← Retour à la liste</RouterLink>
    </div>

    <div class="card">
      <form class="grid gap-3" @submit.prevent="createDemande">
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Employé</label>
          <select class="select" v-model="form.employe_id" required>
            <option value="">Employé</option>
            <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
          </select>
        </div>
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Type de congé</label>
          <select class="select" v-model="form.type_conge_id" required>
            <option value="">Type</option>
            <option v-for="t in types" :key="t.id" :value="t.id">{{ t.libelle }}</option>
          </select>
        </div>
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Date début</label>
          <input class="input" type="date" v-model="form.date_debut" required />
        </div>
        <div class="grid gap-1" v-if="showDateFin">
          <label class="text-sm text-slate-400">Date fin</label>
          <input class="input" type="date" v-model="form.date_fin" />
        </div>
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Motif (optionnel)</label>
          <textarea class="input" rows="3" v-model="form.motif" placeholder="Motif (optionnel)"></textarea>
        </div>
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Type de document (optionnel)</label>
          <input class="input" placeholder="Justificatif congé" v-model="form.type_document" />
        </div>
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Justificatif (PDF/IMG, 4 Mo max)</label>
          <input class="input" type="file" accept=".pdf,image/*" @change="onFileChange" />
        </div>
        <button class="btn w-full" type="submit">Créer</button>
        <p class="text-sm text-slate-500" v-if="message">{{ message }}</p>
      </form>
    </div>
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
  }
}

const onFileChange = (e) => {
  const file = e.target.files?.[0]
  form.value.justificatif = file || null
}

onMounted(fetchRefs)
</script>
