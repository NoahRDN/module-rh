<template>
  <div class="create-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Payroll component</p>
        <h1>{{ isEditing ? 'Modifier un élément' : 'Nouvelle indemnité ou prime' }}</h1>
        <p class="hero-subtitle">
          Paramétrez la portée, la récurrence, la condition éventuelle et le montant fixe de l’élément.
        </p>

        <div class="hero-pills">
          <span class="pill">Prime ou indemnité</span>
          <span class="pill">Cible configurable</span>
          <span class="pill">Condition d’ancienneté</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn btn-secondary" to="/remuneration-items">Retour</RouterLink>
            <button class="btn" type="button" @click="save" :disabled="loading || loadingInit">
              {{ loading ? 'Enregistrement...' : isEditing ? 'Mettre à jour' : 'Créer' }}
            </button>
          </div>

          <div v-if="message" class="status-banner" :class="messageType">
            <span class="status-dot"></span>
            <span>{{ message }}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="card section-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Rule form</p>
          <h2>Paramètres</h2>
        </div>
        <span class="section-chip">{{ isEditing ? 'Modification' : 'Création' }}</span>
      </div>

      <form class="fields-grid" @submit.prevent="save">
        <label class="field-card full">
          <span class="field-label">Libellé</span>
          <input v-model="form.libelle" class="input" placeholder="Ex: Prime d'ancienneté" required />
        </label>

        <label class="field-card">
          <span class="field-label">Nature</span>
          <select v-model="form.nature" class="select" required>
            <option value="prime">Prime</option>
            <option value="indemnite">Indemnité</option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Portée</span>
          <select v-model="form.scope_type" class="select" required>
            <option value="global">Global</option>
            <option value="poste">Par poste</option>
            <option value="employe">Par employé</option>
            <option value="contrat">Par contrat</option>
          </select>
        </label>

        <label v-if="form.scope_type === 'poste'" class="field-card">
          <span class="field-label">Poste</span>
          <select v-model="form.poste_id" class="select" required>
            <option value="">Sélectionner</option>
            <option v-for="poste in postes" :key="poste.id" :value="String(poste.id)">{{ poste.nom }}</option>
          </select>
        </label>

        <label v-if="form.scope_type === 'employe'" class="field-card">
          <span class="field-label">Employé</span>
          <select v-model="form.employe_id" class="select" required>
            <option value="">Sélectionner</option>
            <option v-for="employe in employes" :key="employe.id" :value="String(employe.id)">
              {{ employe.matricule }} - {{ employe.nom }} {{ employe.prenom || '' }}
            </option>
          </select>
        </label>

        <label v-if="form.scope_type === 'contrat'" class="field-card">
          <span class="field-label">Contrat</span>
          <select v-model="form.contrat_id" class="select" required>
            <option value="">Sélectionner</option>
            <option v-for="contrat in contrats" :key="contrat.id" :value="String(contrat.id)">
              {{ contrat.numero || `#${contrat.id}` }} - {{ contrat.employe?.nom || '' }} {{ contrat.employe?.prenom || '' }}
            </option>
          </select>
        </label>

        <label class="field-card">
          <span class="field-label">Récurrence</span>
          <select v-model="form.recurrence_type" class="select" required>
            <option value="recurrent">Récurrent</option>
            <option value="ponctuel">Ponctuel</option>
          </select>
        </label>

        <label v-if="form.recurrence_type === 'ponctuel'" class="field-card">
          <span class="field-label">Mois d'application</span>
          <input v-model="form.mois_application" class="input" type="month" required />
        </label>

        <label class="field-card">
          <span class="field-label">Montant fixe</span>
          <input v-model="form.montant" class="input" type="number" min="0" step="0.01" required />
        </label>

        <label class="field-card">
          <span class="field-label">Traitement fiscal</span>
          <select v-model="form.is_taxable" class="select" required>
            <option :value="true">Imposable</option>
            <option :value="false">Non imposable</option>
          </select>
        </label>

        <label class="field-card checkbox-line">
          <span class="field-label">Actif</span>
          <input v-model="form.actif" type="checkbox" />
        </label>

        <div class="field-card full condition-card">
          <div class="condition-head">
            <span class="field-label">Condition</span>
            <label class="checkbox-inline">
              <input v-model="conditionEnabled" type="checkbox" />
              <span>Ajouter une condition</span>
            </label>
          </div>

          <div v-if="conditionEnabled" class="condition-grid">
            <select v-model="form.condition_type" class="select">
              <option value="anciennete">Ancienneté</option>
            </select>

            <select v-model="form.condition_operator" class="select">
              <option value=">">&gt;</option>
              <option value="<">&lt;</option>
              <option value="=">=</option>
              <option value=">=">&gt;=</option>
              <option value="<=">&lt;=</option>
            </select>

            <input v-model="form.condition_value" class="input" type="number" min="0" step="0.01" placeholder="Valeur" />
          </div>
        </div>

        <div class="submit-row">
          <button class="btn" type="submit" :disabled="loading || loadingInit">
            {{ loading ? 'Enregistrement...' : isEditing ? 'Mettre à jour' : 'Créer' }}
          </button>
          <RouterLink class="btn btn-secondary" to="/remuneration-items">Annuler</RouterLink>
        </div>
      </form>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import api from '../services/api'

