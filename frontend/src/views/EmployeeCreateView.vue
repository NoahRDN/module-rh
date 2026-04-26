<template>
  <div class="employee-create-page">
    <section class="hero hero-band hero-shared">
      <div class="hero-copy">
        <p class="hero-kicker">Employee onboarding</p>
        <h1>Nouvel employé</h1>
        <p class="hero-subtitle">
          Centralisez l’identité, les informations de contact, le rattachement métier et les pièces justificatives
          dans un seul formulaire de création.
        </p>

        <div class="hero-pills">
          <span class="pill">Identité</span>
          <span class="pill">Contact</span>
          <span class="pill">Documents</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink to="/employes" class="btn btn-secondary">
              <AppIcon name="history" :size="18" />
              <span>Retour à l’annuaire</span>
            </RouterLink>
            <button class="btn" type="button" @click="createEmploye" :disabled="saving || loadingRefs">
              <AppIcon name="save" :size="18" />
              <span>{{ saving ? 'Enregistrement...' : 'Créer l’employé' }}</span>
            </button>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Postes disponibles:
              <strong>{{ formatInteger(postes.length) }}</strong>
            </p>
            <p class="hero-meta">
              Compte associé:
              <strong>Créé automatiquement</strong>
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="metric-grid">
      <article v-for="metric in metricCards" :key="metric.label" class="metric-card">
        <span class="metric-chip">{{ metric.tag }}</span>
        <p class="metric-label">{{ metric.label }}</p>
        <p class="metric-value">{{ metric.value }}</p>
        <p class="metric-caption">{{ metric.caption }}</p>
      </article>
    </section>

    <div v-if="loadingRefs" class="card loading-card">
      <p class="loading-title">Chargement des références…</p>
      <p class="muted">Les postes et types de documents sont en cours de synchronisation.</p>
    </div>

    <template v-else>
      <section class="content-grid">
        <article class="card section-card form-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Employee form</p>
              <h2>Informations du collaborateur</h2>
            </div>
            <span class="section-chip">Création</span>
          </div>

          <div v-if="message" class="status-banner" :class="messageType">
            <span class="status-dot"></span>
            <span>{{ message }}</span>
          </div>

          <form class="field-sections" @submit.prevent="createEmploye">
            <section class="field-group">
              <div class="field-group-head">
                <h3>Identité</h3>
                <p>Nom, prénom et identité administrative de base.</p>
              </div>

              <div class="fields-grid">
                <label class="field-card">
                  <span class="field-label">Nom</span>
                  <input v-model="form.nom" class="input" placeholder="Nom" required />
                </label>

                <label class="field-card">
                  <span class="field-label">Prénom</span>
                  <input v-model="form.prenom" class="input" placeholder="Prénom" required />
                </label>

                <label class="field-card">
                  <span class="field-label">Date de naissance</span>
                  <input v-model="form.date_naissance" class="input" type="date" />
                </label>

                <label class="field-card">
                  <span class="field-label">Date d’embauche</span>
                  <input v-model="form.date_embauche" class="input" type="date" required />
                </label>
              </div>
            </section>

            <section class="field-group">
              <div class="field-group-head">
                <h3>Contact</h3>
                <p>Coordonnées utilisées pour le compte associé et le suivi RH.</p>
              </div>

              <div class="fields-grid">
                <label class="field-card">
                  <span class="field-label">Email</span>
                  <input v-model="form.email" class="input" placeholder="Email" required type="email" />
                </label>

                <label class="field-card">
                  <span class="field-label">Téléphone</span>
                  <input v-model="form.telephone" class="input" placeholder="Téléphone" />
                </label>

                <label class="field-card full">
                  <span class="field-label">Adresse</span>
                  <input v-model="form.adresse" class="input" placeholder="Adresse" />
                </label>
              </div>
            </section>

            <section class="field-group">
              <div class="field-group-head">
                <h3>Rattachement métier</h3>
                <p>Le département sera automatiquement déduit du poste si nécessaire.</p>
              </div>

              <div class="fields-grid">
                <label class="field-card full">
                  <span class="field-label">Poste</span>
                  <select v-model="form.poste_id" class="select" required>
                    <option value="">Sélectionner un poste</option>
                    <option v-for="poste in postes" :key="poste.id" :value="poste.id">{{ poste.nom }}</option>
                  </select>
                </label>
              </div>
            </section>

            <section class="field-group">
              <div class="field-group-head">
                <h3>Photo</h3>
                <p>Ajoutez une photo pour la fiche employé et l’annuaire.</p>
              </div>

              <div class="photo-uploader">
                <img :src="photoPreview" alt="preview" class="photo-preview" />
                <div class="photo-copy">
                  <p class="photo-title">Aperçu du profil</p>
                  <p class="photo-subtitle">Formats image standard acceptés via import local.</p>
                  <input class="input" type="file" @change="onPhoto" accept="image/*" />
                </div>
              </div>
            </section>

            <section class="field-group">
              <div class="field-group-head">
                <h3>Documents justificatifs</h3>
                <p>Ajoutez un ou plusieurs groupes de pièces. Chaque groupe accepte plusieurs fichiers pour un même type.</p>
              </div>

              <div class="document-groups">
                <article v-for="(group, index) in documentGroups" :key="group.id" class="document-group-card">
                  <div class="document-group-head">
                    <div>
                      <p class="document-group-kicker">Groupe {{ index + 1 }}</p>
                      <h4>{{ group.type_document || 'Document' }}</h4>
                    </div>

                    <button
                      class="btn btn-secondary btn-sm"
                      type="button"
                      @click="removeDocumentGroup(group.id)"
                      :disabled="documentGroups.length === 1 && !group.files.length && !group.date_expiration"
                    >
                      Supprimer le groupe
                    </button>
                  </div>

                  <div class="fields-grid documents-fields-grid">
                    <label class="field-card">
                      <span class="field-label">Type de document</span>
                      <select v-model="group.type_document" class="select" required>
                        <option v-for="type in documentTypes" :key="type" :value="type">{{ type }}</option>
                      </select>
                    </label>

                    <label class="field-card">
                      <span class="field-label">Date d'expiration</span>
                      <input v-model="group.date_expiration" class="input" type="date" />
                    </label>
                  </div>

                  <div class="upload-field upload-field-inline">
                    <input
                      class="input upload-input"
                      type="file"
                      accept=".pdf,image/*"
                      multiple
                      @change="onGroupFilesChange(group.id, $event)"
                    />

                    <span class="field-help">PDF et images, sélection multiple autorisée pour ce type.</span>

                    <div v-if="group.files.length" class="preview-grid preview-grid-inline">
                      <article v-for="item in group.files" :key="item.id" class="preview-card preview-card-tiny" :title="item.file.name">
                        <div class="preview-thumb preview-thumb-tiny">
                          <img
                            v-if="item.kind === 'image'"
                            class="preview-image preview-image-tiny"
                            :src="item.previewUrl"
                            :alt="item.file.name"
                          />
                          <div v-else class="preview-file-tile preview-file-tile-tiny">
                            <span class="preview-file-ext preview-file-ext-tiny">{{ item.extension }}</span>
                          </div>
                        </div>

                        <button class="preview-action preview-action-tiny" type="button" @click="removeGroupFile(group.id, item.id)">
                          Supprimer
                        </button>
                      </article>
                    </div>
                  </div>
                </article>

                <button class="btn btn-secondary" type="button" @click="addDocumentGroup">
                  Ajouter un type de document
                </button>
              </div>
            </section>

            <div class="submit-row">
              <button class="btn" type="submit" :disabled="saving">
                <AppIcon name="save" :size="18" />
                <span>{{ saving ? 'Enregistrement...' : 'Enregistrer' }}</span>
              </button>
              <RouterLink to="/employes" class="btn btn-secondary">Annuler</RouterLink>
            </div>
          </form>
        </article>

      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'

