<template>
  <div class="flex flex-col gap-4">
    <div class="flex items-center justify-between">
      <div class="page-title">
        <h1>Créer un contrat</h1>
        <span>Associer un employé et définir les dates</span>
      </div>
      <RouterLink class="btn btn-secondary whitespace-nowrap" to="/contrats">← Retour à la liste</RouterLink>
    </div>

    <div class="card">
      <form class="grid gap-4" @submit.prevent="createContrat">
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Employé</label>
          <select class="select" v-model="form.employe_id" required>
            <option value="">Employé</option>
            <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }} {{ emp.prenom }}</option>
          </select>
        </div>

        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Type de contrat</label>
          <select class="select" v-model="form.type_contrat" required>
            <option v-for="t in typeOptions" :key="t" :value="t">{{ t }}</option>
          </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Date de début</label>
            <input class="input" type="date" v-model="form.date_debut" required />
          </div>
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Date de fin (optionnel)</label>
            <input class="input" type="date" v-model="form.date_fin" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Début période d'essai (optionnel)</label>
            <input class="input" type="date" v-model="form.periode_essai_debut" />
          </div>
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Fin période d'essai (optionnel)</label>
            <input class="input" type="date" v-model="form.periode_essai_fin" />
          </div>
        </div>

        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Salaire de base</label>
          <input class="input" v-model="form.salaire_base" placeholder="Salaire base" required type="number" step="0.01" />
        </div>

        <label class="muted flex items-center gap-2"><input type="checkbox" v-model="form.renouvelable" /> Renouvelable</label>

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
const message = ref('')
const typeOptions = ['CDI', 'CDD', 'Stage', 'Interim', 'Consultant', 'Apprenti']

const form = ref({
  employe_id: '',
  type_contrat: 'CDI',
  date_debut: '',
  date_fin: '',
  periode_essai_debut: '',
  periode_essai_fin: '',
  salaire_base: '',
  renouvelable: false
})

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes')
  employes.value = data.data || []
}

const createContrat = async () => {
  try {
    const payload = { ...form.value }
    payload.date_fin = payload.date_fin || null
    payload.periode_essai_debut = payload.periode_essai_debut || null
    payload.periode_essai_fin = payload.periode_essai_fin || null
    await api.post('/v1/contrats', payload)
    message.value = 'Contrat créé'
    setTimeout(() => router.push('/contrats'), 500)
  } catch (e) {
    message.value = 'Erreur lors de la création'
  }
}

onMounted(fetchEmployes)
</script>
