<template>
  <div class="flex flex-col gap-4">
    <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-2xl font-semibold">Créer un employé</h1>
        <p class="text-sm text-slate-500">Formulaire dédié, style FormElements</p>
      </div>
      <RouterLink to="/employes" class="btn btn-secondary whitespace-nowrap">← Retour à la liste</RouterLink>
    </div>

    <div class="card">
      <form class="space-y-4" @submit.prevent="createEmploye">
        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Matricule</label>
          <input class="input" v-model="form.matricule" placeholder="Matricule" required />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Nom</label>
            <input class="input" v-model="form.nom" placeholder="Nom" required />
          </div>
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Prénom</label>
            <input class="input" v-model="form.prenom" placeholder="Prénom" required />
          </div>
        </div>

        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Email</label>
          <input class="input" v-model="form.email" placeholder="Email" required type="email" />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Téléphone</label>
            <input class="input" v-model="form.telephone" placeholder="Téléphone (optionnel)" />
          </div>
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Adresse</label>
            <input class="input" v-model="form.adresse" placeholder="Adresse" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Date de naissance</label>
            <input class="input" type="date" v-model="form.date_naissance" />
          </div>
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Date d'embauche</label>
            <input class="input" type="date" v-model="form.date_embauche" required />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Département</label>
            <select class="select" v-model="form.departement_id">
              <option value="">(Optionnel)</option>
              <option v-for="dep in departements" :key="dep.id" :value="dep.id">{{ dep.nom }}</option>
            </select>
          </div>
          <div class="grid gap-1">
            <label class="text-sm text-slate-400">Poste</label>
            <select class="select" v-model="form.poste_id">
              <option value="">(Optionnel)</option>
              <option v-for="p in postes" :key="p.id" :value="p.id">{{ p.nom }}</option>
            </select>
          </div>
        </div>

        <div class="grid gap-1">
          <label class="text-sm text-slate-400">Photo (prévisualisation)</label>
          <div class="flex items-center gap-3">
            <img
              :src="photoPreview"
              alt="preview"
              class="h-12 w-12 rounded-full object-cover border border-slate-700"
              style="max-height: 48px; max-width: 48px;"
            />
            <input class="input" type="file" @change="onPhoto" accept="image/*" />
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button class="btn" type="submit">Enregistrer</button>
          <p class="muted" v-if="message">{{ message }}</p>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const departements = ref([])
const postes = ref([])
const message = ref('')
const photoPreview = ref('https://via.placeholder.com/80?text=EMP')

const form = ref({
  matricule: '',
  nom: '',
  prenom: '',
  email: '',
  telephone: '',
  adresse: '',
  date_naissance: '',
  date_embauche: '',
  departement_id: '',
  poste_id: '',
  photo: ''
})

const fetchRefs = async () => {
  const [deps, pos] = await Promise.all([
    api.get('/v1/departements'),
    api.get('/v1/postes')
  ])
  departements.value = deps.data.data || []
  postes.value = pos.data.data || []
}

const onPhoto = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = () => {
    form.value.photo = reader.result
    photoPreview.value = reader.result
  }
  reader.readAsDataURL(file)
}

const createEmploye = async () => {
  try {
    const payload = { ...form.value }
    payload.departement_id = payload.departement_id || null
    payload.poste_id = payload.poste_id || null
    payload.photo = payload.photo || null
    payload.date_naissance = payload.date_naissance || null
    await api.post('/v1/employes', payload)
    message.value = 'Employé créé'
    setTimeout(() => router.push('/employes'), 500)
  } catch (e) {
    message.value = 'Erreur lors de la création'
  }
}

onMounted(fetchRefs)
</script>