const route = useRoute()
const router = useRouter()

const loadingInit = ref(false)
const loading = ref(false)
const message = ref('')
const messageType = ref('info')
const employes = ref([])
const postes = ref([])
const contrats = ref([])
const conditionEnabled = ref(false)

const form = ref({
  libelle: '',
  nature: 'prime',
  scope_type: 'global',
  poste_id: '',
  employe_id: '',
  contrat_id: '',
  recurrence_type: 'recurrent',
  mois_application: '',
  condition_type: 'anciennete',
  condition_operator: '>=',
  condition_value: '',
  montant: '',
  is_taxable: true,
  actif: true,
})

const isEditing = computed(() => Boolean(route.params.id))

const resetScopeTargets = () => {
  if (form.value.scope_type !== 'poste') form.value.poste_id = ''
  if (form.value.scope_type !== 'employe') form.value.employe_id = ''
  if (form.value.scope_type !== 'contrat') form.value.contrat_id = ''
}

watch(() => form.value.scope_type, resetScopeTargets)

watch(conditionEnabled, (enabled) => {
  if (!enabled) {
    form.value.condition_type = 'anciennete'
    form.value.condition_operator = '>='
    form.value.condition_value = ''
  }
})

watch(() => form.value.recurrence_type, (value) => {
  if (value !== 'ponctuel') {
    form.value.mois_application = ''
  }
})

const loadReferences = async () => {
  const [employesResponse, postesResponse, contratsResponse] = await Promise.all([
    api.get('/v1/employes', { params: { all: true } }),
    api.get('/v1/postes', { params: { all: true } }),
    api.get('/v1/contrats', { params: { all: true } }),
  ])

  employes.value = employesResponse.data?.data || employesResponse.data || []
  postes.value = postesResponse.data?.data || postesResponse.data || []
  contrats.value = contratsResponse.data?.data || contratsResponse.data || []
}

const loadExisting = async () => {
  if (!isEditing.value) return
  loadingInit.value = true
  try {
    const { data } = await api.get(`/v1/remuneration-items/${route.params.id}`)
    form.value = {
      libelle: data.libelle || '',
      nature: data.nature || 'prime',
      scope_type: data.scope_type || 'global',
      poste_id: data.poste_id ? String(data.poste_id) : '',
      employe_id: data.employe_id ? String(data.employe_id) : '',
      contrat_id: data.contrat_id ? String(data.contrat_id) : '',
      recurrence_type: data.recurrence_type || 'recurrent',
      mois_application: data.mois_application || '',
      condition_type: data.condition_type || 'anciennete',
      condition_operator: data.condition_operator || '>=',
      condition_value: data.condition_value ?? '',
      montant: data.montant ?? '',
      is_taxable: data.is_taxable !== false,
      actif: Boolean(data.actif),
    }
    conditionEnabled.value = Boolean(data.condition_type)
  } catch (e) {
    message.value = e.response?.data?.message || 'Impossible de charger cet élément.'
    messageType.value = 'danger'
  } finally {
    loadingInit.value = false
  }
}

const save = async () => {
  if (loading.value || loadingInit.value) return
  loading.value = true
  message.value = ''

  const payload = {
    ...form.value,
    poste_id: form.value.poste_id || null,
    employe_id: form.value.employe_id || null,
    contrat_id: form.value.contrat_id || null,
    mois_application: form.value.mois_application || null,
    condition_type: conditionEnabled.value ? form.value.condition_type : null,
    condition_operator: conditionEnabled.value ? form.value.condition_operator : null,
    condition_value: conditionEnabled.value && form.value.condition_value !== '' ? Number(form.value.condition_value) : null,
    montant: Number(form.value.montant || 0),
    is_taxable: Boolean(form.value.is_taxable),
    actif: Boolean(form.value.actif),
  }

  try {
    if (isEditing.value) {
      await api.put(`/v1/remuneration-items/${route.params.id}`, payload)
      message.value = 'Élément mis à jour.'
    } else {
      await api.post('/v1/remuneration-items', payload)
      message.value = 'Élément créé.'
    }
    messageType.value = 'success'
    setTimeout(() => router.push('/remuneration-items'), 450)
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur lors de l’enregistrement.'
    messageType.value = 'danger'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadReferences()
  await loadExisting()
})
</script>

<style scoped>
.checkbox-line,
.checkbox-inline {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.condition-card {
  display: grid;
  gap: 12px;
}

.condition-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.condition-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

@media (max-width: 900px) {
  .condition-grid {
    grid-template-columns: 1fr;
  }
}
</style>
