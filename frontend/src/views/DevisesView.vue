<template>
  <div class="rh-page devises-page">
    <section class="hero hero-band hero-shared hero-compact">
      <div class="hero-copy">
        <p class="hero-kicker">Currency settings</p>
        <h1>Devises</h1>
        <p class="hero-subtitle">
          Gérez la liste des devises disponibles pour les paramètres entreprise et l’affichage des montants.
        </p>
        <div class="hero-pills">
          <span class="pill">{{ activeCount }} active(s)</span>
          <span class="pill">{{ inactiveCount }} inactive(s)</span>
          <span class="pill">{{ devises.length }} total</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <button class="btn btn-secondary" type="button" :disabled="loading" @click="loadDevises">
              Actualiser
            </button>
            <button class="btn" type="button" @click="openCreate">Nouvelle devise</button>
          </div>

          <label class="field-card">
            <span class="field-label">Filtrer</span>
            <select class="select" v-model="statusFilter">
              <option value="tous">Toutes</option>
              <option value="active">Actives</option>
              <option value="inactive">Inactives</option>
            </select>
          </label>

          <div v-if="error" class="status-banner danger">
            <span class="status-dot"></span>
            <span>{{ error }}</span>
          </div>
          <div v-if="statusMessage" class="status-banner success">
            <span class="status-dot"></span>
            <span>{{ statusMessage }}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="card section-card table-card">
      <div class="section-heading">
        <div>
          <p class="section-kicker">Catalogue</p>
          <h2>Liste des devises</h2>
        </div>
      </div>

      <div class="table-shell">
        <table class="table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Libellé</th>
              <th>Symbole</th>
              <th>Statut</th>
              <th class="actions-col">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="devise in filteredDevises" :key="devise.id">
              <td class="accent">{{ devise.code }}</td>
              <td>{{ devise.libelle }}</td>
              <td>{{ devise.symbole || devise.code }}</td>
              <td>
                <span class="chip" :class="devise.active ? 'success' : 'muted-chip'">
                  {{ devise.active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="actions-col">
                <div class="actions-row">
                  <button class="btn btn-secondary btn-xs" type="button" @click="openEdit(devise)">Modifier</button>
                  <button class="btn btn-secondary btn-xs" type="button" @click="toggleStatus(devise)">
                    {{ devise.active ? 'Désactiver' : 'Activer' }}
                  </button>
                  <button class="btn btn-secondary btn-xs danger-text" type="button" @click="remove(devise)">Supprimer</button>
                </div>
              </td>
            </tr>
            <tr v-if="!filteredDevises.length">
              <td colspan="5" class="muted">Aucune devise pour ce filtre.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal card section-card">
        <div class="section-heading compact">
          <div>
            <p class="section-kicker">{{ isEdit ? 'Edition' : 'Création' }}</p>
            <h2>{{ isEdit ? 'Modifier devise' : 'Nouvelle devise' }}</h2>
          </div>
        </div>

        <div class="fields-grid">
          <label class="field-card">
            <span class="field-label">Code</span>
            <input class="input" v-model.trim="form.code" type="text" maxlength="10" placeholder="MGA" />
          </label>

          <label class="field-card">
            <span class="field-label">Libellé</span>
            <input class="input" v-model.trim="form.libelle" type="text" maxlength="100" placeholder="Ariary malgache" />
          </label>

          <label class="field-card">
            <span class="field-label">Symbole</span>
            <input class="input" v-model.trim="form.symbole" type="text" maxlength="16" placeholder="Ar / € / $" />
          </label>

          <label class="field-card">
            <span class="field-label">Statut</span>
            <select class="select" v-model="form.active">
              <option :value="true">Active</option>
              <option :value="false">Inactive</option>
            </select>
          </label>
        </div>

        <div class="action-row modal-actions">
          <button class="btn btn-secondary" type="button" @click="closeModal">Annuler</button>
          <button class="btn" type="button" :disabled="saving" @click="save">
            {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../services/api'
import { setCurrencyCatalog } from '../utils/currency'

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const statusMessage = ref('')
const statusFilter = ref('tous')
const devises = ref([])
const showModal = ref(false)
const editId = ref(null)
const form = ref({
  code: '',
  libelle: '',
  symbole: '',
  active: true,
})

const isEdit = computed(() => editId.value !== null)
const activeCount = computed(() => devises.value.filter((item) => item.active).length)
const inactiveCount = computed(() => devises.value.filter((item) => !item.active).length)

const filteredDevises = computed(() => {
  if (statusFilter.value === 'active') return devises.value.filter((item) => item.active)
  if (statusFilter.value === 'inactive') return devises.value.filter((item) => !item.active)
  return devises.value
})

const resetForm = () => {
  form.value = {
    code: '',
    libelle: '',
    symbole: '',
    active: true,
  }
}

const loadDevises = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/v1/devises')
    devises.value = Array.isArray(data) ? data : []
    setCurrencyCatalog(devises.value.filter((item) => item?.active))
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur de chargement des devises'
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  resetForm()
  editId.value = null
  showModal.value = true
}

const openEdit = (devise) => {
  form.value = {
    code: devise.code || '',
    libelle: devise.libelle || '',
    symbole: devise.symbole || '',
    active: Boolean(devise.active),
  }
  editId.value = devise.id
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editId.value = null
  resetForm()
}

const save = async () => {
  saving.value = true
  error.value = ''
  statusMessage.value = ''
  try {
    const payload = {
      code: String(form.value.code || '').toUpperCase(),
      libelle: form.value.libelle,
      symbole: form.value.symbole || null,
      active: Boolean(form.value.active),
    }
    if (isEdit.value) {
      await api.put(`/v1/devises/${editId.value}`, payload)
      statusMessage.value = 'Devise mise à jour.'
    } else {
      await api.post('/v1/devises', payload)
      statusMessage.value = 'Devise créée.'
    }
    await loadDevises()
    closeModal()
  } catch (e) {
    error.value = e.response?.data?.message || 'Erreur de sauvegarde'
  } finally {
    saving.value = false
  }
}

const toggleStatus = async (devise) => {
  error.value = ''
  statusMessage.value = ''
  try {
    await api.put(`/v1/devises/${devise.id}`, {
      code: devise.code,
      libelle: devise.libelle,
      symbole: devise.symbole,
      active: !devise.active,
    })
    statusMessage.value = 'Statut devise mis à jour.'
    await loadDevises()
  } catch (e) {
    error.value = e.response?.data?.message || 'Impossible de modifier le statut'
  }
}

const remove = async (devise) => {
  if (!window.confirm(`Supprimer la devise ${devise.code} ?`)) return
  error.value = ''
  statusMessage.value = ''
  try {
    await api.delete(`/v1/devises/${devise.id}`)
    statusMessage.value = 'Devise supprimée.'
    await loadDevises()
  } catch (e) {
    error.value = e.response?.data?.message || 'Suppression impossible'
  }
}

onMounted(loadDevises)
</script>

<style scoped>
.accent {
  font-weight: 800;
  color: var(--brand-600);
}

.chip.success {
  background: var(--success-100);
  color: var(--success-500);
}

.muted-chip {
  background: rgba(148, 163, 184, 0.12);
  color: var(--muted);
}

.actions-col {
  width: 1%;
  white-space: nowrap;
}

.actions-row {
  display: flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
}

@media (max-width: 980px) {
  .actions-row {
    flex-wrap: wrap;
  }
}

.danger-text {
  color: #ef4444;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 80;
  display: grid;
  place-items: center;
  padding: 16px;
  background: rgba(2, 6, 23, 0.55);
}

.modal {
  width: min(700px, 100%);
  display: grid;
  gap: 14px;
}

.modal-actions {
  justify-content: flex-end;
}
</style>
