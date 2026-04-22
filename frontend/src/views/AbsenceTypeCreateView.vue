<template>
  <div class="create-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Leave taxonomy</p>
        <h1>{{ isEdit ? "Modifier type d'absence" : "Nouveau type d'absence" }}</h1>
        <p class="hero-subtitle">
          Définissez les règles d’un type de congé (solde, cumul, limite, paiement) pour piloter le
          workflow et les calculs associés.
        </p>

        <div class="hero-pills">
          <span class="pill">Règles</span>
          <span class="pill">Solde</span>
          <span class="pill">Cumul</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink class="btn btn-secondary" to="/absences-types">Retour</RouterLink>
            <button class="btn" type="button" @click="submit" :disabled="loading">
              {{ loading ? (isEdit ? 'Enregistrement...' : 'Création...') : 'Enregistrer' }}
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
          <p class="section-kicker">Leave form</p>
          <h2>Paramètres</h2>
        </div>
        <span class="section-chip">{{ isEdit ? 'Modification' : 'Création' }}</span>
      </div>

      <form class="fields-grid" @submit.prevent="submit" v-if="ready">
        <label class="field-card">
          <span class="field-label">Libellé</span>
          <input class="input" v-model="form.libelle" placeholder="Ex: Congé payé" required />
        </label>

        <label class="field-card">
          <span class="field-label">Code</span>
          <input class="input" v-model="form.code" placeholder="Ex: PAYE" required />
        </label>

        <label class="field-card">
          <span class="field-label">Fréquence</span>
          <select class="select" v-model="form.frequence_id">
            <option value="">(optionnel)</option>
            <option v-for="f in frequences" :key="f.id" :value="f.id">{{ f.code }} - {{ f.libelle }}</option>
          </select>
        </label>

        <label class="field-card full">
          <span class="field-label">Description</span>
          <textarea class="input" rows="4" v-model="form.description" placeholder="Description"></textarea>
        </label>

        <label class="field-card full">
          <span class="field-label">Options</span>
          <div class="chip-list">
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" v-model="form.paye" />
              <span>Payant</span>
            </label>
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" v-model="form.utilise_solde" />
              <span>Utilise un solde</span>
            </label>
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" v-model="form.cumulable" />
              <span>Cumulable</span>
            </label>
          </div>
        </label>

        <label class="field-card">
          <span class="field-label">Jours forfait</span>
          <input class="input" type="number" min="0" v-model="form.jours_forfait" placeholder="(optionnel)" />
        </label>

        <label class="field-card">
          <span class="field-label">Limite</span>
          <input class="input" type="number" min="0" v-model="form.limite" placeholder="(optionnel)" />
        </label>

        <label class="field-card">
          <span class="field-label">Fréquence limite</span>
          <select class="select" v-model="form.limite_frequence_id">
            <option value="">(optionnel)</option>
            <option v-for="f in frequences" :key="f.id" :value="f.id">{{ f.code }} - {{ f.libelle }}</option>
          </select>
        </label>

        <template v-if="form.cumulable">
          <label class="field-card">
            <span class="field-label">Durée cumul</span>
            <input class="input" type="number" min="0" v-model="form.cumulable_duree" placeholder="Durée" />
          </label>

          <label class="field-card">
            <span class="field-label">Fréquence cumul</span>
            <select class="select" v-model="form.cumulable_frequence_id">
              <option value="">Sélectionner</option>
              <option v-for="f in frequences" :key="f.id" :value="f.id">{{ f.code }} - {{ f.libelle }}</option>
            </select>
          </label>
        </template>

        <div class="submit-row">
          <button class="btn" type="submit" :disabled="loading">
            {{ loading ? (isEdit ? 'Enregistrement...' : 'Création...') : 'Enregistrer' }}
          </button>
          <RouterLink class="btn btn-secondary" to="/absences-types">Annuler</RouterLink>
        </div>
      </form>

      <div v-else class="empty-state">
        <p>Chargement du type…</p>
        <span class="muted">Récupération des paramètres (fréquences, règles).</span>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const ready = ref(true)
const message = ref('')
const frequences = ref([])
const form = ref({
  libelle: '',
  code: '',
  description: '',
  paye: true,
  utilise_solde: true,
  cumulable: false,
  jours_forfait: '',
  limite: '',
  frequence_id: '',
  limite_frequence_id: '',
  cumulable_duree: '',
  cumulable_frequence_id: ''
})

const isEdit = computed(() => Boolean(route.params.id))

const loadFreq = async () => {
  const { data } = await api.get('/v1/frequences-conges')
  frequences.value = data.data || data || []
}

const loadType = async () => {
  if (!isEdit.value) return
  ready.value = false
  message.value = ''
  try {
    const { data } = await api.get(`/v1/types-conges/${route.params.id}`)
    form.value = {
      libelle: data.libelle || '',
      code: data.code || '',
      description: data.description || '',
      paye: Boolean(data.paye),
      utilise_solde: Boolean(data.utilise_solde),
      cumulable: Boolean(data.cumulable),
      jours_forfait: data.jours_forfait ?? '',
      limite: data.limite ?? '',
      frequence_id: data.frequence_id ?? '',
      limite_frequence_id: data.limite_frequence_id ?? '',
      cumulable_duree: data.cumulable_duree ?? '',
      cumulable_frequence_id: data.cumulable_frequence_id ?? '',
    }
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur lors du chargement du type'
  } finally {
    ready.value = true
  }
}

const submit = async () => {
  loading.value = true
  message.value = ''
  try {
    if (isEdit.value) {
      await api.put(`/v1/types-conges/${route.params.id}`, form.value)
    } else {
      await api.post('/v1/types-conges', form.value)
    }
    router.push('/absences-types')
  } catch (e) {
    message.value = e.response?.data?.message || (isEdit.value ? "Erreur lors de l'enregistrement" : 'Erreur lors de la création')
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadFreq()
  await loadType()
})
</script>
