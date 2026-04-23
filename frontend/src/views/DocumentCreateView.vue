<template>
  <div class="create-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">{{ isEditing ? 'Employee file update' : 'Employee files' }}</p>
        <h1>{{ pageTitle }}</h1>
        <p class="hero-subtitle">
          {{ pageSubtitle }}
        </p>

        <div class="hero-pills">
          <span class="pill">PDF et images</span>
          <span class="pill">Sélection multiple</span>
          <span class="pill">Traçabilité</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn btn-secondary" :to="backTarget">Retour</RouterLink>
            <button class="btn" type="button" @click="submit" :disabled="loading">
              {{ loading ? submitLoadingLabel : submitLabel }}
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
          <p class="section-kicker">{{ isEditing ? 'Document update' : 'Document upload' }}</p>
          <h2>{{ isEditing ? 'Modifier la pièce' : 'Informations' }}</h2>
        </div>
        <span class="section-chip">{{ isEditing ? 'Édition' : 'Création' }}</span>
      </div>

      <form class="fields-grid" @submit.prevent="submit" enctype="multipart/form-data">
        <label class="field-card">
          <span class="field-label">Employé</span>
          <select class="select" v-model="form.employe_id" required>
            <option value="">Sélectionner</option>
            <option v-for="emp in employes" :key="emp.id" :value="String(emp.id)">
              {{ emp.matricule }} - {{ emp.nom }} {{ emp.prenom || '' }}
            </option>
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

        <div class="field-card full upload-field">
          <div class="upload-heading">
            <span class="field-label">{{ isEditing ? 'Upload fichier(s)' : 'Upload fichiers' }}</span>
            <span class="upload-summary">{{ selectionSummary }}</span>
          </div>

          <input
            class="input upload-input"
            type="file"
            accept=".pdf,image/*"
            multiple
            @change="onFileChange"
          />

          <span class="field-help">{{ fileHelp }}</span>

          <div v-if="isEditing && currentGroupDocuments.length" class="preview-section">
            <p class="preview-section-title">{{ currentSectionTitle }}</p>
            <div class="preview-grid preview-grid-current">
              <article
                v-for="item in currentGroupDocuments"
                :key="item.id"
                class="preview-card preview-card-current preview-card-vignette"
                :title="item.name"
              >
                <div class="preview-thumb">
                  <img
                    v-if="item.kind === 'image' && item.previewUrl"
                    class="preview-image"
                    :src="item.previewUrl"
                    :alt="item.name"
                  />
                  <div v-else class="preview-file-tile">
                    <span class="preview-file-ext">{{ item.loading ? '...' : item.extension || 'PDF' }}</span>
                  </div>
                </div>
                <p class="preview-caption">{{ item.name }}</p>
                <div class="preview-actions-row">
                  <a
                    class="preview-action preview-action-secondary"
                    :href="item.openUrl || '#'"
                    target="_blank"
                    rel="noopener"
                    :aria-disabled="!item.openUrl"
                    @click.prevent="openCurrentDocument(item)"
                  >
                    Ouvrir
                  </a>
                  <button
                    class="preview-action"
                    type="button"
                    @click="removeCurrentDocument(item)"
                    :disabled="deletingDocumentId === item.id"
                  >
                    {{ deletingDocumentId === item.id ? '...' : 'Supprimer' }}
                  </button>
                </div>
              </article>
            </div>
          </div>

          <div v-if="selectedFiles.length" class="preview-section">
            <p class="preview-section-title">
              {{ isEditing ? 'Fichiers sélectionnés' : 'Fichiers à importer' }}
            </p>

            <div class="preview-grid">
              <article v-for="(item, index) in selectedFiles" :key="item.id" class="preview-card preview-card-vignette" :title="item.file.name">
                <div class="preview-thumb">
                  <img
                    v-if="item.kind === 'image'"
                    class="preview-image"
                    :src="item.previewUrl"
                    :alt="item.file.name"
                  />
                  <div v-else class="preview-file-tile">
                    <span class="preview-file-ext">{{ item.extension }}</span>
                  </div>
                  <span class="preview-role">{{ previewRole(index) }}</span>
                </div>

                <button class="preview-action" type="button" @click="removeSelectedFile(item.id)">
                  Supprimer
                </button>
              </article>
            </div>
          </div>
        </div>

        <div class="submit-row">
          <button class="btn" type="submit" :disabled="loading">
            {{ loading ? submitLoadingLabel : submitLabel }}
          </button>
          <RouterLink class="btn btn-secondary" :to="backTarget">Annuler</RouterLink>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import api from '../services/api'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const message = ref('')
