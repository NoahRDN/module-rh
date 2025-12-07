<template>
  <div class="flex items-center justify-between mb-4">
    <div>
      <h1 class="text-2xl font-semibold">Pointage & Heures sup</h1>
      <p class="text-sm text-slate-500">Entrées / sorties / pauses</p>
    </div>
    <div class="flex gap-2">
      <select class="select" v-model="filters.employe_id" @change="fetchPointages">
        <option value="">Tous les employés</option>
        <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
      </select>
      <input class="input" type="date" v-model="filters.from" @change="fetchPointages" />
      <input class="input" type="date" v-model="filters.to" @change="fetchPointages" />
    </div>
  </div>

  <div class="grid gap-4 lg:grid-cols-3">
    <div class="card lg:col-span-2">
      <h2 class="text-lg font-semibold mb-2">Liste des pointages</h2>
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th>Employé</th>
              <th>Type</th>
              <th>Date/heure</th>
              <th>Source</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in pointages" :key="p.id">
              <td>{{ p.employe?.matricule || '—' }}</td>
              <td>{{ p.type }}</td>
              <td>{{ p.pointe_a }}</td>
              <td>{{ p.source }}</td>
            </tr>
            <tr v-if="!pointages.length">
              <td colspan="4" class="muted">Aucun pointage</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <h2 class="text-lg font-semibold">Ajouter un pointage</h2>
      <form class="grid" style="gap: 10px; margin-top: 10px;" @submit.prevent="createPointage">
        <select class="select" v-model="form.employe_id" required>
          <option value="">Employé</option>
          <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
        </select>
        <select class="select" v-model="form.type" required>
          <option value="entree">Entrée</option>
          <option value="sortie">Sortie</option>
          <option value="pause_debut">Pause début</option>
          <option value="pause_fin">Pause fin</option>
        </select>
        <input class="input" type="datetime-local" v-model="form.pointe_a" required />
        <input class="input" v-model="form.source" placeholder="Source (badgeuse, manuel...)" />
        <input class="input" v-model="form.commentaire" placeholder="Commentaire" />
        <button class="btn" type="submit">Enregistrer</button>
        <p class="muted" v-if="message">{{ message }}</p>
      </form>
    </div>
  </div>

  <hr class="divider" />

  <div class="grid gap-4 lg:grid-cols-3">
    <div class="card">
      <h2 class="text-lg font-semibold">Relevé journalier</h2>
      <form class="grid" style="gap: 10px; margin-top: 10px;" @submit.prevent="loadJournalier">
        <select class="select" v-model="journalier.employe_id" required>
          <option value="">Employé</option>
          <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
        </select>
        <input class="input" type="date" v-model="journalier.date" required />
        <button class="btn" type="submit">Calculer</button>
      </form>
      <div v-if="resumeJour" class="mt-3 text-sm">
        <p>Heures travaillées : {{ resumeJour.heures_travaillees }} h</p>
        <p>Heures supp. : {{ resumeJour.heures_supplementaires }} h</p>
        <p>Retard : {{ resumeJour.retard_minutes }} min</p>
        <p>Pauses : {{ resumeJour.minutes_pauses }} min</p>
      </div>
    </div>

    <div class="card lg:col-span-2">
      <h2 class="text-lg font-semibold">Relevé mensuel</h2>
      <form class="grid" style="gap: 10px; margin-top: 10px;" @submit.prevent="loadMensuel">
        <div class="grid" style="grid-template-columns: repeat(auto-fit,minmax(120px,1fr)); gap: 8px;">
          <select class="select" v-model="mensuel.employe_id" required>
            <option value="">Employé</option>
            <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
          </select>
          <input class="input" type="number" min="2000" max="2100" v-model="mensuel.year" placeholder="Année" required />
          <input class="input" type="number" min="1" max="12" v-model="mensuel.month" placeholder="Mois" required />
          <button class="btn" type="submit">Calculer</button>
        </div>
      </form>
      <div v-if="resumeMensuel" class="mt-3 text-sm">
        <div v-for="(res, jour) in resumeMensuel" :key="jour" class="border-b border-slate-800/20 pb-2 mb-2">
          <p class="font-semibold">{{ jour }}</p>
          <p>Heures travaillées : {{ res.heures_travaillees }} h</p>
          <p>Heures supp. : {{ res.heures_supplementaires }} h</p>
          <p>Retard : {{ res.retard_minutes }} min</p>
          <p>Pauses : {{ res.minutes_pauses }} min</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const pointages = ref([])
const employes = ref([])
const message = ref('')

const filters = ref({
  employe_id: '',
  from: '',
  to: ''
})

const form = ref({
  employe_id: '',
  type: 'entree',
  pointe_a: '',
  source: '',
  commentaire: ''
})

const journalier = ref({
  employe_id: '',
  date: ''
})

const resumeJour = ref(null)

const mensuel = ref({
  employe_id: '',
  year: new Date().getFullYear(),
  month: new Date().getMonth() + 1
})

const resumeMensuel = ref(null)

const fetchPointages = async () => {
  const params = { ...filters.value }
  const { data } = await api.get('/v1/pointages', { params })
  pointages.value = data.data || []
}

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes')
  employes.value = data.data || []
}

const createPointage = async () => {
  try {
    await api.post('/v1/pointages', form.value)
    message.value = 'Pointage enregistré'
    await fetchPointages()
  } catch (e) {
    message.value = 'Erreur lors de l’enregistrement'
  }
}

const loadJournalier = async () => {
  const { data } = await api.get('/v1/pointages/releve-journalier', { params: journalier.value })
  resumeJour.value = data.resume
}

const loadMensuel = async () => {
  const { data } = await api.get('/v1/pointages/releve-mensuel', { params: mensuel.value })
  resumeMensuel.value = data.jours
}

onMounted(async () => {
  await fetchEmployes()
  await fetchPointages()
})
</script>
