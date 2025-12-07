<template>
  <div class="flex flex-col gap-4">
    <div class="flex items-center justify-between">
      <div class="page-title">
        <h1>Nouvelle mobilité / promotion</h1>
        <span>Associer un employé à un poste et un département</span>
      </div>
      <RouterLink class="btn btn-secondary whitespace-nowrap" to="/historiques">← Retour</RouterLink>
    </div>

    <div class="card">
      <form class="grid gap-4" @submit.prevent="createHistorique">
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Employé</label>
          <select class="select" v-model="form.employe_id" required>
            <option value="">Employé</option>
            <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }} {{ emp.prenom }}</option>
          </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Nouveau poste</label>
            <select class="select" v-model="form.poste_id">
              <option value="">(optionnel)</option>
              <option v-for="p in postes" :key="p.id" :value="p.id">{{ p.nom }}</option>
            </select>
          </div>
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Département</label>
            <select class="select" v-model="form.departement_id">
              <option value="">(optionnel)</option>
              <option v-for="d in departements" :key="d.id" :value="d.id">{{ d.nom }}</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Date de changement</label>
            <input class="input" type="date" v-model="form.date_changement" required />
          </div>
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Motif</label>
            <input class="input" v-model="form.motif" placeholder="Promotion, mobilité interne..." />
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button class="btn" type="submit">Enregistrer</button>
          <p class="muted" v-if="message">{{ message }}</p>
        </div>
      </form>
    </div>
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
  try {
    const payload = { ...form.value }
    payload.poste_id = payload.poste_id || null
    payload.departement_id = payload.departement_id || null
    await api.post('/v1/historiques-postes', payload)
    message.value = 'Mouvement enregistré'
    setTimeout(() => router.push('/historiques'), 500)
  } catch (e) {
    message.value = 'Erreur lors de l’enregistrement'
  }
}

onMounted(fetchRefs)
</script>
