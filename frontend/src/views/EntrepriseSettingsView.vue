<template>
  <div class="rh-page entreprise-settings-page">
    <section class="rh-hero hero hero-band hero-shared">
      <div class="rh-hero-copy hero-copy">
        <p class="rh-hero-kicker hero-kicker">Company identity</p>
        <h1>Paramètres de l'entreprise</h1>
        <p class="rh-hero-subtitle hero-subtitle">
          Configurez le nom et le logo utilisés dans les documents administratifs générés par le
          module RH.
        </p>

        <div class="rh-hero-pills hero-pills">
          <span class="pill">Nom légal</span>
          <span class="pill">Devise</span>
          <span class="pill">Logo PDF</span>
          <span class="pill">Documents RH</span>
        </div>
      </div>

      <div class="rh-hero-actions hero-actions">
        <div class="rh-panel filters-panel">
          <div class="rh-action-row">
            <button class="btn btn-secondary" type="button" @click="loadSettings" :disabled="loading">
              <AppIcon name="refresh" :size="18" />
              <span>Réinitialiser</span>
            </button>
            <button class="btn" type="button" @click="save" :disabled="loading">
              <AppIcon name="save" :size="18" />
              <span>Enregistrer</span>
            </button>
          </div>

          <p class="rh-hero-meta hero-meta">
            Identité actuelle:
            <strong>{{ form.nom || 'Non configurée' }}</strong>
          </p>
        </div>
      </div>
    </section>

    <p class="rh-status-banner success" v-if="statusMessage">
      <span class="rh-status-dot"></span>
      <span>{{ statusMessage }}</span>
    </p>
    <p class="rh-status-banner danger" v-if="error">
      <span class="rh-status-dot"></span>
      <span>{{ error }}</span>
    </p>

    <div class="card rh-loading-card" v-if="loading && !loaded">
      <p class="rh-loading-title">Chargement des paramètres...</p>
      <p class="muted">Synchronisation de l'identité entreprise.</p>
    </div>

    <section class="rh-content-grid" v-else>
      <article class="card rh-section-card">
        <div class="rh-section-heading">
          <div>
            <p class="rh-section-kicker">Identity</p>
            <h2>Informations générales</h2>
          </div>
        </div>

        <div class="rh-fields-grid">
          <label class="rh-field-card full">
            <span class="rh-field-label">Nom de l'entreprise</span>
            <input
              class="input"
              v-model.trim="form.nom"
              type="text"
              maxlength="255"
              placeholder="Ex: Société ABC"
            />
          </label>

          <label class="rh-field-card">
            <span class="rh-field-label">Devise de l'application</span>
            <select class="select" v-model="form.devise">
              <option v-for="currency in currencyOptions" :key="currency" :value="currency">
                {{ currency }}
              </option>
            </select>
            <span class="muted">Utilisée par défaut pour les montants affichés dans l'application.</span>
          </label>

          <label class="rh-field-card full">
            <span class="rh-field-label">Logo</span>
            <input class="input" type="file" accept="image/*" @change="onLogoChange" />
            <span class="muted">Formats acceptés: JPG, PNG, WebP, SVG. Taille maximale: 2 Mo.</span>
          </label>
        </div>
      </article>

      <aside class="card rh-section-card rh-side-card">
        <div class="rh-section-heading compact">
          <div>
            <p class="rh-section-kicker">Preview</p>
            <h2>Aperçu document</h2>
          </div>
        </div>

        <div class="letter-preview">
          <div class="letter-header">
            <img v-if="logoPreview" :src="logoPreview" :alt="`Logo ${form.nom}`" />
            <div class="preview-company">{{ form.nom || 'Nom de l’entreprise' }}</div>
          </div>
          <div class="preview-line wide"></div>
          <div class="preview-line"></div>
          <div class="preview-title">Document administratif RH</div>
          <p class="preview-copy">
            Ce nom et ce logo seront repris dans l'en-tête des attestations, certificats, contrats,
            avenants et lettres générés.
          </p>
        </div>

        <button
          v-if="existingLogoUrl || logoPreview"
          class="btn btn-secondary remove-logo"
          type="button"
          @click="removeLogo"
          :disabled="loading"
        >
          <AppIcon name="trash" :size="18" />
          <span>Retirer le logo</span>
        </button>
      </aside>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api, { resolveBackendAssetUrl } from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'
