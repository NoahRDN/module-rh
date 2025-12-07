<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between mb-4">
    <div>
      <h1 class="text-2xl font-semibold">Demandes de congés</h1>
      <p class="text-sm text-slate-500">Workflow manager + RH</p>
    </div>
    <div class="flex w-full gap-2 lg:w-auto">
      <select class="select" v-model="filterEmploye" @change="fetchDemandes">
        <option value="">Tous les employés</option>
        <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
      </select>
      <button class="btn btn-secondary" @click="fetchDemandes">Actualiser</button>
    </div>
  </div>

  <div class="grid gap-4 lg:grid-cols-3">
    <div class="card lg:col-span-2">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold">Demandes en cours</h3>
        <span class="muted text-sm">Manager → RH</span>
      </div>
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-slate-800/60">
            <th class="py-2 text-left text-slate-400 text-xs">Employé</th>
            <th class="py-2 text-left text-slate-400 text-xs">Type</th>
            <th class="py-2 text-left text-slate-400 text-xs">Période</th>
            <th class="py-2 text-left text-slate-400 text-xs">Statut</th>
            <th class="py-2 text-left text-slate-400 text-xs">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <tr v-for="d in demandes" :key="d.id" class="hover:bg-slate-800/30 transition">
            <td class="py-2">
              <p class="font-semibold text-slate-100">{{ d.employe?.matricule || '—' }}</p>
              <p class="text-xs text-slate-500">{{ d.employe ? `${d.employe.nom} ${d.employe.prenom}` : '—' }}</p>
            </td>
            <td class="py-2">{{ d.type?.nom || '—' }}</td>
            <td class="py-2 text-xs text-slate-300">{{ d.date_debut }} → {{ d.date_fin }}</td>
            <td class="py-2">
              <span class="px-2 py-1 rounded-full text-xs" :class="badgeClass(d.statut)">
                {{ d.statut }}
              </span>
            </td>
            <td class="py-2 flex flex-wrap gap-2">
              <button class="btn btn-secondary text-xs" @click="approveManager(d.id)">Manager ✓</button>
              <button class="btn text-xs" style="padding: 6px 10px;" @click="approveRH(d.id)">RH ✓</button>
              <button class="btn btn-secondary text-xs" style="padding: 6px 10px; background: rgba(239,68,68,0.12); color: #fca5a5;" @click="reject(d.id)">Refuser</button>
            </td>
          </tr>
          <tr v-if="!demandes.length">
            <td colspan="5" class="py-3 text-center text-slate-500">Aucune demande</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2 class="text-lg font-semibold">Nouvelle demande</h2>
      <p class="text-sm text-slate-500 mb-3">Création employé</p>
      <form class="space-y-3" @submit.prevent="createDemande">
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Employé</label>
          <select class="select" v-model="form.employe_id" required>
            <option value="">Employé</option>
            <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
          </select>
        </div>
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Type de congé</label>
          <select class="select" v-model="form.type_id" required>
            <option value="">Type</option>
            <option v-for="t in types" :key="t.id" :value="t.id">{{ t.nom }}</option>
          </select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Date début</label>
            <input class="input" type="date" v-model="form.date_debut" required />
          </div>
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Date fin</label>
            <input class="input" type="date" v-model="form.date_fin" required />
          </div>
        </div>
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Motif (optionnel)</label>
          <textarea class="input" rows="3" v-model="form.motif" placeholder="Motif (optionnel)"></textarea>
        </div>
        <button class="btn w-full" type="submit">Créer</button>
        <p class="text-sm text-slate-500" v-if="message">{{ message }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'

const demandes = ref([])
const types = ref([])
const employes = ref([])
const filterEmploye = ref('')
const message = ref('')
const form = ref({
  employe_id: '',
  type_id: '',
  date_debut: '',
  date_fin: '',
  motif: ''
})

const badgeClass = (statut) => {
  switch (statut) {
    case 'manager_valide':
      return 'bg-blue-50 text-blue-600'
    case 'rh_valide':
      return 'bg-green-50 text-green-600'
    case 'rejete':
      return 'bg-red-50 text-red-600'
    default:
      return 'bg-slate-100 text-slate-600'
  }
}

const fetchDemandes = async () => {
  const params = filterEmploye.value ? { employe_id: filterEmploye.value } : {}
  const { data } = await api.get('/v1/demandes-conges', { params })
  demandes.value = data.data || []
}

const fetchRefs = async () => {
  const [t, e] = await Promise.all([
    api.get('/v1/absences-types'),
    api.get('/v1/employes')
  ])
  types.value = t.data.data || []
  employes.value = e.data.data || []
}

const createDemande = async () => {
  try {
    await api.post('/v1/demandes-conges', form.value)
    message.value = 'Demande créée'
    await fetchDemandes()
  } catch (e) {
    message.value = 'Erreur'
  }
}

const approveManager = async (id) => {
  await api.post(`/v1/demandes-conges/${id}/manager-approve`)
  await fetchDemandes()
}

const approveRH = async (id) => {
  await api.post(`/v1/demandes-conges/${id}/rh-approve`)
  await fetchDemandes()
}

const reject = async (id) => {
  await api.post(`/v1/demandes-conges/${id}/reject`)
  await fetchDemandes()
}

onMounted(async () => {
  await fetchRefs()
  await fetchDemandes()
})
</script>