const employes = ref([])
const typeDocuments = ref(['CIN', 'Diplome', 'CV', 'Contrat', 'Attestation', 'Autre'])
const selectedFiles = ref([])
const currentGroupDocuments = ref([])
const currentGroupKey = ref(String(route.query.group_key || ''))
const currentGroupUuid = ref(String(route.query.group_uuid || ''))
const deletingDocumentId = ref(null)
const form = ref({
  employe_id: String(route.query.employe_id || ''),
  type_document: 'CIN',
  date_expiration: '',
})

const isEditing = computed(() => Boolean(route.params.id))
const backTarget = computed(() => String(route.query.return || '/documents'))
const pageTitle = computed(() => (isEditing.value ? 'Modifier un groupe de documents' : 'Nouveau document'))
const pageSubtitle = computed(() => (
  isEditing.value
    ? 'Les métadonnées seront appliquées à tous les fichiers du groupe. Les nouveaux fichiers sélectionnés seront ajoutés au même lot.'
    : 'Ajoutez une ou plusieurs pièces justificatives RH pour un même type de document et appliquez la même date d’expiration à l’ensemble.'
))
const currentSectionTitle = computed(() => (
  currentGroupDocuments.value.length > 1 ? 'Groupe actuel' : 'Document actuel'
))
const selectionSummary = computed(() => {
  if (selectedFiles.value.length === 0) {
    return 'Aucune sélection'
  }
  return `${selectedFiles.value.length} fichier${selectedFiles.value.length > 1 ? 's' : ''}`
})
const submitLabel = computed(() => {
  if (isEditing.value) {
    return 'Enregistrer le groupe'
  }
  return selectedFiles.value.length > 1 ? `Uploader ${selectedFiles.value.length} fichiers` : 'Uploader'
})
const submitLoadingLabel = computed(() => (isEditing.value ? 'Enregistrement...' : 'Upload...'))
const fileHelp = computed(() => (
  isEditing.value
    ? 'Sélection multiple activée. Les métadonnées seront appliquées au groupe et les fichiers sélectionnés seront ajoutés à ce même groupe.'
    : 'Sélection multiple activée. Chaque fichier sera enregistré comme un document distinct avec le même type et la même date d’expiration.'
))

const loadEmployes = async () => {
  const { data } = await api.get('/v1/employes', { params: { all: true } })
  employes.value = data.data || data || []
}

const loadTypes = async () => {
  try {
    const { data } = await api.get('/v1/documents/types')
    if (Array.isArray(data.data) && data.data.length) {
      typeDocuments.value = data.data
      if (!form.value.type_document || !typeDocuments.value.includes(form.value.type_document)) {
        form.value.type_document = data.data[0]
      }
    }
  } catch (e) {
    // conserve les valeurs par défaut
  }
}

const detectFileKind = (name = '', mime = '') => {
  const lowered = String(name).toLowerCase()
  if (mime.startsWith('image/') || /\.(jpg|jpeg|png|webp|gif|bmp|svg)$/.test(lowered)) {
    return 'image'
  }
  if (mime === 'application/pdf' || lowered.endsWith('.pdf')) {
    return 'pdf'
  }
  return 'file'
}

const detectExtension = (name = '') => {
  const ext = String(name).split('.').pop() || 'FILE'
  return ext.toUpperCase()
}