const router = useRouter()
const postes = ref([])
const documentTypes = ref(['CIN', 'Diplome', 'CV', 'Contrat', 'Attestation', 'Autre'])
const message = ref('')
const messageType = ref('info')
const saving = ref(false)
const loadingRefs = ref(false)
const photoPreview = ref('https://via.placeholder.com/160?text=EMP')
const documentGroups = ref([])
let documentGroupSequence = 0

const form = ref({
  nom: '',
  prenom: '',
  email: '',
  telephone: '',
  adresse: '',
  date_naissance: '',
  date_embauche: '',
  poste_id: '',
  photo: '',
})

const createDocumentGroup = () => ({
  id: `document-group-${documentGroupSequence += 1}`,
  type_document: documentTypes.value[0] || 'CIN',
  date_expiration: '',
  files: [],
})

const completedFields = computed(() =>
  [
    form.value.nom,
    form.value.prenom,
    form.value.email,
    form.value.date_embauche,
    form.value.poste_id,
    form.value.telephone,
    form.value.adresse,
    form.value.date_naissance,
    form.value.photo,
  ].filter(Boolean).length,
)

const selectedDocumentsCount = computed(() =>
  documentGroups.value.reduce((total, group) => total + group.files.length, 0),
)

const selectedPosteLabel = computed(() => {
  const poste = postes.value.find((item) => String(item.id) === String(form.value.poste_id))
  return poste?.nom || 'Non sélectionné'
})

