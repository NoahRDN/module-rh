<template>
  <div class="flex items-center justify-between mb-4">
    <div>
      <h1 class="text-2xl font-semibold">Génération Paie</h1>
      <p class="text-sm text-slate-500">Calcul brut/net mensuel</p>
    </div>
  </div>

  <div class="grid gap-4 lg:grid-cols-3">
    <div class="card">
      <h2 class="text-lg font-semibold">Paramètres</h2>
      <p class="text-sm text-slate-500 mb-3">Employé & mois</p>
      <form class="grid" style="gap: 10px;" @submit.prevent="generer">
        <select class="select" v-model="form.employe_id" required>
          <option value="">Employé</option>
          <option v-for="emp in employes" :key="emp.id" :value="emp.id">{{ emp.matricule }} - {{ emp.nom }}</option>
        </select>
        <input class="input" type="month" v-model="form.mois" required />
        <button class="btn" type="submit">Générer</button>
        <p class="muted" v-if="message">{{ message }}</p>
      </form>
    </div>

    <div class="card lg:col-span-2" v-if="paie">
      <h2 class="text-lg font-semibold">Résultat</h2>
      <div class="grid grid-cols-2 gap-2 text-sm mt-3">
        <p><strong>Brut :</strong> {{ paie.total_brut }}</p>
        <p><strong>Net :</strong> {{ paie.net_a_payer }}</p>
        <p><strong>HS :</strong> {{ paie.heures_supplementaires }} h ({{ paie.montant_hs }})</p>
        <p><strong>Retenues :</strong> {{ paie.total_retenues }}</p>
        <p><strong>CNAPS :</strong> {{ paie.retenue_cnaps }}</p>
        <p><strong>OSTIE :</strong> {{ paie.retenue_ostie }}</p>
        <p><strong>IRSA :</strong> {{ paie.retenue_irsa }}</p>
      </div>
      <button class="btn mt-3" @click="downloadPdf" :disabled="downloading">
        {{ downloading ? 'Téléchargement...' : 'Télécharger le PDF' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../services/api'

const employes = ref([])
const paie = ref(null)
const message = ref('')
const downloading = ref(false)

const form = ref({
  employe_id: '',
  mois: ''
})

const fetchEmployes = async () => {
  const { data } = await api.get('/v1/employes')
  employes.value = data.data || []
}

const generer = async () => {
  try {
    const { data } = await api.post('/v1/paies/generer', form.value)
    paie.value = data.paie
    message.value = data.message
  } catch (e) {
    message.value = 'Erreur lors du calcul'
  }
}

const downloadPdf = async () => {
  if (!paie.value?.id) return
  try {
    downloading.value = true
    const { data } = await api.get(`/v1/paies/${paie.value.id}/pdf`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `bulletin_paie_${paie.value.employe_id}_${paie.value.mois}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    message.value = 'Erreur lors du téléchargement'
  } finally {
    downloading.value = false
  }
}

onMounted(fetchEmployes)
</script>
