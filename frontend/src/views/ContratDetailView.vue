<template>
  <div class="flex flex-col gap-3">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold">Fiche contrat</h1>
        <p class="text-sm text-slate-500">Détails du contrat #{{ contrat?.id }}</p>
      </div>
      <div class="flex gap-2">
        <RouterLink class="btn btn-secondary" to="/contrats-historiques">Historique</RouterLink>
        <button class="btn btn-secondary" @click="telechargerPdf">PDF</button>
        <button class="btn btn-secondary" @click="router.back()">← Retour</button>
      </div>
    </div>

    <div class="card">
      <div class="grid gap-3 md:grid-cols-2">
        <div>
          <p class="muted text-xs">Numéro</p>
          <p class="font-semibold">{{ contrat?.numero || '—' }}</p>
        </div>
        <div>
          <p class="muted text-xs">Type</p>
          <p class="font-semibold">{{ contrat?.type_contrat }}</p>
        </div>
        <div>
          <p class="muted text-xs">Employé</p>
          <p class="font-semibold">{{ contrat?.employe?.matricule }} - {{ contrat?.employe?.nom }} {{ contrat?.employe?.prenom }}</p>
          <p class="text-xs text-slate-400">{{ contrat?.employe?.poste?.nom }} · {{ contrat?.employe?.departement?.nom }}</p>
        </div>
        <div>
          <p class="muted text-xs">Salaire de base</p>
          <p class="font-semibold">{{ contrat?.salaire_base }} Ar</p>
        </div>
        <div>
          <p class="muted text-xs">Contrat</p>
          <p>{{ formatDate(contrat?.date_debut) }} → {{ formatDate(contrat?.date_fin) || '—' }}</p>
        </div>
        <div>
          <p class="muted text-xs">Période d'essai</p>
          <p>{{ formatDate(contrat?.periode_essai_debut) || '—' }} → {{ formatDate(contrat?.periode_essai_fin) || '—' }}</p>
        </div>
        <div>
          <p class="muted text-xs">Renouvelable</p>
          <span class="chip" :class="contrat?.renouvelable ? '' : 'muted'">{{ contrat?.renouvelable ? 'Oui' : 'Non' }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import api from '../services/api'

const route = useRoute()
const router = useRouter()
const contrat = ref(null)

const fetchContrat = async () => {
  const { data } = await api.get(`/v1/contrats/${route.params.id}`)
  contrat.value = data
}

const formatDate = (d) => (d ? String(d).split('T')[0] : '')

const telechargerPdf = async () => {
  if (!contrat.value?.id) return
  try {
    const { data, headers } = await api.get(`/v1/contrats/${contrat.value.id}/pdf`, {
      responseType: 'blob'
    })
    const blob = new Blob([data], { type: headers['content-type'] || 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `contrat_${contrat.value.id}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    // ignore
  }
}

onMounted(fetchContrat)
</script>
