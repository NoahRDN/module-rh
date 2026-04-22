<template>
  <div class="create-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Employee files</p>
        <h1>Nouveau document</h1>
        <p class="hero-subtitle">
          Ajoutez une pièce RH (CIN, diplôme, contrat...) à un collaborateur et suivez l’expiration si
          besoin.
        </p>

        <div class="hero-pills">
          <span class="pill">Archivage</span>
          <span class="pill">Conformité</span>
          <span class="pill">Expiration</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn btn-secondary" to="/documents">Retour</RouterLink>
            <button class="btn" type="button" @click="submit" :disabled="loading">
              {{ loading ? 'Upload...' : 'Uploader' }}
            </button>
          </div>

          <div v-if="message" class="status-banner danger">
            <span class="status-dot"></span>
            <span>{{ message }}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="card section-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Document upload</p>
          <h2>Informations</h2>
        </div>
        <span class="section-chip">Création</span>
      </div>

      <form class="fields-grid" @submit.prevent="submit" enctype="multipart/form-data">
        <label class="field-card">
          <span class="field-label">Employé</span>
          <select class="select" v-model="form.employe_id" required>
            <option value="">Sélectionner</option>
            <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Type de document</span>
          <select class="select" v-model="form.type_document" required>
            <option v-for="t in typeDocuments" :key="t" :value="t">{{ t }}</option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Date d'expiration</span>
          <input class="input" type="date" v-model="form.date_expiration" />
        </label>

        <label class="field-card full">
          <span class="field-label">Fichier</span>
          <input class="input" type="file" @change="onFile" required />
        </label>

        <div class="submit-row">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? 'Upload...' : 'Uploader' }}</button>
          <RouterLink class="btn btn-secondary" to="/documents">Annuler</RouterLink>
        </div>
      </form>
    </section>
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
