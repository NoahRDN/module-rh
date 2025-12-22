<template>
  <div class="hero">
    <div>
      <p class="eyebrow">Surveillance continue</p>
      <h1>Alertes automatiques</h1>
      <p class="subtitle">Congés en attente, échéances proches, absences répétées</p>
      <div class="chips">
        <span class="pill">{{ stats.total }} alertes</span>
        <span class="pill pill-green">{{ stats.conges }} congés</span>
        <span class="pill pill-blue">{{ stats.absences }} absences</span>
        <span class="pill pill-red">{{ stats.critiques }} critiques</span>
      </div>
    </div>
    <div class="hero-actions">
      <button class="btn" @click="fetchAlertes">↻ Actualiser</button>
    </div>
  </div>

  <div class="grid gap-4 lg:grid-cols-2">
    <div class="card glass">
      <div class="section-header">
        <div>
          <p class="eyebrow">Congés</p>
          <h3>En attente / Proches</h3>
        </div>
      </div>
      <div class="timeline">
        <div
          v-for="a in alertesFiltrees(['conge_en_attente', 'conge_proche'])"
          :key="(a.demande_id || '') + a.type"
          class="timeline-item"
        >
          <div class="bullet" :class="levelClass(a.level)"></div>
          <div class="content">
            <div class="title">
              <span class="badge" v-if="a.employe?.matricule">{{ a.employe.matricule }}</span>
              <span class="message">{{ a.message }}</span>
            </div>
            <div class="meta">
              <span v-if="a.demande_id">Demande #{{ a.demande_id }}</span>
              <span class="type-tag">Type : {{ a.type }}</span>
            </div>
            <RouterLink
              v-if="a.demande_id"
              class="link"
              :to="{ name: 'demandes-conges', query: { focus: a.demande_id } }"
            >
              Ouvrir la demande →
            </RouterLink>
          </div>
        </div>
        <p v-if="!alertesFiltrees(['conge_en_attente', 'conge_proche']).length" class="empty">Aucune alerte</p>
      </div>
    </div>

    <div class="card glass">
      <div class="section-header">
        <div>
          <p class="eyebrow">Absences</p>
          <h3>Absences répétées</h3>
        </div>
      </div>
      <div class="timeline">
        <div
          v-for="a in alertesFiltrees(['absences_maladie', 'absences_exceptionnelles'])"
          :key="(a.employe_id || '') + a.type"
          class="timeline-item"
        >
          <div class="bullet" :class="levelClass(a.level)"></div>
          <div class="content">
            <div class="title">
              <span class="badge" v-if="a.employe?.matricule">{{ a.employe.matricule }}</span>
              <span class="message">{{ a.message }}</span>
            </div>
            <div class="meta">
              <span v-if="a.employe_id && !a.employe?.matricule">Employé #{{ a.employe_id }}</span>
              <span class="type-tag">Type : {{ a.type }}</span>
            </div>
          </div>
        </div>
        <p v-if="!alertesFiltrees(['absences_maladie', 'absences_exceptionnelles']).length" class="empty">Aucune alerte</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import api from '../services/api'

const alertes = ref([])
const fetchAlertes = async () => {
  const { data } = await api.get('/v1/alertes')
  alertes.value = data.data || []
}

const alertesFiltrees = (types) => alertes.value.filter((a) => types.includes(a.type))

const stats = computed(() => {
  const total = alertes.value.length
  const conges = alertesFiltrees(['conge_en_attente', 'conge_proche']).length
  const absences = alertesFiltrees(['absences_maladie', 'absences_exceptionnelles']).length
  const critiques = alertes.value.filter((a) => a.level === 'danger').length
  return { total, conges, absences, critiques }
})

const levelClass = (level) => {
  switch (level) {
    case 'danger':
      return 'danger'
    case 'warning':
    default:
      return 'warning'
  }
}

onMounted(fetchAlertes)
</script>

<style scoped>
.hero {
  display: flex;
  gap: 16px;
  justify-content: space-between;
  align-items: flex-start;
  padding: 18px 20px;
  border-radius: 16px;
  background: radial-gradient(circle at 20% 20%, rgba(59,130,246,0.15), transparent 40%),
              radial-gradient(circle at 80% 0%, rgba(16,185,129,0.12), transparent 35%),
              #0b1120;
  color: #e2e8f0;
  border: 1px solid rgba(148, 163, 184, 0.3);
}
.hero h1 { margin: 4px 0; font-size: 26px; }
.subtitle { color: #cbd5e1; margin: 0; }
.eyebrow { font-size: 12px; letter-spacing: 0.08em; color: #a5b4fc; text-transform: uppercase; margin: 0; }
.chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.pill {
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.2);
  color: #e2e8f0;
  font-size: 12px;
}
.pill-green { background: rgba(16, 185, 129, 0.18); color: #bbf7d0; }
.pill-blue { background: rgba(59, 130, 246, 0.18); color: #bfdbfe; }
.pill-red { background: rgba(248, 113, 113, 0.18); color: #fecdd3; }
.hero-actions .btn { background: #22c55e; color: #0b172a; border: none; }

.card.glass {
  border: 1px solid rgba(148, 163, 184, 0.2);
  background: linear-gradient(135deg, rgba(255,255,255,0.08), rgba(255,255,255,0.02));
  backdrop-filter: blur(6px);
}
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.section-header h3 { margin: 0; font-size: 18px; }
.timeline { display: flex; flex-direction: column; gap: 12px; }
.timeline-item { display: grid; grid-template-columns: 18px 1fr; gap: 10px; align-items: start; padding: 10px 0; border-bottom: 1px solid rgba(148, 163, 184, 0.15); }
.timeline-item:last-child { border-bottom: none; }
.bullet { width: 12px; height: 12px; border-radius: 50%; margin-top: 4px; background: rgba(234,179,8,0.9); box-shadow: 0 0 0 6px rgba(234,179,8,0.18); }
.bullet.danger { background: rgba(248,113,113,0.95); box-shadow: 0 0 0 6px rgba(248,113,113,0.16); }
.bullet.warning { background: rgba(234,179,8,0.95); box-shadow: 0 0 0 6px rgba(234,179,8,0.16); }
.content .title { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.message { font-weight: 600; color: #0f172a; }
:deep(body.dark) .message { color: #e2e8f0; }
.meta { display: flex; gap: 10px; color: #94a3b8; font-size: 12px; margin-top: 4px; }
.type-tag { background: rgba(148, 163, 184, 0.15); padding: 2px 8px; border-radius: 999px; }
.link { font-size: 12px; color: #22c55e; margin-top: 6px; display: inline-block; }
.empty { color: #94a3b8; font-size: 13px; text-align: center; padding: 12px 0; }
.badge {
  background: rgba(59, 130, 246, 0.15);
  color: #1d4ed8;
  border-radius: 999px;
  padding: 4px 8px;
  font-size: 12px;
}
:deep(body.dark) .badge { color: #bfdbfe; }

.btn {
  padding: 10px 14px;
  border-radius: 10px;
  border: 1px solid rgba(148, 163, 184, 0.4);
  background: rgba(255,255,255,0.08);
  color: #e2e8f0;
  cursor: pointer;
}
.btn:hover { border-color: rgba(255,255,255,0.5); }
.muted, .subtitle, .meta, .type-tag, .pill { transition: color 0.2s ease, background 0.2s ease; }

@media (max-width: 1024px) {
  .hero { flex-direction: column; }
  .timeline-item { grid-template-columns: 12px 1fr; }
}
</style>