const metricCards = computed(() => [
  {
    label: 'Champs complétés',
    value: formatInteger(completedFields.value),
    caption: 'Lecture rapide de progression du formulaire',
    tag: 'Progress',
  },
  {
    label: 'Documents prêts',
    value: formatInteger(selectedDocumentsCount.value),
    caption: 'Pièces justificatives actuellement jointes au dossier',
    tag: 'Files',
  },
  {
    label: 'Compte utilisateur',
    value: form.value.email ? 'Prêt' : 'En attente',
    caption: 'Le compte employé sera généré à partir de l’email',
    tag: 'Access',
  },
  {
    label: 'Photo profil',
    value: form.value.photo ? 'Ajoutée' : 'Optionnelle',
    caption: 'Aperçu disponible avant validation',
    tag: 'Profile',
  },
])

const overviewCards = computed(() => [
  {
    label: 'Identité',
    value: `${form.value.nom || '—'} ${form.value.prenom || ''}`.trim() || 'Non renseignée',
    copy: 'Nom complet du collaborateur en cours de création.',
    tag: 'Identity',
  },
  {
    label: 'Poste sélectionné',
    value: selectedPosteLabel.value,
    copy: 'Le département sera hérité du poste si besoin.',
    tag: 'Role',
  },
  {
    label: 'Email',
    value: form.value.email || 'Non renseigné',
    copy: 'Sera utilisé pour le compte employé créé automatiquement.',
    tag: 'Access',
  },
  {
    label: 'Documents',
    value: `${formatInteger(selectedDocumentsCount.value)} pièce(s)`,
    copy: 'Total des pièces justificatives préparées pour import après création.',
    tag: 'Files',
  },
])

const notes = computed(() => [
  form.value.email
    ? 'Un compte utilisateur employé sera créé automatiquement à partir de cet email.'
    : 'Sans email, aucun compte utilisateur associé ne pourra être exploité correctement.',
  selectedDocumentsCount.value
    ? `${formatInteger(selectedDocumentsCount.value)} document(s) justificatif(s) seront importés après la création du collaborateur.`
    : 'Aucun document justificatif n’est encore attaché au dossier.',
  form.value.poste_id
    ? 'Le département sera déduit automatiquement du poste sélectionné.'
    : 'Le poste reste obligatoire pour rattacher correctement le collaborateur.',
  form.value.photo
    ? 'Une photo de profil est déjà prête pour la fiche employé.'
    : 'La photo reste facultative mais améliore la lisibilité dans l’annuaire.',
])

const fetchAllPostes = async () => {
  loadingRefs.value = true
  try {
    const collected = []
    let page = 1
    let lastPage = 1

    do {
      const { data } = await api.get('/v1/postes', { params: { page } })
      const rows = data.data || []
      collected.push(...rows)

      if (data.meta) {
        lastPage = data.meta.last_page
      } else if (data.last_page !== undefined) {
        lastPage = data.last_page
      } else {
        lastPage = 1
      }

      page += 1
    } while (page <= lastPage)

    postes.value = collected
  } finally {
    loadingRefs.value = false
  }
}

