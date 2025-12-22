<template>
  <div class="max-w-3xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold">Ajouter un pointage</h1>
        <p class="text-sm text-slate-500">Entrée, sortie ou pause</p>
      </div>
      <RouterLink class="btn btn-secondary" to="/pointages">← Retour</RouterLink>
    </div>

    <div class="card">
      <form class="grid gap-3" @submit.prevent="createPointage">
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Employé</label>
          <select class="select" v-model="form.employe_id" required>
            <option value="">Employé</option>
            <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
          </select>
        </div>
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Type</label>
          <select class="select" v-model="form.type" required>
            <option value="entree">Entrée</option>
            <option value="sortie">Sortie</option>
            <option value="pause_debut">Pause début</option>
            <option value="pause_fin">Pause fin</option>
          </select>
        </div>
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Date/heure</label>
          <input class="input" type="datetime-local" v-model="form.pointe_a" required />
        </div>
        <input class="input" v-model="form.source" placeholder="Source (badgeuse, manuel...)" />
        <input class="input" v-model="form.commentaire" placeholder="Commentaire" />
        <button class="btn" type="submit">Enregistrer</button>
        <p class="muted" v-if="message">{{ message }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const employes = ref([])
const message = ref('')

const form = ref({
  employe_id: '',
  type: 'entree',
  pointe_a: '',
  source: '',
  commentaire: ''
})

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes', { params: { active_only: true } })
  employes.value = data.data || []
}

const createPointage = async () => {
  try {
    await api.post('/v1/pointages', form.value)
    router.push('/pointages')
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur lors de l’enregistrement'
  }
}

onMounted(fetchEmployes)
</script>
