<template>
  <div class="flex items-center justify-between mb-4">
    <div>
      <h1 class="text-2xl font-semibold">Pointage & Heures sup</h1>
      <p class="text-sm text-slate-500">Entrées / sorties / pauses · retards · absences justifiées</p>
    </div>
    <div class="flex gap-2">
      <select class="select" v-model="filters.employe_id" @change="debouncedFetchPointages">
        <option value="">Tous les employés</option>
        <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
      </select>
      <input class="input" type="date" v-model="filters.from" @change="debouncedFetchPointages" />
      <input class="input" type="date" v-model="filters.to" @change="debouncedFetchPointages" />
    </div>
  </div>

  <div class="grid gap-4 lg:grid-cols-3">
    <div class="card lg:col-span-2">
      <h2 class="text-lg font-semibold mb-2">Liste des pointages</h2>
      <div class="grid gap-2 md:grid-cols-3 lg:grid-cols-6 mb-3">
        <input class="input" placeholder="Matricule" v-model="filtersLocal.matricule" />
        <input class="input" placeholder="Nom" v-model="filtersLocal.nom" />
        <input class="input" placeholder="Type" v-model="filtersLocal.type" />
        <input class="input" placeholder="Source" v-model="filtersLocal.source" />
        <input class="input" placeholder="Date" v-model="filtersLocal.date" />
      </div>
      <div class="flex justify-end mb-2">
        <button class="btn btn-secondary btn-xs" @click="resetFilters">Réinitialiser</button>
      </div>
      <div class="overflow-x-auto">
        <table class="table">
          <thead>
            <tr>
              <th class="cursor-pointer" @click="setSort('employe')">Employé {{ sortLabel('employe') }}</th>
              <th class="cursor-pointer" @click="setSort('type')">Type {{ sortLabel('type') }}</th>
            <th class="cursor-pointer" @click="setSort('date')">Date/heure {{ sortLabel('date') }}</th>
            <th class="cursor-pointer" @click="setSort('source')">Source {{ sortLabel('source') }}</th>
            <th>Absence</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in pointagesFiltres" :key="p.id">
            <td>{{ p.employe?.matricule || '—' }}</td>
            <td>{{ p.type }}</td>
            <td>{{ p.pointe_a }}</td>
            <td>{{ p.source }}</td>
            <td>
              <span v-if="p.absence_justifiee" class="chip" style="background: rgba(59,130,246,0.15); color: #93c5fd;">Justifiée</span>
              <span v-else class="muted">—</span>
            </td>
          </tr>
          <tr v-if="!pointagesFiltres.length">
            <td colspan="5" class="muted">Aucun pointage</td>
          </tr>
        </tbody>
      </table>
        <div class="flex items-center justify-between mt-3 text-sm text-slate-400">
          <span>Page {{ pagination.page }} / {{ pagination.last_page }} — {{ pagination.total }} lignes</span>
          <div class="flex items-center gap-2">
            <button class="btn btn-secondary text-xs" :disabled="pagination.page <= 1" @click="prevPage">Précédent</button>
            <button class="btn btn-secondary text-xs" :disabled="pagination.page >= pagination.last_page" @click="nextPage">Suivant</button>
          </div>
        </div>
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

</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import api from '../services/api'
import { debounce } from '../utils/debounce'

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

const releve = ref({
  mode: 'day',
  employe_id: '',
  date: '',
  month: ''
})

const resumeJour = ref(null)

const mensuel = ref({
  employe_id: '',
  year: new Date().getFullYear(),
  month: new Date().getMonth() + 1
})

const resumeMensuel = ref(null)
const resumeHebdo = ref([])
const filtersLocal = ref({ matricule: '', nom: '', type: '', source: '', date: '' })
const sortKey = ref('date')
const sortDir = ref('desc')
const pagination = ref({ page: 1, last_page: 1, total: 0 })

const fetchPointages = async () => {
  const params = { ...filters.value, page: pagination.value.page }
  const { data } = await api.get('/v1/pointages', { params })
  pointages.value = data.data || []
  if (data.meta) {
    pagination.value = {
      page: data.meta.current_page,
      last_page: data.meta.last_page,
      total: data.meta.total
    }
  } else if (data.current_page !== undefined) {
    pagination.value = {
      page: data.current_page,
      last_page: data.last_page,
      total: data.total
    }
  }
}