const fetchDocumentTypes = async () => {
  try {
    const { data } = await api.get('/v1/documents/types')
    if (Array.isArray(data.data) && data.data.length) {
      documentTypes.value = data.data
      documentGroups.value = documentGroups.value.map((group) => ({
        ...group,
        type_document: documentTypes.value.includes(group.type_document)
          ? group.type_document
          : documentTypes.value[0],
      }))
    }
  } catch (error) {
    // conserve les types locaux
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

const revokeFileEntry = (entry) => {
  if (entry?.previewUrl) {
    URL.revokeObjectURL(entry.previewUrl)
  }
}

const clearGroupFiles = (group) => {
  group.files.forEach(revokeFileEntry)
  group.files = []
}

const addDocumentGroup = () => {
  documentGroups.value.push(createDocumentGroup())
}

const removeDocumentGroup = (groupId) => {
  const group = documentGroups.value.find((item) => item.id === groupId)
  if (!group) {
    return
  }

  if (documentGroups.value.length === 1 && !group.files.length && !group.date_expiration) {
    return
  }

  clearGroupFiles(group)
  documentGroups.value = documentGroups.value.filter((item) => item.id !== groupId)

  if (!documentGroups.value.length) {
    documentGroups.value = [createDocumentGroup()]
  }
}

const mergeFilesIntoGroup = (groupId, files) => {
  documentGroups.value = documentGroups.value.map((group) => {
    if (group.id !== groupId) {
      return group
    }

    const existing = new Set(group.files.map((item) => item.id))
    const additions = []

    files.forEach((file) => {
      const entry = createFileEntry(file)
      if (existing.has(entry.id)) {
        revokeFileEntry(entry)
        return
      }
      additions.push(entry)
      existing.add(entry.id)
    })

    return {
      ...group,
      files: [...group.files, ...additions],
    }
  })
}

const onGroupFilesChange = (groupId, event) => {
  const files = Array.from(event.target.files || [])
  if (files.length) {
    mergeFilesIntoGroup(groupId, files)
  }
  event.target.value = ''
}

const removeGroupFile = (groupId, fileId) => {
  documentGroups.value = documentGroups.value.map((group) => {
    if (group.id !== groupId) {
      return group
    }

    const entry = group.files.find((item) => item.id === fileId)
    if (entry) {
      revokeFileEntry(entry)
    }

    return {
      ...group,
      files: group.files.filter((item) => item.id !== fileId),
    }
  })
}

const onPhoto = (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = () => {
    form.value.photo = reader.result
    photoPreview.value = reader.result
  }
  reader.readAsDataURL(file)
}

const uploadDocumentGroups = async (employeId) => {
  const groupsToUpload = documentGroups.value.filter((group) => group.files.length > 0)

  if (!groupsToUpload.length) {
    return { total: 0, failed: 0 }
  }

  const results = await Promise.allSettled(groupsToUpload.map(async (group) => {
    const fd = new FormData()
    fd.append('employe_id', employeId)
    fd.append('type_document', group.type_document)
    fd.append('date_expiration', group.date_expiration || '')
    group.files.forEach((item) => {
      fd.append('fichiers[]', item.file)
    })

    await api.post('/v1/documents/upload', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  }))

  return {
    total: results.length,
    failed: results.filter((result) => result.status === 'rejected').length,
  }
}

const createEmploye = async () => {
  if (saving.value) return

  saving.value = true
  message.value = ''

  try {
    const payload = { ...form.value }
    payload.poste_id = payload.poste_id || null
    payload.photo = payload.photo || null
    payload.date_naissance = payload.date_naissance || null

    const { data } = await api.post('/v1/employes', payload)
    const uploadResult = await uploadDocumentGroups(data.id)

    if (uploadResult.failed > 0) {
      message.value = `Employé créé, mais ${uploadResult.failed} groupe(s) de documents n'ont pas pu être importés.`
      messageType.value = 'warning'
    } else if (uploadResult.total > 0) {
      message.value = 'Employé et documents justificatifs créés avec succès.'
      messageType.value = 'success'
    } else {
      message.value = 'Employé créé avec succès.'
      messageType.value = 'success'
    }

    setTimeout(() => router.push(`/employes/${data.id}`), 700)
  } catch (error) {
    message.value = error.response?.data?.message || 'Erreur lors de la création.'
    messageType.value = 'warning'
  } finally {
    saving.value = false
  }
}

const formatInteger = (value) => new Intl.NumberFormat('fr-FR').format(Number(value || 0))
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

onMounted(async () => {
  documentGroups.value = [createDocumentGroup()]
  await Promise.all([fetchAllPostes(), fetchDocumentTypes()])
})

onBeforeUnmount(() => {
  documentGroups.value.forEach(clearGroupFiles)
})
</script>

<style scoped>
.content-grid {
  grid-template-columns: 1fr;
}

.field-group-head h3 {
  margin: 8px 0 0;
  font-weight: 800;
  letter-spacing: 0;
}

.field-sections {
  display: grid;
  gap: 22px;
}

.field-group {
  display: grid;
  gap: 16px;
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: 22px;
  background: rgba(248, 250, 252, 0.78);
}

body[data-theme='dark'] .field-group {
  background: rgba(15, 23, 42, 0.46);
}

.field-group-head {
  display: grid;
  gap: 6px;
}

.field-group-head h3 {
  font-size: 1.1rem;
}

.field-group-head p,
.photo-subtitle,
.field-help {
  color: var(--muted);
  font-size: 0.92rem;
  line-height: 1.6;
}

.fields-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.photo-uploader {
  display: flex;
  align-items: center;
  gap: 18px;
}

.photo-preview {
  width: 88px;
  height: 88px;
  flex: none;
  border-radius: 22px;
  object-fit: cover;
  border: 1px solid var(--border);
  background: rgba(248, 250, 252, 0.8);
}

.photo-copy {
  display: grid;
  gap: 8px;
  width: 100%;
}

.photo-title {
  margin: 0;
  font-weight: 700;
}

.photo-subtitle {
  margin: 0;
}

.document-groups {
  display: grid;
  gap: 14px;
}

.document-group-card {
  display: grid;
  gap: 14px;
  padding: 16px;
  border-radius: 18px;
  border: 1px solid var(--border);
  background: rgba(255, 255, 255, 0.68);
}

body[data-theme='dark'] .document-group-card {
  background: rgba(15, 23, 42, 0.4);
}

.document-group-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.document-group-head h4,
.document-group-kicker {
  margin: 0;
}

.document-group-kicker {
  color: var(--muted);
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.documents-fields-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.upload-field {
  display: grid;
  gap: 10px;
}

.upload-field-inline {
  align-items: flex-start;
}

.upload-input {
  padding: 10px 12px;
}

.preview-grid {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: flex-start;
  gap: 12px;
}

.preview-grid-inline {
  margin-top: 2px;
}

.preview-card {
  display: grid;
  gap: 8px;
  width: 146px;
  padding: 8px;
  border-radius: 14px;
  border: 1px solid var(--border);
  background: rgba(255, 255, 255, 0.76);
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
}

body[data-theme='dark'] .preview-card {
  background: rgba(15, 23, 42, 0.7);
}

.preview-card-tiny {
  width: 146px;
}

.preview-thumb {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 84px;
  border-radius: 12px;
  background: linear-gradient(180deg, #2563eb, #1d4ed8);
  overflow: hidden;
}

.preview-thumb-tiny {
  min-height: 84px;
}

.preview-image {
  width: 100%;
  height: 84px;
  object-fit: cover;
  display: block;
}

.preview-image-tiny {
  height: 84px;
}

.preview-file-tile {
  width: calc(100% - 18px);
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.96);
}

.preview-file-tile-tiny {
  height: 60px;
}

.preview-file-ext {
  color: #1e3a8a;
  font-size: 0.95rem;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.preview-file-ext-tiny {
  font-size: 0.95rem;
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

.preview-action-tiny {
  padding: 8px 10px;
}

.submit-row {
  display: flex;
  gap: 10px;
}

.submit-row > * {
  width: fit-content;
}

@media (max-width: 900px) {
  .fields-grid,
  .documents-fields-grid {
    grid-template-columns: 1fr;
  }

  .photo-uploader {
    align-items: flex-start;
    flex-direction: column;
  }
}

@media (max-width: 680px) {
  .submit-row {
    flex-direction: column;
    align-items: stretch;
  }

  .submit-row > * {
    width: 100%;
  }
}
</style>
