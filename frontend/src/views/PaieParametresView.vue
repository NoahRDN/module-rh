<template>
  <div class="flex items-center justify-between mb-4">
    <div>
      <h1 class="text-2xl font-semibold">Paramètres Paie</h1>
      <p class="text-sm text-slate-500">Taux légaux & primes</p>
    </div>
  </div>

  <div class="bg-white shadow rounded-2xl p-4 border border-slate-100">
    <form class="grid gap-3" @submit.prevent="save">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-slate-600">Plafond CNAPS</label>
          <input class="input" v-model="form.cnaps_plafond" type="number" step="0.01" required />
        </div>
        <div>
          <label class="text-sm text-slate-600">CNAPS employé (%)</label>
          <input class="input" v-model="form.cnaps_taux_employe" type="number" step="0.01" required />
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-slate-600">CNAPS employeur (%)</label>
          <input class="input" v-model="form.cnaps_taux_employeur" type="number" step="0.01" required />
        </div>
        <div>
          <label class="text-sm text-slate-600">OSTIE employé (%)</label>
          <input class="input" v-model="form.ostie_taux_employe" type="number" step="0.01" required />
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-slate-600">OSTIE employeur (%)</label>
          <input class="input" v-model="form.ostie_taux_employeur" type="number" step="0.01" required />
        </div>
      </div>
      <!-- IRSA géré via tranches ci-dessous -->
      <button class="btn w-fit" type="submit">Enregistrer</button>
      <p class="text-sm text-slate-500" v-if="message">{{ message }}</p>

      <div class="mt-4">
        <h3 class="text-sm font-semibold text-slate-700 mb-2">Tranches IRSA</h3>
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
            <tr v-if="!tranches.length"><td colspan="4" class="text-slate-400 text-sm">Aucune tranche</td></tr>
          </tbody>
        </table>

        <div class="grid grid-cols-4 gap-2 items-end">
          <div>
            <label class="text-sm text-slate-600">Min</label>
            <input class="input" v-model="newTranche.min_base" type="number" step="0.01" />
          </div>
          <div>
            <label class="text-sm text-slate-600">Max</label>
            <input class="input" v-model="newTranche.max_base" type="number" step="0.01" />
          </div>
          <div>
            <label class="text-sm text-slate-600">Taux (%)</label>
            <input class="input" v-model="newTranche.taux" type="number" step="0.01" />
          </div>
          <button class="btn btn-secondary" @click="addTranche">Ajouter tranche</button>
        </div>
      </div>
    </form>
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
