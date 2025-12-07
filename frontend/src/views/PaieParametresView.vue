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
          <label class="text-sm text-slate-600">CNAPS (%)</label>
          <input class="input" v-model="form.cnaps" type="number" step="0.01" required />
        </div>
        <div>
          <label class="text-sm text-slate-600">OSTIE (%)</label>
          <input class="input" v-model="form.ostie" type="number" step="0.01" required />
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-slate-600">IRSA base</label>
          <input class="input" v-model="form.irsa_base" type="number" step="0.01" required />
        </div>
        <div>
          <label class="text-sm text-slate-600">IRSA taux (%)</label>
          <input class="input" v-model="form.irsa_taux" type="number" step="0.01" required />
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-slate-600">Taux HS</label>
          <input class="input" v-model="form.hs_taux" type="number" step="0.01" required />
        </div>
        <div>
          <label class="text-sm text-slate-600">Prime transport</label>
          <input class="input" v-model="form.prime_transport" type="number" step="0.01" required />
        </div>
      </div>
      <div>
        <label class="text-sm text-slate-600">Prime présence</label>
        <input class="input" v-model="form.prime_presence" type="number" step="0.01" required />
      </div>
      <button class="btn w-fit" type="submit">Enregistrer</button>
      <p class="text-sm text-slate-500" v-if="message">{{ message }}</p>
    </form>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const form = ref({
  cnaps: 0,
  ostie: 0,
  irsa_base: 0,
  irsa_taux: 0,
  hs_taux: 0,
  prime_transport: 0,
  prime_presence: 0
})
const id = ref(null)
const message = ref('')

const load = async () => {
  const { data } = await api.get('/v1/paie-parametres')
  if (data) {
    id.value = data.id
    form.value = {
      cnaps: data.cnaps,
      ostie: data.ostie,
      irsa_base: data.irsa_base,
      irsa_taux: data.irsa_taux,
      hs_taux: data.hs_taux,
      prime_transport: data.prime_transport,
      prime_presence: data.prime_presence
    }
  }
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

onMounted(load)
</script>
