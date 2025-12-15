<template>
  <div class="page">
    <div class="page-header">
      <h1>Uploader un document</h1>
      <RouterLink class="btn btn-secondary btn-sm" to="/documents">← Retour</RouterLink>
    </div>
    <div class="card">
      <form class="grid gap-3" @submit.prevent="submit" enctype="multipart/form-data">
        <div class="grid gap-1">
          <label class="text-sm text-slate-500">Employé</label>
          <select class="select" v-model="form.employe_id" required>
            <option value="">Employé</option>
            <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
          </select>
        </div>
        <div class="grid gap-1">
          <label class="text-sm text-slate-500">Type de document</label>
          <select class="select" v-model="form.type_document" required>
            <option v-for="t in typeDocuments" :key="t" :value="t">{{ t }}</option>
          </select>
        </div>
        <div class="grid gap-1">
          <label class="text-sm text-slate-500">Date d'expiration (optionnel)</label>
          <input class="input" type="date" v-model="form.date_expiration" />
        </div>
        <div class="grid gap-1">
          <label class="text-sm text-slate-500">Fichier</label>
          <input class="input" type="file" @change="onFile" required />
        </div>
        <div class="flex gap-2 items-center">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? 'Upload...' : 'Uploader' }}</button>
          <span class="muted" v-if="message">{{ message }}</span>
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
const loading = ref(false)
const message = ref('')
const employes = ref([])
const typeDocuments = ref(['CIN', 'Diplome', 'CV', 'Contrat', 'Attestation', 'Autre'])
const fileRef = ref(null)
const form = ref({ employe_id: '', type_document: 'CIN', date_expiration: '' })

const loadEmployes = async () => {
  const { data } = await api.get('/v1/employes', { params: { active_only: true } })
  employes.value = data.data || data || []
}

const loadTypes = async () => {
  try {
    const { data } = await api.get('/v1/documents/types')
    if (Array.isArray(data.data) && data.data.length) {
      typeDocuments.value = data.data
      form.value.type_document = data.data[0]
    }
  } catch (e) {
    // defaults
  }
}

const onFile = (e) => {
  fileRef.value = e.target.files?.[0] || null
}

const submit = async () => {
  if (!fileRef.value) {
    message.value = 'Choisis un fichier'
    return
  }
  loading.value = true
  message.value = ''
  const fd = new FormData()
  fd.append('employe_id', form.value.employe_id)
  fd.append('type_document', form.value.type_document)
  fd.append('date_expiration', form.value.date_expiration || '')
  fd.append('fichier', fileRef.value)
  try {
    await api.post('/v1/documents/upload', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    router.push('/documents')
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur upload'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadEmployes()
  await loadTypes()
})
</script>

<style scoped>
.page { padding: 20px; max-width: 800px; margin: 0 auto; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; background: #fff; box-shadow: 0 6px 20px rgba(15,23,42,0.08); }
.input, .select { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; }
.btn { padding: 10px 16px; border: none; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; cursor: pointer; }
.btn-secondary { background: #e2e8f0; color: #0f172a; }
.muted { color: #94a3b8; }
</style>
