<template>
  <div class="page-header">
    <div class="page-title">
      <h1>Documents RH</h1>
      <span>Pièces jointes des employés</span>
    </div>
    <select class="select" v-model="filterEmploye" @change="fetchDocs">
      <option value="">Tous les employés</option>
      <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
    </select>
  </div>

  <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 18px;">
    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>Employé</th>
            <th>Type</th>
            <th>Fichier</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in docs" :key="d.id">
            <td>{{ d.employe?.matricule || '—' }}</td>
            <td>{{ d.type_document }}</td>
            <td><a :href="d.url" target="_blank" rel="noopener">Ouvrir</a></td>
          </tr>
          <tr v-if="!docs.length">
            <td colspan="3" class="muted">Aucun document</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card">
      <div class="page-title">
        <h1>Uploader</h1>
        <span>Nouveau document</span>
      </div>
      <form class="grid" style="margin-top: 10px; gap: 10px;" @submit.prevent="uploadDoc" enctype="multipart/form-data">
        <select class="select" v-model="form.employe_id" required>
          <option value="">Employé</option>
          <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
        </select>
        <input class="input" v-model="form.type_document" placeholder="Type document" required />
        <input class="input" v-model="form.date_expiration" placeholder="Date expiration (optionnel)" />
        <input class="input" type="file" @change="onFile" required />
        <button class="btn" type="submit">Uploader</button>
        <p class="muted" v-if="message">{{ message }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const docs = ref([])
const employes = ref([])
const filterEmploye = ref('')
const message = ref('')
const fileRef = ref(null)
const form = ref({
  employe_id: '',
  type_document: 'CIN',
  date_expiration: ''
})

const fetchDocs = async () => {
  const params = filterEmploye.value ? { employe_id: filterEmploye.value } : {}
  const { data } = await api.get('/v1/documents', { params })
  docs.value = data.data || []
}

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes')
  employes.value = data.data || []
}

const onFile = (e) => {
  fileRef.value = e.target.files?.[0] || null
}

const uploadDoc = async () => {
  if (!fileRef.value) {
    message.value = 'Choisis un fichier'
    return
  }
  const fd = new FormData()
  fd.append('employe_id', form.value.employe_id)
  fd.append('type_document', form.value.type_document)
  fd.append('date_expiration', form.value.date_expiration || '')
  fd.append('fichier', fileRef.value)
  try {
    await api.post('/v1/documents/upload', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    message.value = 'Document uploadé'
    fileRef.value = null
    await fetchDocs()
  } catch (e) {
    message.value = 'Erreur upload'
  }
}

onMounted(async () => {
  await fetchEmployes()
  await fetchDocs()
})
</script>