import { getCurrencyOptions, setStoredCurrency } from '../utils/currency'

const loading = ref(false)
const loaded = ref(false)
const error = ref('')
const statusMessage = ref('')
const logoFile = ref(null)
const localLogoPreview = ref('')
const existingLogoUrl = ref('')
const removeExistingLogo = ref(false)
const form = ref({
  nom: '',
  devise: 'MGA',
})
const currencyOptions = getCurrencyOptions()

const logoPreview = computed(() => localLogoPreview.value || existingLogoUrl.value)

const loadSettings = async () => {
  loading.value = true
  error.value = ''
  statusMessage.value = ''
  try {
    const { data } = await api.get('/v1/entreprise-settings')
    form.value.nom = data.nom || ''
    form.value.devise = data.devise || 'MGA'
    setStoredCurrency(form.value.devise)
    existingLogoUrl.value = resolveBackendAssetUrl(data.logo_url || '')
    logoFile.value = null
    localLogoPreview.value = ''
    removeExistingLogo.value = false
    loaded.value = true
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur de chargement des paramètres entreprise'
  } finally {
    loading.value = false
  }
}

const onLogoChange = (event) => {
  const [file] = event.target.files || []
  logoFile.value = file || null
  removeExistingLogo.value = false

  if (localLogoPreview.value) {
    URL.revokeObjectURL(localLogoPreview.value)
  }

  localLogoPreview.value = file ? URL.createObjectURL(file) : ''
}

const removeLogo = () => {
  logoFile.value = null
  removeExistingLogo.value = true
  existingLogoUrl.value = ''

  if (localLogoPreview.value) {
    URL.revokeObjectURL(localLogoPreview.value)
    localLogoPreview.value = ''
  }
}

const save = async () => {
  loading.value = true
  error.value = ''
  statusMessage.value = ''
  try {
    const payload = new FormData()
    payload.append('nom', form.value.nom)
    payload.append('devise', form.value.devise || 'MGA')
    if (logoFile.value) {
      payload.append('logo', logoFile.value)
    }
    if (removeExistingLogo.value) {
      payload.append('remove_logo', '1')
    }

    const { data } = await api.post('/v1/entreprise-settings', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    form.value.nom = data.nom || form.value.nom
    form.value.devise = data.devise || form.value.devise || 'MGA'
    setStoredCurrency(form.value.devise)
    existingLogoUrl.value = resolveBackendAssetUrl(data.logo_url || '')
    logoFile.value = null
    removeExistingLogo.value = false
    if (localLogoPreview.value) {
      URL.revokeObjectURL(localLogoPreview.value)
      localLogoPreview.value = ''
    }
    statusMessage.value = 'Paramètres entreprise enregistrés avec succès.'
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur de sauvegarde des paramètres entreprise'
  } finally {
    loading.value = false
  }
}

onMounted(loadSettings)
</script>

<style scoped>
.letter-preview {
  display: grid;
  gap: 16px;
  padding: 22px;
  border: 1px solid var(--border);
  border-radius: 22px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.88), rgba(248, 250, 252, 0.72));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
}

body[data-theme='dark'] .letter-preview {
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.88), rgba(30, 41, 59, 0.72));
}

.letter-header {
  display: grid;
  justify-items: center;
  gap: 10px;
  padding-bottom: 14px;
  border-bottom: 2px solid var(--brand-500);
  text-align: center;
}

.letter-header img {
  max-width: 180px;
  max-height: 80px;
  object-fit: contain;
}

.preview-company {
  color: var(--brand-600);
  font-size: 1.2rem;
  font-weight: 900;
  letter-spacing: -0.03em;
}

.preview-line {
  height: 10px;
  width: 72%;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.25);
}

.preview-line.wide {
  width: 100%;
}

.preview-title {
  margin-top: 10px;
  font-weight: 900;
  text-align: center;
  text-transform: uppercase;
}

.preview-copy {
  margin: 0;
  color: var(--muted);
  line-height: 1.65;
}

.remove-logo {
  margin-top: 16px;
  width: 100%;
  justify-content: center;
}
</style>
