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
            <option v-for="emp in employesDisponibles" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }} {{ emp.prenom }}</option>
          </select>
          <p class="text-xs text-slate-500" v-if="!employesDisponibles.length">Aucun employé disponible (tous ont un contrat actif)</p>
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
            <label class="text-sm text-slate-400">Durée du contrat</label>
            <div class="flex flex-wrap gap-2 md:flex-nowrap">
              <label class="text-xs text-slate-500 flex flex-col gap-1 w-20">
                Jours
                <input class="input" type="number" min="0" v-model.number="form.duree_jours" />
              </label>
              <label class="text-xs text-slate-500 flex flex-col gap-1 w-20">
                Mois
                <input class="input" type="number" min="0" v-model.number="form.duree_mois" />
              </label>
              <label class="text-xs text-slate-500 flex flex-col gap-1 w-24">
                Années
                <input class="input" type="number" min="0" v-model.number="form.duree_ans" />
              </label>
            </div>
            <p class="text-xs text-slate-500">Laissez 0 pour ignorer l'unité</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Durée de période d'essai</label>
            <div class="flex flex-wrap gap-2 md:flex-nowrap">
              <label class="text-xs text-slate-500 flex flex-col gap-1 w-20">
                Jours
                <input class="input" type="number" min="0" v-model.number="form.essai_jours" />
              </label>
              <label class="text-xs text-slate-500 flex flex-col gap-1 w-20">
                Mois
                <input class="input" type="number" min="0" v-model.number="form.essai_mois" />
              </label>
              <label class="text-xs text-slate-500 flex flex-col gap-1 w-24">
                Années
                <input class="input" type="number" min="0" v-model.number="form.essai_ans" />
              </label>
            </div>
            <p class="text-xs text-slate-500">La période peut démarrer plus tard si précisé ci-dessous.</p>
          </div>
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Début période d'essai (optionnel)</label>
            <input class="input" type="date" v-model="form.essai_debut" />
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
import { onMounted, ref, computed } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const employes = ref([])
const message = ref('')
const typeOptions = ['CDI', 'CDD', 'Stage', 'Interim', 'Consultant', 'Apprenti']
console.log(employes);
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
  try {
    const payload = { ...form.value }
    // dates de fin calculées côté backend à partir des durées, on n'envoie pas de date_fin
    await api.post('/v1/contrats', payload)
    message.value = 'Contrat créé'
    setTimeout(() => router.push('/contrats'), 500)
  } catch (e) {
    message.value = 'Erreur lors de la création'
  }
}

onMounted(fetchEmployes)
onMounted(async () => {
  await fetchEmployes()
  console.log(employes.value)
})

</script>