const createFileEntry = (file) => {
  const kind = detectFileKind(file.name, file.type || '')
  return {
    id: `${file.name}-${file.size}-${file.lastModified}`,
    file,
    kind,
    extension: detectExtension(file.name),
    previewUrl: kind === 'image' ? URL.createObjectURL(file) : '',
  }
}

const revokeEntry = (entry) => {
  if (entry?.previewUrl) {
    URL.revokeObjectURL(entry.previewUrl)
  }
}

const revokeCurrentGroupDocuments = () => {
  currentGroupDocuments.value.forEach((document) => {
    if (document?.previewUrl) {
      URL.revokeObjectURL(document.previewUrl)
    }
    if (document?.openUrl && document.openUrl !== document.previewUrl) {
      URL.revokeObjectURL(document.openUrl)
    }
  })
}

const clearSelectedFiles = () => {
  selectedFiles.value.forEach(revokeEntry)
  selectedFiles.value = []
}

const mergeSelectedFiles = (files) => {
  const existing = new Set(selectedFiles.value.map((item) => item.id))
  const additions = []

  files.forEach((file) => {
    const entry = createFileEntry(file)
    if (existing.has(entry.id)) {
      revokeEntry(entry)
      return
    }
    additions.push(entry)
    existing.add(entry.id)
  })

  selectedFiles.value = [...selectedFiles.value, ...additions]
}

const onFileChange = (event) => {
  const files = Array.from(event.target.files || [])
  if (files.length) {
    mergeSelectedFiles(files)
  }
  event.target.value = ''
}

const removeSelectedFile = (id) => {
  const entry = selectedFiles.value.find((item) => item.id === id)
  if (!entry) {
    return
  }
  revokeEntry(entry)
  selectedFiles.value = selectedFiles.value.filter((item) => item.id !== id)
}

const sortDocuments = (documents) => [...documents].sort((a, b) => {
  const dateDiff = new Date(b.created_at || 0) - new Date(a.created_at || 0)
  if (dateDiff !== 0) {
    return dateDiff
  }
  return Number(b.id || 0) - Number(a.id || 0)
})

const buildDocumentGroupKey = (doc) => {
  if (doc?.group_uuid) {
    return `uuid:${doc.group_uuid}`
  }

  const createdAt = String(doc?.created_at || doc?.date_importation || '').split('.')[0]
  return [
    doc?.employe_id || '',
    doc?.type_document || '',
    doc?.date_expiration ? String(doc.date_expiration).split('T')[0] : '',
    createdAt,
  ].join('::')
}

const mapExistingDocument = (document) => ({
  id: document.id,
  group_uuid: document.group_uuid || '',
  key: buildDocumentGroupKey(document),
  name: document.nom_fichier || 'document',
  kind: document.preview_type || detectFileKind(document.nom_fichier || '', ''),
  extension: String(document.extension || detectExtension(document.nom_fichier || '')).toUpperCase(),
  previewUrl: '',
  openUrl: '',
  loading: true,
})

const makeGroupUuid = () => globalThis.crypto?.randomUUID?.() || `group-${Date.now()}-${Math.random().toString(16).slice(2)}`

const fetchDocumentBlob = async (documentId) => api.get(`/v1/documents/${documentId}/download`, { responseType: 'blob' })

const hydrateExistingDocument = async (document) => {
  const item = mapExistingDocument(document)

  try {
    const response = await fetchDocumentBlob(document.id)
    const blob = response.data instanceof Blob
      ? response.data
      : new Blob([response.data], { type: response.headers?.['content-type'] || 'application/octet-stream' })
    const objectUrl = URL.createObjectURL(blob)

    item.openUrl = objectUrl
    item.previewUrl = item.kind === 'image' ? objectUrl : ''
  } catch (error) {
    item.openUrl = ''
    item.previewUrl = ''
  } finally {
    item.loading = false
  }

  return item
}

