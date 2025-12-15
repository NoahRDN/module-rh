<template>
  <div class="flex flex-col gap-3 mb-4">
    <div class="flex flex-col gap-1">
      <h1 class="text-2xl font-semibold">Alertes automatiques</h1>
      <p class="text-sm text-slate-500">Congés en attente, congés proches, absences répétées</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <button class="btn btn-secondary" @click="fetchAlertes">Actualiser</button>
    </div>
  </div>

  <div class="grid gap-3 lg:grid-cols-2">
    <div class="card">
      <h3 class="text-lg font-semibold mb-2">Alertes en attente / proches</h3>
      <div class="flex flex-col gap-2">
        <div
          v-for="a in alertesFiltrees(['conge_en_attente', 'conge_proche'])"
          :key="a.demande_id + a.type"
          class="alert-card"
          :class="levelClass(a.level)"
        >
          <p class="font-semibold">
            <span v-if="a.employe?.matricule" class="badge">{{ a.employe.matricule }}</span>
            {{ a.message }}
          </p>
          <p class="text-xs text-slate-400" v-if="a.demande_id">Demande #{{ a.demande_id }}</p>
          <RouterLink
            v-if="a.demande_id"
            class="text-xs text-emerald-600 underline"
            :to="{ name: 'demandes-conges', query: { focus: a.demande_id } }"
          >
            Ouvrir la demande
          </RouterLink>
        </div>
        <p v-if="!alertesFiltrees(['conge_en_attente', 'conge_proche']).length" class="muted text-sm">Aucune alerte</p>
      </div>
    </div>

    <div class="card">
      <h3 class="text-lg font-semibold mb-2">Absences répétées</h3>
      <div class="flex flex-col gap-2">
        <div
          v-for="a in alertesFiltrees(['absences_maladie', 'absences_exceptionnelles'])"
          :key="(a.employe_id || '') + a.type"
          class="alert-card"
          :class="levelClass(a.level)"
        >
          <p class="font-semibold">
            <span v-if="a.employe?.matricule" class="badge">{{ a.employe.matricule }}</span>
            {{ a.message }}
          </p>
          <p class="text-xs text-slate-400" v-if="a.employe_id && !a.employe?.matricule">Employé ID {{ a.employe_id }}</p>
        </div>
        <p v-if="!alertesFiltrees(['absences_maladie', 'absences_exceptionnelles']).length" class="muted text-sm">Aucune alerte</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'

const alertes = ref([])
const fetchAlertes = async () => {
  const { data } = await api.get('/v1/alertes')
  alertes.value = data.data || []
}

const alertesFiltrees = (types) => alertes.value.filter((a) => types.includes(a.type))

const levelClass = (level) => {
  switch (level) {
    case 'danger':
      return 'alert-danger'
    case 'warning':
    default:
      return 'alert-warning'
  }
}

onMounted(fetchAlertes)
</script>

<style scoped>
.alert-card {
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 10px 12px;
  background: rgba(255, 255, 255, 0.03);
}
.alert-warning {
  border-color: rgba(234, 179, 8, 0.3);
  background: rgba(234, 179, 8, 0.08);
}
.alert-danger {
  border-color: rgba(248, 113, 113, 0.3);
  background: rgba(248, 113, 113, 0.08);
}
</style>