const debouncedFetchPointages = debounce(fetchPointages, 300)

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes', { params: { active_only: true } })
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

const loadReleve = async () => {
  if (!releve.value.employe_id) return
  if (releve.value.mode === 'day' && !releve.value.date) return
  if (releve.value.mode !== 'day' && !releve.value.month) return

  if (releve.value.mode === 'day') {
    const { data } = await api.get('/v1/pointages/releve-journalier', {
      params: { employe_id: releve.value.employe_id, date: releve.value.date }
    })
    resumeJour.value = data.resume
    resumeMensuel.value = null
    resumeHebdo.value = []
  } else if (releve.value.mode === 'month') {
    const [year, month] = releve.value.month.split('-')
    const { data } = await api.get('/v1/pointages/releve-mensuel', {
      params: { employe_id: releve.value.employe_id, year, month }
    })
    resumeMensuel.value = data.jours
    resumeJour.value = null
    resumeHebdo.value = []
  } else if (releve.value.mode === 'week') {
    const mois = releve.value.month
    const { data } = await api.get('/v1/pointages/releve-paie', {
      params: { employe_id: releve.value.employe_id, mois }
    })
    resumeHebdo.value = groupByWeek(data.details || [])
    resumeJour.value = null
    resumeMensuel.value = null
  }
}

const groupByWeek = (details) => {
  const weeks = {}
  details.forEach((d) => {
    const date = new Date(d.jour)
    const week = weekNumber(date)
    if (!weeks[week]) {
      weeks[week] = {
        label: week,
        heures_travaillees: 0,
        heures_supplementaires: 0,
        retard_minutes: 0,
        absences: 0,
        dimanches: 0
      }
    }
    weeks[week].heures_travaillees += d.heures_travaillees || 0
    weeks[week].heures_supplementaires += d.heures_supplementaires || 0
    weeks[week].retard_minutes += d.retard_minutes || 0
    weeks[week].absences += d.absent ? 1 : 0
    const day = date.getDay()
    if (day === 0) weeks[week].dimanches += 1
  })
  return Object.values(weeks)
}

const weekNumber = (date) => {
  const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()))
  const dayNum = d.getUTCDay() || 7
  d.setUTCDate(d.getUTCDate() + 4 - dayNum)
  const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1))
  const weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7)
  return `${d.getUTCFullYear()}-W${weekNo}`
}

const pointagesFiltres = computed(() => {
  const f = filtersLocal.value
  const toStr = (v) => String(v || '').toLowerCase()
  let list = pointages.value.filter((p) =>
    toStr(p.employe?.matricule).includes(toStr(f.matricule)) &&
    (`${toStr(p.employe?.nom)} ${toStr(p.employe?.prenom)}`).includes(toStr(f.nom)) &&
    toStr(p.type).includes(toStr(f.type)) &&
    toStr(p.source).includes(toStr(f.source)) &&
    toStr(p.pointe_a).includes(toStr(f.date))
  )
  const key = sortKey.value
  const dir = sortDir.value
  list = [...list].sort((a, b) => {
    const va = getVal(a, key)
    const vb = getVal(b, key)
    if (va < vb) return dir === 'asc' ? -1 : 1
    if (va > vb) return dir === 'asc' ? 1 : -1
    return 0
  })
  return list
})

const getVal = (p, key) => {
  const toStr = (v) => String(v || '').toLowerCase()
  switch (key) {
    case 'employe': return `${toStr(p.employe?.nom)} ${toStr(p.employe?.prenom)}`
    case 'type': return toStr(p.type)
    case 'source': return toStr(p.source)
    case 'date':
    default: return toStr(p.pointe_a)
  }
}

const setSort = (key) => {
  if (sortKey.value === key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  else { sortKey.value = key; sortDir.value = 'asc' }
}
const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')
const resetFilters = () => { filtersLocal.value = { matricule: '', nom: '', type: '', source: '', date: '' } }

const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchPointages()
  }
}
const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchPointages()
  }
}

onMounted(async () => {
  await fetchEmployes()
  await fetchPointages()
})
</script>

<style scoped>
.stat {
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 10px 12px;
  background: rgba(255, 255, 255, 0.02);
}
</style>