const loadDocumentGroup = async (documentId = route.params.id) => {
  if (!isEditing.value || !documentId) {
    return
  }

  const { data } = await api.get(`/v1/documents/${documentId}`)
  form.value = {
    employe_id: String(data.employe_id || ''),
    type_document: data.type_document || typeDocuments.value[0] || 'CIN',
    date_expiration: data.date_expiration ? String(data.date_expiration).split('T')[0] : '',
  }

  const initialKey = String(route.query.group_key || currentGroupKey.value || buildDocumentGroupKey(data))
  const initialUuid = String(route.query.group_uuid || currentGroupUuid.value || data.group_uuid || '')
  const { data: employeData } = await api.get(`/v1/employes/${data.employe_id}`)
  const employeeDocuments = sortDocuments(employeData.documents || [])
  const groupedDocuments = new Map()

  employeeDocuments.forEach((document) => {
    const key = buildDocumentGroupKey(document)
    if (!groupedDocuments.has(key)) {
      groupedDocuments.set(key, [])
    }
    groupedDocuments.get(key).push(document)
  })

  const resolvedGroup =
    groupedDocuments.get(initialKey) ||
    [...groupedDocuments.values()].find((documents) =>
      documents.some((document) => Number(document.id) === Number(data.id) || (initialUuid && document.group_uuid === initialUuid)),
    ) ||
    [data]

  const sortedGroup = sortDocuments(resolvedGroup)
  const primaryDocument = sortedGroup[0] || data

  revokeCurrentGroupDocuments()
  currentGroupDocuments.value = await Promise.all(sortedGroup.map(hydrateExistingDocument))
  currentGroupKey.value = buildDocumentGroupKey(primaryDocument)
  currentGroupUuid.value = primaryDocument.group_uuid || initialUuid || makeGroupUuid()
}

const openCurrentDocument = (document) => {
  if (!document?.openUrl) {
    return
  }

  window.open(document.openUrl, '_blank', 'noopener')
}

const removeCurrentDocument = async (document) => {
  if (!document?.id || deletingDocumentId.value === document.id) {
    return
  }

  const confirmed = window.confirm(`Supprimer le document "${document.name}" ?`)
  if (!confirmed) {
    return
  }

  deletingDocumentId.value = document.id
  message.value = ''

  try {
    await api.delete(`/v1/documents/${document.id}`)

    const remainingDocuments = currentGroupDocuments.value.filter((item) => item.id !== document.id)
    if (!remainingDocuments.length) {
      router.push(backTarget.value)
      return
    }

    const nextDocument = remainingDocuments[0]
    const nextQuery = {
      ...route.query,
      group_key: currentGroupKey.value,
      group_uuid: currentGroupUuid.value || nextDocument.group_uuid || '',
    }

    if (String(route.params.id) !== String(nextDocument.id)) {
      await router.replace({
        name: 'document-edit',
        params: { id: nextDocument.id },
        query: nextQuery,
      })
    }

    await loadDocumentGroup(nextDocument.id)
  } catch (error) {
    message.value = error.response?.data?.message || 'Erreur lors de la suppression du document.'
  } finally {
    deletingDocumentId.value = null
  }
}

const previewRole = (index) => {
  if (isEditing.value) {
    return 'Ajout'
  }
  return 'Import'
}

const formatFileSize = (size) => {
  const bytes = Number(size) || 0
  if (bytes < 1024) {
    return `${bytes} o`
  }
  const kb = bytes / 1024
  if (kb < 1024) {
    return `${kb.toFixed(1)} Ko`
  }
  return `${(kb / 1024).toFixed(1)} Mo`
}

