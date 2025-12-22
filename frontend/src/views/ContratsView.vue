<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Contrats</h1>
      <span>Contrats actifs / historiques</span>
    </div>
    <div class="flex items-center gap-2">
      <RouterLink class="btn btn-secondary whitespace-nowrap" to="/contrats-historiques">Historique</RouterLink>
      <select class="select" v-model="filterEmploye" @change="debouncedFetchContrats">
        <option value="">Tous les employés</option>
        <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
      </select>
      <RouterLink class="btn whitespace-nowrap" to="/contrats/nouveau">+ Nouveau contrat</RouterLink>
    </div>
  </div>

  <p class="text-sm text-emerald-600" v-if="banner">{{ banner }}</p>

  <div class="grid gap-2 md:grid-cols-3 lg:grid-cols-6 mb-3 filters-card">
    <input class="input" placeholder="Numéro" v-model="filters.numero" />
    <input class="input" placeholder="Matricule" v-model="filters.matricule" />
    <input class="input" placeholder="Nom" v-model="filters.nom" />
    <input class="input" placeholder="Type" v-model="filters.type" />
    <input class="input" placeholder="Département" v-model="filters.departement" />
    <input class="input" placeholder="Poste" v-model="filters.poste" />
  </div>
  <div class="flex justify-end items-center mb-3">
    <button class="btn btn-secondary btn-xs" @click="resetFilters">Réinitialiser</button>
  </div>

  <div v-if="loading" class="loading-overlay">
    <div class="spinner-big"></div>
    <p>Chargement des contrats...</p>
  </div>

  <div v-else class="card">
    <table class="table">
      <thead>
        <tr>
          <th class="cursor-pointer" @click="setSort('id')">ID {{ sortLabel('id') }}</th>
          <th class="cursor-pointer" @click="setSort('numero')">Numéro {{ sortLabel('numero') }}</th>
          <th class="cursor-pointer" @click="setSort('matricule')">Matricule {{ sortLabel('matricule') }}</th>
          <th class="cursor-pointer" @click="setSort('nom')">Nom & Prénom {{ sortLabel('nom') }}</th>
          <th class="cursor-pointer" @click="setSort('type')">Type {{ sortLabel('type') }}</th>
          <th>Durée</th>
          <th class="cursor-pointer" @click="setSort('date_debut')">Dates contrat {{ sortLabel('date_debut') }}</th>
          <th>Période d'essai</th>
          <th class="cursor-pointer" @click="setSort('renouvellement')">Date renouvellement {{ sortLabel('renouvellement') }}</th>
          <th>Renouvelable</th>
          <th class="cursor-pointer" @click="setSort('statut')">Statut {{ sortLabel('statut') }}</th>
          <th>Actions</th>
          <th class="cursor-pointer" @click="setSort('departement')">Département {{ sortLabel('departement') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="10" class="muted">Chargement...</td>
        </tr>
        <tr v-else v-for="c in contratsFiltres" :key="c.id">
          <td>{{ c.id }}</td>
          <td>{{ c.numero || '—' }}</td>
          <td>{{ c.employe?.matricule || '—' }}</td>
          <td>{{ c.employe ? `${c.employe.nom} ${c.employe.prenom}` : '—' }}</td>
          <td>{{ c.type_contrat }}</td>
          <td>{{ duree(c) }}</td>
          <td>
            <div>Début : {{ formatDate(c.date_debut) || '—' }}</div>
            <div>Fin : {{ formatDate(c.date_fin) || '—' }}</div>
          </td>
          <td>
            <div>
              <div>Début : {{ formatDate(c.periode_essai_debut) || '—' }}</div>
              <div>Fin : {{ formatDate(c.periode_essai_fin) || '—' }}</div>
            </div>
          </td>
          <td>{{ formatDate(currentEnd(c)) || '—' }}</td>
          <td>
            <span class="chip" :class="c.renouvelable ? '' : 'muted'">{{ c.renouvelable ? 'Oui' : 'Non' }}</span>
          </td>
          <td>
            <span class="chip" :class="c.statut === 'en_cours' ? '' : 'muted'">{{ c.statut || '—' }}</span>
          </td>
          <td class="space-x-2">
            <button class="btn btn-secondary text-xs" style="padding:6px 10px;" @click="ouvrirCloture(c)">Clore</button>
            <button class="btn btn-secondary text-xs" style="padding:6px 10px;" @click="ouvrirRenouv(c)">Renouveler</button>
            <RouterLink class="btn btn-secondary text-xs" style="padding:6px 10px;" :to="`/contrats/${c.id}`">Fiche</RouterLink>
            <button class="btn btn-secondary text-xs" style="padding:6px 10px;" @click="telechargerPdf(c.id)">PDF</button>
            <div v-if="renouvellementId === c.id" class="mt-2 flex flex-col gap-2 p-3 rounded-lg border border-slate-200 bg-slate-50/60 dark:bg-slate-800/40">
              <p class="text-sm font-semibold">Prolongation</p>
              <label class="text-xs text-slate-500">
                Cible
                <select class="select mt-1" v-model="renouvellementCible">
                  <option value="contrat">Contrat</option>
                  <option value="essai">Période d'essai</option>
                </select>
              </label>
              <div style="display:flex; gap:8px; align-items:flex-end; flex-wrap:nowrap;">
                <label class="text-xs text-slate-500 flex flex-col gap-1" style="width:80px;">
                  Jours
                  <input class="input" type="number" min="0" v-model.number="renouvellement.duree_jours" />
                </label>
                <label class="text-xs text-slate-500 flex flex-col gap-1" style="width:80px;">
                  Mois
                  <input class="input" type="number" min="0" v-model.number="renouvellement.duree_mois" />
                </label>
                <label class="text-xs text-slate-500 flex flex-col gap-1" style="width:90px;">
                  Années
                  <input class="input" type="number" min="0" v-model.number="renouvellement.duree_ans" />
                </label>
              </div>
              <div class="flex gap-2">
                <button class="btn btn-secondary text-xs" style="padding:6px 10px;" @click="confirmerRenouv(c)">Confirmer</button>
                <button class="btn btn-secondary text-xs" style="padding:6px 10px;" @click="annulerRenouv">Annuler</button>
              </div>
              <p class="text-xs text-red-500" v-if="message">{{ message }}</p>
            </div>
            <div v-if="clotureId === c.id" class="mt-2 flex flex-col gap-2 p-3 rounded-lg border border-rose-200 bg-rose-50/60 dark:bg-slate-800/40">
              <p class="text-sm font-semibold text-rose-700">Clôturer ce contrat</p>
              <label class="text-xs text-slate-500">
                Date de fin
                <input class="input mt-1" type="date" v-model="clotureDate" />
              </label>
              <div class="flex gap-2">
                <button class="btn btn-secondary text-xs" style="padding:6px 10px;" @click="confirmerCloture(c)">Clore</button>
                <button class="btn btn-secondary text-xs" style="padding:6px 10px;" @click="annulerCloture">Annuler</button>
              </div>
              <p class="text-xs text-red-500" v-if="messageCloture">{{ messageCloture }}</p>
            </div>
          </td>
          <td>{{ c.employe?.departement?.nom || '—' }}</td>
        </tr>
        <tr v-if="!contratsFiltres.length && !loading">
          <td colspan="10" class="muted">Aucun contrat</td>
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
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import api from '../services/api'
import { debounce } from '../utils/debounce'

const contrats = ref([])
const employes = ref([])
const filterEmploye = ref('')
const message = ref('')
const banner = ref('')
const typeOptions = ['CDI', 'CDD', 'Stage', 'Interim', 'Consultant', 'Apprenti']
const loading = ref(false)
const renouvellementId = ref(null)
const renouvellement = ref({ duree_jours: 0, duree_mois: 0, duree_ans: 0 })
const renouvellementCible = ref('contrat')
const renouvellementEssaiDebut = ref('')
const filters = ref({ numero: '', matricule: '', nom: '', type: '', departement: '', poste: '' })
const sortKey = ref('id')
const sortDir = ref('asc')
const pagination = ref({ page: 1, last_page: 1, total: 0 })
const clotureId = ref(null)
const clotureDate = ref('')
const messageCloture = ref('')
const statutId = ref(null)
const nouveauStatut = ref('en_cours')
const messageStatut = ref('')

const formatDate = (d) => {
  if (!d) return ''
  return String(d).split('T')[0]
}

const currentEnd = (c) => c.periode_essai_fin || c.date_fin

const ouvrirRenouv = (c) => {
  renouvellementId.value = c.id
  renouvellement.value = { duree_jours: 0, duree_mois: 0, duree_ans: 0 }
  renouvellementCible.value = 'contrat'
  renouvellementEssaiDebut.value = ''
  message.value = ''
}

const annulerRenouv = () => {
  renouvellementId.value = null
  renouvellement.value = { duree_jours: 0, duree_mois: 0, duree_ans: 0 }
  message.value = ''
}

const confirmerRenouv = async (c) => {
  const d = renouvellement.value
  if (![d.duree_jours, d.duree_mois, d.duree_ans].some((v) => v && v > 0)) {
    message.value = 'Indique une durée (jours, mois ou années)'
    return
  }
  const target = renouvellementCible.value
  let payload = {}
  if (target === 'contrat') {
    payload = prolongerContrat(c, d)
  } else {
    payload = prolongerEssai(c, d, renouvellementEssaiDebut.value)
  }
  if (!payload.date_debut || !payload.date_fin) {
    message.value = 'Impossible de calculer les nouvelles dates'
    return
  }
  try {
    await api.put(`/v1/contrats/${c.id}`, {
      employe_id: c.employe_id,
      type_contrat: c.type_contrat,
      date_debut: payload.date_debut,
      date_fin: payload.date_fin,
      periode_essai_debut: payload.periode_essai_debut || null,
      periode_essai_fin: payload.periode_essai_fin || null,
      renouvelable: true,
      salaire_base: c.salaire_base ?? 0,
      numero: c.numero
    })
    banner.value = 'Contrat renouvelé'
    message.value = ''
    annulerRenouv()
    await fetchContrats()
  } catch (e) {
    message.value = 'Erreur lors du renouvellement'
  }
}

import { parseISO, intervalToDuration, formatDuration } from 'date-fns'
import { fr } from 'date-fns/locale'

function duree(c) {
  const start = c.periode_essai_debut || c.date_debut
  const end   = c.periode_essai_fin   || c.date_fin
  if (!start || !end) return '—'

  const s = parseISO(start)
  const e = parseISO(end)
  if (isNaN(s) || isNaN(e) || e <= s) return '—'

  const d = intervalToDuration({ start: s, end: e })
  // Exemple: "6 mois" / "1 an 2 mois" / "10 jours"
  return formatDuration(d, { locale: fr })
}


const fetchContrats = async () => {
  loading.value = true
  const params = filterEmploye.value ? { employe_id: filterEmploye.value, page: pagination.value.page } : { page: pagination.value.page }
  const { data } = await api.get('/v1/contrats', { params })
  contrats.value = data.data || []
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
  loading.value = false
}

const debouncedFetchContrats = debounce(fetchContrats, 300)

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes')
  employes.value = data.data || []
}

const telechargerPdf = async (id) => {
  if (!id) return
  try {
    const { data, headers } = await api.get(`/v1/contrats/${id}/pdf`, {
      responseType: 'blob'
    })
    const blob = new Blob([data], { type: headers['content-type'] || 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `contrat_${id}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    banner.value = 'Impossible de générer le PDF'
  }
}

const addDuration = (start, d) => {
  const date = new Date(start)
  if (d.duree_ans) date.setFullYear(date.getFullYear() + Number(d.duree_ans))
  if (d.duree_mois) date.setMonth(date.getMonth() + Number(d.duree_mois))
  if (d.duree_jours) date.setDate(date.getDate() + Number(d.duree_jours))
  return date.toISOString().slice(0, 10)
}

const prolongerContrat = (c, d) => {
  const start = formatDate(c.date_fin || c.periode_essai_fin || c.date_debut)
  const end = start ? addDuration(start, d) : ''
  return { date_debut: start, date_fin: end, periode_essai_debut: null, periode_essai_fin: null }
}

const prolongerEssai = (c, d, essaiStartOverride) => {
  const essaiStart = essaiStartOverride || formatDate(c.periode_essai_fin || c.periode_essai_debut || c.date_debut)
  const essaiEnd = essaiStart ? addDuration(essaiStart, d) : ''
  return {
    date_debut: formatDate(c.date_debut),
    date_fin: formatDate(c.date_fin),
    periode_essai_debut: essaiStart,
    periode_essai_fin: essaiEnd
  }
}

const ouvrirCloture = (c) => {
  clotureId.value = c.id
  clotureDate.value = formatDate(c.date_fin) || formatDate(new Date())
  messageCloture.value = ''
}

const annulerCloture = () => {
  clotureId.value = null
  clotureDate.value = ''
  messageCloture.value = ''
}

const confirmerCloture = async (c) => {
  if (!clotureDate.value) {
    messageCloture.value = 'Date de fin requise'
    return
  }
  try {
    await api.put(`/v1/contrats/${c.id}`, {
      employe_id: c.employe_id,
      type_contrat: c.type_contrat,
      date_debut: formatDate(c.date_debut),
      date_fin: clotureDate.value,
      periode_essai_debut: formatDate(c.periode_essai_debut),
      periode_essai_fin: formatDate(c.periode_essai_fin),
      renouvelable: c.renouvelable,
      salaire_base: c.salaire_base ?? 0,
      numero: c.numero,
      statut: 'termine'
    })
    banner.value = 'Contrat clôturé'
    annulerCloture()
    await fetchContrats()
  } catch (e) {
    messageCloture.value = 'Erreur lors de la clôture'
  }
}

const ouvrirStatut = (c) => {
  statutId.value = c.id
  nouveauStatut.value = c.statut || 'en_cours'
  messageStatut.value = ''
}

const annulerStatut = () => {
  statutId.value = null
  nouveauStatut.value = 'en_cours'
  messageStatut.value = ''
}

const confirmerStatut = async (c) => {
  if (!nouveauStatut.value) {
    messageStatut.value = 'Choisis un statut'
    return
  }
  try {
    await api.put(`/v1/contrats/${c.id}`, {
      employe_id: c.employe_id,
      type_contrat: c.type_contrat,
      date_debut: formatDate(c.date_debut),
      date_fin: formatDate(c.date_fin),
      periode_essai_debut: formatDate(c.periode_essai_debut),
      periode_essai_fin: formatDate(c.periode_essai_fin),
      renouvelable: c.renouvelable,
      salaire_base: c.salaire_base ?? 0,
      numero: c.numero,
      statut: nouveauStatut.value
    })
    banner.value = 'Statut mis à jour'
    annulerStatut()
    await fetchContrats()
  } catch (e) {
    messageStatut.value = 'Erreur mise à jour statut'
  }
}

const contratsFiltres = computed(() => {
  const f = filters.value
  const toStr = (v) => String(v || '').toLowerCase()
  let list = contrats.value.filter((c) => {
    return (
      toStr(c.numero).includes(toStr(f.numero)) &&
      toStr(c.employe?.matricule).includes(toStr(f.matricule)) &&
      (`${toStr(c.employe?.nom)} ${toStr(c.employe?.prenom)}`).includes(toStr(f.nom)) &&
      toStr(c.type_contrat).includes(toStr(f.type)) &&
      toStr(c.employe?.departement?.nom).includes(toStr(f.departement)) &&
      toStr(c.employe?.poste?.nom).includes(toStr(f.poste))
    )
  })
  const key = sortKey.value
  const dir = sortDir.value
  list = [...list].sort((a, b) => {
    const va = getSortVal(a, key)
    const vb = getSortVal(b, key)
    if (va < vb) return dir === 'asc' ? -1 : 1
    if (va > vb) return dir === 'asc' ? 1 : -1
    return 0
  })
  return list
})

const getSortVal = (c, key) => {
  const toStr = (v) => String(v || '').toLowerCase()
  switch (key) {
    case 'numero':
      return toStr(c.numero)
    case 'matricule':
      return toStr(c.employe?.matricule)
    case 'nom':
      return `${toStr(c.employe?.nom)} ${toStr(c.employe?.prenom)}`
    case 'type':
      return toStr(c.type_contrat)
    case 'date_debut':
      return toStr(c.date_debut)
    case 'renouvellement':
      return toStr(currentEnd(c))
    case 'departement':
      return toStr(c.employe?.departement?.nom)
    case 'statut':
      return toStr(c.statut)
    case 'poste':
      return toStr(c.employe?.poste?.nom)
    case 'id':
    default:
      return Number(c.id) || 0
  }
}

const setSort = (key) => {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
}

const sortLabel = (key) => (sortKey.value === key ? (sortDir.value === 'asc' ? '▲' : '▼') : '')

const resetFilters = () => {
  filters.value = { numero: '', matricule: '', nom: '', type: '', departement: '', poste: '' }
}

const nextPage = () => {
  if (pagination.value.page < pagination.value.last_page) {
    pagination.value.page++
    fetchContrats()
  }
}
const prevPage = () => {
  if (pagination.value.page > 1) {
    pagination.value.page--
    fetchContrats()
  }
}

onMounted(async () => {
  await fetchEmployes()
  await fetchContrats()
})
</script>

<style scoped>
.filters-card {
  background: #fff;
  padding: 10px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
}
.loading {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #94a3b8;
  font-size: 14px;
}
.spinner {
  width: 18px;
  height: 18px;
  border: 2px solid #e2e8f0;
  border-top: 2px solid #0ea5e9;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.loading-overlay {
  position: relative;
  min-height: 240px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
}
.spinner-big {
  width: 46px;
  height: 46px;
  border: 4px solid #e2e8f0;
  border-top: 4px solid #0ea5e9;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
