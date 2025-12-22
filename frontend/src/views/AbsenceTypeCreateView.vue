<template>
  <div class="page">
    <div class="page-header">
      <h1>Nouveau type d'absence</h1>
      <RouterLink class="btn btn-secondary btn-sm" to="/absences-types">← Retour</RouterLink>
    </div>
    <div class="card">
      <form class="grid gap-3" @submit.prevent="submit">
        <input class="input" v-model="form.libelle" placeholder="Libellé (ex: Congé payé)" required />
        <input class="input" v-model="form.code" placeholder="Code (ex: PAYE)" required />
        <select class="select" v-model="form.frequence_id">
          <option value="">Fréquence (optionnel)</option>
          <option v-for="f in frequences" :key="f.id" :value="f.id">{{ f.code }} - {{ f.libelle }}</option>
        </select>
        <textarea class="input" rows="3" v-model="form.description" placeholder="Description"></textarea>
        <label class="text-sm flex items-center gap-2"><input type="checkbox" v-model="form.paye" /> Payant</label>
        <label class="text-sm flex items-center gap-2"><input type="checkbox" v-model="form.utilise_solde" /> Utilise un solde</label>
        <label class="text-sm flex items-center gap-2"><input type="checkbox" v-model="form.cumulable" /> Cumulable</label>
        <input class="input" type="number" min="0" v-model="form.jours_forfait" placeholder="Jours forfait (optionnel)" />
        <input class="input" type="number" min="0" v-model="form.limite" placeholder="Limite (nombre, optionnel)" />
        <select class="select" v-model="form.limite_frequence_id">
          <option value="">Fréquence de limite (optionnel)</option>
          <option v-for="f in frequences" :key="f.id" :value="f.id">{{ f.code }} - {{ f.libelle }}</option>
        </select>
        <div v-if="form.cumulable" class="grid gap-2 md:grid-cols-2">
          <input class="input" type="number" min="0" v-model="form.cumulable_duree" placeholder="Durée de cumul" />
          <select class="select" v-model="form.cumulable_frequence_id">
            <option value="">Fréquence de cumul</option>
            <option v-for="f in frequences" :key="f.id" :value="f.id">{{ f.code }} - {{ f.libelle }}</option>
          </select>
        </div>
        <div class="flex gap-2 items-center">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? 'Création...' : 'Enregistrer' }}</button>
          <span class="muted" v-if="message">{{ message }}</span>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const loading = ref(false)
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

const loadFreq = async () => {
  const { data } = await api.get('/v1/frequences-conges')
  frequences.value = data.data || data || []
}

const submit = async () => {
  loading.value = true
  message.value = ''
  try {
    await api.post('/v1/types-conges', form.value)
    router.push('/absences-types')
  } catch (e) {
    message.value = e.response?.data?.message || 'Erreur lors de la création'
  } finally {
    loading.value = false
  }
}

onMounted(loadFreq)
</script>

<style scoped>
.page { padding: 20px; max-width: 800px; margin: 0 auto; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; background: #fff; box-shadow: 0 6px 20px rgba(15,23,42,0.08); }
.input, .select { border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; }
.btn { padding: 10px 16px; border: none; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; cursor: pointer; }
.btn-secondary { background: #e2e8f0; color: #0f172a; }
.muted { color: #94a3b8; }
</style>