const submit = async () => {
  if (!isEditing.value && selectedFiles.value.length === 0) {
    message.value = 'Choisissez au moins un fichier à importer.'
    return
  }

  if (isEditing.value && currentGroupDocuments.value.length === 0) {
    message.value = 'Aucun document de groupe à modifier.'
    return
  }

  loading.value = true
  message.value = ''

  try {
    if (isEditing.value) {
      const groupUuid = currentGroupUuid.value || makeGroupUuid()

      await Promise.all(currentGroupDocuments.value.map(async (document) => {
        const updateData = new FormData()
        updateData.append('employe_id', form.value.employe_id)
        updateData.append('group_uuid', groupUuid)
        updateData.append('type_document', form.value.type_document)
        updateData.append('date_expiration', form.value.date_expiration || '')
        updateData.append('_method', 'PUT')

        await api.post(`/v1/documents/${document.id}`, updateData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
      }))

      if (selectedFiles.value.length > 0) {
        const uploadData = new FormData()
        uploadData.append('employe_id', form.value.employe_id)
        uploadData.append('group_uuid', groupUuid)
        uploadData.append('type_document', form.value.type_document)
        uploadData.append('date_expiration', form.value.date_expiration || '')
        selectedFiles.value.forEach((item) => {
          uploadData.append('fichiers[]', item.file)
        })

        await api.post('/v1/documents/upload', uploadData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
      }
    } else {
      const fd = new FormData()
      fd.append('employe_id', form.value.employe_id)
      fd.append('type_document', form.value.type_document)
      fd.append('date_expiration', form.value.date_expiration || '')
      selectedFiles.value.forEach((item) => {
        fd.append('fichiers[]', item.file)
      })

      await api.post('/v1/documents/upload', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    }

    router.push(backTarget.value)
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur lors de l’enregistrement du document.'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    await Promise.all([loadEmployes(), loadTypes()])
    await loadDocumentGroup()
  } catch (e) {
    message.value = e.response?.data?.message || 'Impossible de charger le document.'
  }
})

watch(
  () => route.params.id,
  async (documentId, previousId) => {
    if (!isEditing.value || !documentId || documentId === previousId) {
      return
    }

    message.value = ''
    await loadDocumentGroup(documentId)
  },
)

onBeforeUnmount(() => {
  revokeCurrentGroupDocuments()
  clearSelectedFiles()
})
</script>

<style scoped>
.upload-field {
  gap: 14px;
}

.upload-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.upload-summary {
  color: var(--muted);
  font-size: 0.9rem;
  font-weight: 700;
}

.upload-input {
  padding: 10px 12px;
}

.field-help {
  color: var(--muted);
  font-size: 0.92rem;
}

.preview-section {
  display: grid;
  gap: 10px;
}

.preview-section-title {
  margin: 0;
  color: var(--text);
  font-weight: 700;
}

.preview-grid {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: flex-start;
  gap: 12px;
}

.preview-grid-current {
  gap: 10px;
}

.preview-card {
  display: grid;
  gap: 8px;
  width: 146px;
  padding: 8px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: rgba(255, 255, 255, 0.72);
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
}

.preview-card-current {
  background: rgba(37, 99, 235, 0.05);
  width: 156px;
}

.preview-card-vignette {
  gap: 10px;
}

.preview-thumb {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 92px;
  border-radius: 12px;
  background: linear-gradient(180deg, #2563eb, #1d4ed8);
  overflow: hidden;
}

.preview-image {
  width: 100%;
  height: 92px;
  object-fit: cover;
  display: block;
}

.preview-file-tile {
  width: calc(100% - 18px);
  height: 68px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.95);
}

.preview-file-ext {
  color: #1e3a8a;
  font-size: 1rem;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.preview-role {
  position: absolute;
  top: 8px;
  right: 8px;
  padding: 4px 8px;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.82);
  color: white;
  font-size: 0.72rem;
  font-weight: 700;
}

.preview-caption {
  margin: 0;
  color: var(--text);
  font-weight: 700;
  font-size: 0.82rem;
  line-height: 1.2;
  text-align: center;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 32px;
}

.preview-action {
  width: 100%;
  border: 0;
  border-radius: 10px;
  padding: 8px 10px;
  background: #c6281d;
  color: #fff;
  font-weight: 700;
  font-size: 0.84rem;
  cursor: pointer;
  text-align: center;
  text-decoration: none;
}

.preview-action-secondary {
  background: rgba(15, 23, 42, 0.09);
  color: var(--text);
}

.preview-actions-row {
  display: grid;
  gap: 8px;
}

@media (max-width: 720px) {
  .preview-grid {
    gap: 10px;
  }

  .preview-card,
  .preview-card-current {
    width: 136px;
  }
}
</style>
