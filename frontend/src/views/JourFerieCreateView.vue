<template>
  <div class="max-w-2xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold">Ajouter un jour férié</h1>
        <p class="text-sm text-slate-500">Définir un jour chômé dans le calendrier</p>
      </div>
      <RouterLink class="btn btn-secondary" to="/jours-feries">← Retour</RouterLink>
    </div>

    <div class="card">
      <form class="grid gap-3" @submit.prevent="save">
        <div class="grid gap-1">
          <label class="text-xs text-slate-400">Nom</label>
          <input class="input" v-model="form.nom" placeholder="Ex : Nouvel an" required />
        </div>
        <div class="grid gap-1">
          <label class="text-xs text-slate-400">Date</label>
          <input class="input" type="date" v-model="form.date" required />
        </div>
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" v-model="form.recurrent" />
          <span>Récurent chaque année</span>
        </label>
        <div class="flex gap-2">
          <button class="btn" type="submit" :disabled="loading">Enregistrer</button>
          <button class="btn btn-secondary" type="button" @click="resetForm">Annuler</button>
        </div>
        <p v-if="message" class="text-sm" :class="messageClass">{{ message }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const loading = ref(false)
const message = ref('')
const messageClass = ref('text-green-500')

const form = ref({
  nom: '',
  date: '',
  recurrent: false
})

const resetForm = () => {
  form.value = { nom: '', date: '', recurrent: false }
  message.value = ''
}

const save = async () => {
  message.value = ''
  messageClass.value = 'text-green-500'
  if (!form.value.nom || !form.value.date) {
    message.value = 'Nom et date sont requis.'
    messageClass.value = 'text-red-500'
    return
  }
  loading.value = true
  try {
    await api.post('/v1/jours-feries', form.value)
    router.push('/jours-feries')
  } catch (e) {
    messageClass.value = 'text-red-500'
    message.value = e.response?.data?.message || 'Erreur lors de la sauvegarde.'
  } finally {
    loading.value = false
  }
}
</script>
