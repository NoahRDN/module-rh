<template>
  <div class="alertes-page">
    <div class="hero">
      <div>
        <p class="eyebrow">Surveillance continue</p>
        <h1>Alertes automatiques</h1>
        <p class="subtitle">Congés en attente, échéances proches, absences répétées</p>
        <div class="chips">
          <span class="pill">{{ stats.total }} alertes</span>
          <span class="pill pill-green">{{ stats.conges }} congés</span>
          <span class="pill pill-blue">{{ stats.absences }} absences</span>
          <span class="pill">{{ stats.contrats }} contrats</span>
          <span class="pill">{{ stats.soldes }} congés non pris</span>
          <span class="pill pill-red">{{ stats.critiques }} critiques</span>
        </div>
      </div>
      <div class="hero-actions">
        <button class="btn" @click="fetchAlertes">↻ Actualiser</button>
      </div>
    </div>

    <div class="alertes-grid">
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

      <div class="card glass">
        <div class="section-header">
          <div>
            <p class="eyebrow">Contrats</p>
            <h3>Fin de contrat proche</h3>
          </div>
        </div>
        <div class="timeline">
          <div
            v-for="a in alertesFiltrees(['fin_contrat'])"
            :key="(a.contrat_id || a.employe_id || '') + a.type"
            class="timeline-item"
          >
            <div class="bullet" :class="levelClass(a.level)"></div>
            <div class="content">
              <div class="title">
                <span class="badge" v-if="a.employe_id">Employé #{{ a.employe_id }}</span>
                <span class="message">{{ a.message }}</span>
              </div>
              <div class="meta">
                <span v-if="a.date_fin">Fin le {{ a.date_fin }}</span>
                <span v-if="a.contrat_id">Contrat #{{ a.contrat_id }}</span>
                <span class="type-tag">Type : {{ a.type }}</span>
              </div>
              <RouterLink
                v-if="a.contrat_id"
                class="link"
                :to="{ name: 'contrat-detail', params: { id: a.contrat_id } }"
              >
                Ouvrir le contrat →
              </RouterLink>
            </div>
          </div>
          <p v-if="!alertesFiltrees(['fin_contrat']).length" class="empty">Aucune alerte</p>
        </div>
      </div>

      <div class="card glass">
        <div class="section-header">
          <div>
            <p class="eyebrow">Congés</p>
            <h3>Congés non pris</h3>
          </div>
        </div>
        <div class="timeline">
          <div
            v-for="a in alertesFiltrees(['conges_non_pris'])"
            :key="(a.employe_id || '') + a.type"
            class="timeline-item"
          >
            <div class="bullet" :class="levelClass(a.level)"></div>
            <div class="content">
              <div class="title">
                <span class="badge" v-if="a.employe_id">Employé #{{ a.employe_id }}</span>
                <span class="message">{{ a.message }}</span>
              </div>
              <div class="meta">
                <span v-if="a.solde">Solde : {{ a.solde }} jours</span>
                <span class="type-tag">Type : {{ a.type }}</span>
              </div>
            </div>
          </div>
          <p v-if="!alertesFiltrees(['conges_non_pris']).length" class="empty">Aucune alerte</p>
        </div>
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
  const contrats = alertesFiltrees(['fin_contrat']).length
  const soldes = alertesFiltrees(['conges_non_pris']).length
  const critiques = alertes.value.filter((a) => a.level === 'danger').length
  return { total, conges, absences, contrats, soldes, critiques }
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
.alertes-page {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.alertes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 18px;
}

.hero {
  display: flex;
  gap: 16px;
  justify-content: space-between;
  align-items: flex-start;
  padding: 18px 20px;
  border-radius: 16px;
  background: var(--panel);
  color: var(--text);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-lg);
}
.hero h1 { margin: 4px 0; font-size: 26px; }
.subtitle { color: var(--muted); margin: 0; }
.eyebrow { font-size: 12px; letter-spacing: 0.08em; color: var(--brand-500); text-transform: uppercase; margin: 0; }
.chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.pill {
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.12);
  color: var(--text);
  border: 1px solid rgba(148, 163, 184, 0.3);
  font-size: 12px;
}
.pill-green { background: #ecfdf3; color: #15803d; border-color: #a6f4c5; }
.pill-blue { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
.pill-red { background: #fef2f2; color: #b91c1c; border-color: #fecdd3; }
.hero-actions .btn { background: #22c55e; color: #0b172a; border: none; }

.card.glass {
  border: 1px solid var(--border);
  background: var(--panel);
  box-shadow: var(--shadow-sm);
  backdrop-filter: none;
}
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.section-header h3 { margin: 0; font-size: 18px; }
.timeline { display: flex; flex-direction: column; gap: 12px; }
.timeline-item { display: grid; grid-template-columns: 18px 1fr; gap: 10px; align-items: start; padding: 10px 0; border-bottom: 1px solid var(--border); }
.timeline-item:last-child { border-bottom: none; }
.bullet { width: 12px; height: 12px; border-radius: 50%; margin-top: 4px; background: rgba(234,179,8,0.9); box-shadow: 0 0 0 6px rgba(234,179,8,0.18); }
.bullet.danger { background: rgba(248,113,113,0.95); box-shadow: 0 0 0 6px rgba(248,113,113,0.16); }
.bullet.warning { background: rgba(234,179,8,0.95); box-shadow: 0 0 0 6px rgba(234,179,8,0.16); }
.content .title { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.message { font-weight: 600; color: var(--text); }
.meta { display: flex; gap: 10px; color: var(--muted); font-size: 12px; margin-top: 4px; }
.type-tag { background: rgba(148, 163, 184, 0.12); padding: 2px 8px; border-radius: 999px; color: var(--text); border: 1px solid rgba(148, 163, 184, 0.25); }
.link { font-size: 12px; color: #22c55e; margin-top: 6px; display: inline-block; }
.empty { color: #94a3b8; font-size: 13px; text-align: center; padding: 12px 0; }
.badge {
  background: rgba(59, 130, 246, 0.12);
  color: #1d4ed8;
  border: 1px solid rgba(59, 130, 246, 0.2);
  border-radius: 999px;
  padding: 4px 8px;
  font-size: 12px;
}

.btn {
  padding: 10px 14px;
  border-radius: 10px;
  border: 1px solid rgba(148, 163, 184, 0.25);
  background: linear-gradient(120deg, var(--brand-500), var(--brand-600));
  color: #fff;
  cursor: pointer;
}
.btn:hover { border-color: rgba(148, 163, 184, 0.5); box-shadow: var(--shadow-sm); }
.muted, .subtitle, .meta, .type-tag, .pill { transition: color 0.2s ease, background 0.2s ease; }

@media (max-width: 1024px) {
  .hero { flex-direction: column; }
  .timeline-item { grid-template-columns: 12px 1fr; }
  .alertes-grid { grid-template-columns: 1fr; }
}
</style>
