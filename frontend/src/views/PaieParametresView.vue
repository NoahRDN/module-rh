<template>
  <div class="paie-settings">
    <div class="card">
      <div class="page-header">
        <div class="page-title">
          <h1>Paramètres paie</h1>
          <span>Taux légaux, plafonds et tranches IRSA</span>
        </div>
        <div class="actions">
          <button class="btn btn-secondary" @click="load">↻ Recharger</button>
          <button class="btn" @click="save">💾 Enregistrer</button>
        </div>
      </div>

      <div class="panel-grid">
        <div class="setting-card">
          <div class="setting-header">
            <h3>Cotisations sociales</h3>
            <p>Plafonds et taux appliqués aux salaires bruts</p>
          </div>
          <div class="fields-grid">
            <div class="field">
              <label>Plafond CNAPS</label>
              <input class="input" v-model="form.cnaps_plafond" type="number" step="0.01" required />
            </div>
            <div class="field">
              <label>CNAPS employé (%)</label>
              <input class="input" v-model="form.cnaps_taux_employe" type="number" step="0.01" required />
            </div>
            <div class="field">
              <label>CNAPS employeur (%)</label>
              <input class="input" v-model="form.cnaps_taux_employeur" type="number" step="0.01" required />
            </div>
            <div class="field">
              <label>OSTIE employé (%)</label>
              <input class="input" v-model="form.ostie_taux_employe" type="number" step="0.01" required />
            </div>
            <div class="field">
              <label>OSTIE employeur (%)</label>
              <input class="input" v-model="form.ostie_taux_employeur" type="number" step="0.01" required />
            </div>
          </div>
          <p v-if="message" class="hint">{{ message }}</p>
        </div>

        <div class="setting-card">
          <div class="setting-header">
            <h3>Tranches IRSA</h3>
            <p>Barème progressif appliqué sur la base imposable</p>
          </div>
          <div class="table-wrapper">
            <table class="table mb-3">
              <thead>
                <tr>
                  <th>Min</th>
                  <th>Max</th>
                  <th>Taux (%)</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="t in tranches" :key="t.id">
                  <td>{{ t.min_base }}</td>
                  <td>{{ t.max_base ?? '∞' }}</td>
                  <td>{{ t.taux }}</td>
                  <td class="text-right">
                    <button class="btn btn-secondary btn-xs" @click="removeTranche(t.id)">Supprimer</button>
                  </td>
                </tr>
                <tr v-if="!tranches.length"><td colspan="4" class="muted text-sm">Aucune tranche</td></tr>
              </tbody>
            </table>
          </div>

          <div class="add-tranche">
            <div class="field">
              <label>Min</label>
              <input class="input" v-model="newTranche.min_base" type="number" step="0.01" />
            </div>
            <div class="field">
              <label>Max</label>
              <input class="input" v-model="newTranche.max_base" type="number" step="0.01" />
            </div>
            <div class="field">
              <label>Taux (%)</label>
              <input class="input" v-model="newTranche.taux" type="number" step="0.01" />
            </div>
            <button class="btn btn-secondary" @click="addTranche">Ajouter tranche</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const form = ref({
  cnaps_plafond: 0,
  cnaps_taux_employe: 0,
  cnaps_taux_employeur: 0,
  ostie_taux_employe: 0,
  ostie_taux_employeur: 0,
  prime_transport: 0,
  prime_presence: 0
})
const id = ref(null)
const message = ref('')
const tranches = ref([])
const newTranche = ref({ min_base: 0, max_base: null, taux: 0 })

const load = async () => {
  const { data } = await api.get('/v1/paie-parametres')
  if (data) {
    id.value = data.id
    form.value = {
      cnaps_plafond: data.cnaps_plafond,
      cnaps_taux_employe: data.cnaps_taux_employe,
      cnaps_taux_employeur: data.cnaps_taux_employeur,
      ostie_taux_employe: data.ostie_taux_employe,
      ostie_taux_employeur: data.ostie_taux_employeur,
      prime_transport: data.prime_transport,
      prime_presence: data.prime_presence
    }
  }

  const { data: tr } = await api.get('/v1/irsa-tranches')
  tranches.value = tr || []
}

const save = async () => {
  if (!id.value) return
  try {
    await api.put(`/v1/paie-parametres/${id.value}`, form.value)
    message.value = 'Paramètres mis à jour'
  } catch (e) {
    message.value = 'Erreur lors de la sauvegarde'
  }
}

const addTranche = async () => {
  try {
    await api.post('/v1/irsa-tranches', newTranche.value)
    await load()
    newTranche.value = { min_base: 0, max_base: null, taux: 0 }
  } catch (e) {
    message.value = 'Erreur ajout tranche'
  }
}

const removeTranche = async (idTranche) => {
  try {
    await api.delete(`/v1/irsa-tranches/${idTranche}`)
    tranches.value = tranches.value.filter(t => t.id !== idTranche)
  } catch (e) {
    message.value = 'Erreur suppression tranche'
  }
}

onMounted(load)
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border);
}
.page-title h1 { margin: 0; font-size: 20px; }
.page-title span { color: var(--muted); }
.actions { display: flex; gap: 8px; }
.panel-grid {
  display: grid;
  gap: 16px;
  grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
}
.setting-card {
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 14px;
  background: var(--card, #fff);
  box-shadow: var(--shadow, 0 10px 30px rgba(15, 23, 42, 0.06));
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.setting-header h3 { margin: 0 0 4px; font-size: 16px; }
.setting-header p { margin: 0; color: var(--muted); font-size: 13px; }
.field { display: grid; gap: 6px; }
.field label { font-size: 12px; color: var(--muted); }
.input {
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 10px 12px;
  width: 100%;
  background: var(--card, #fff);
  color: var(--text);
}
.btn {
  border: none;
  background: linear-gradient(135deg, #0ea5e9, #2563eb);
  color: #fff;
  padding: 9px 12px;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 600;
  box-shadow: 0 10px 25px rgba(37, 99, 235, 0.25);
}
.btn-secondary { background: rgba(148, 163, 184, 0.2); color: var(--text); box-shadow: none; }
.btn.btn-xs { padding: 6px 8px; font-size: 12px; }
.muted { color: #94a3b8; }
.table-wrapper { max-height: 260px; overflow: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th, .table td { border-bottom: 1px solid var(--border); padding: 8px; text-align: left; }
.add-tranche {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 10px;
  align-items: end;
}
.hint { color: #0ea5e9; font-size: 13px; margin-top: 4px; }
.card {
  border: 1px solid var(--border);
  border-radius: 14px;
  background: var(--card, #fff);
  padding: 16px;
  box-shadow: var(--shadow, 0 10px 30px rgba(15, 23, 42, 0.06));
}
</style>
