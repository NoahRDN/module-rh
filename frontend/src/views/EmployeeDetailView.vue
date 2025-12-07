<template>
  <div class="profile-shell">
    <button class="back-btn" @click="router.back()">← Retour</button>
    <div class="card header-card">
      <div class="profile-top">
        <div class="avatar">
          <img :src="photoUrl(employe)" alt="user" />
        </div>
        <div class="profile-main">
          <h4>{{ employe.nom }} {{ employe.prenom }}</h4>
          <div class="meta">
            <p>{{ employe.poste?.nom || 'Poste N/A' }}</p>
            <span class="divider"></span>
            <p>{{ employe.departement?.nom || 'Département N/A' }}</p>
          </div>
        </div>
        <div class="badge">
          Matricule : {{ employe.matricule }}
        </div>
      </div>
    </div>

    <div class="card info-card">
      <h3>Informations personnelles</h3>
      <div class="info-grid">
        <div class="info-item fit-email">
          <p class="label">Email</p>
          <p class="value">{{ employe.email || '—' }}</p>
        </div>
        <div class="info-item">
          <p class="label">Téléphone</p>
          <p class="value">{{ employe.telephone || '—' }}</p>
        </div>
        <div class="info-item">
          <p class="label">Adresse</p>
          <p class="value">{{ employe.adresse || '—' }}</p>
        </div>
        <div class="info-item">
          <p class="label">Date de naissance</p>
          <p class="value">{{ employe.date_naissance || '—' }}</p>
        </div>
        <div class="info-item">
          <p class="label">Date d'embauche</p>
          <p class="value">{{ employe.date_embauche || '—' }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api'

const route = useRoute()
const router = useRouter()
const employe = ref({})
const placeholder = 'https://via.placeholder.com/120?text=EMP'

const fetchEmploye = async () => {
  const { data } = await api.get(`/v1/employes/${route.params.id}`)
  employe.value = data
}

const photoUrl = (emp) => emp.photo || placeholder

onMounted(fetchEmploye)
</script>

<style scoped>
.profile-shell {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.card {
  background: var(--panel);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 18px;
  color: var(--text);
  box-shadow: var(--card-shadow);
}

.header-card {
  padding: 20px;
}

.profile-top {
  display: flex;
  align-items: center;
  gap: 18px;
  flex-wrap: wrap;
}

.avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  overflow: hidden;
  border: 1px solid var(--border);
  flex-shrink: 0;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-main h4 {
  margin: 0 0 6px;
  font-size: 20px;
}

.meta {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  font-size: 14px;
}

.divider {
  width: 1px;
  height: 14px;
  background: var(--border);
}

.badge {
  margin-left: auto;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(34, 197, 94, 0.12);
  color: var(--accent);
  font-weight: 600;
  font-size: 12px;
  border: 1px solid var(--border);
}

.info-card h3 {
  margin: 0 0 12px;
  font-size: 18px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
}

.info-item {
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: 12px;
  background: var(--bg-soft);
}

.info-item.fit-email {
  width: fit-content;
  max-width: 100%;
}

.info-item.fit-email .value {
  word-break: break-all;
}

.label {
  margin: 0 0 4px;
  color: var(--muted);
  font-size: 13px;
}

.value {
  margin: 0;
  font-weight: 600;
  color: var(--text);
}

.back-btn {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text);
  padding: 8px 12px;
  border-radius: 10px;
  cursor: pointer;
  margin-bottom: 10px;
  width: 10%;
}

.back-btn:hover {
  background: rgba(255, 255, 255, 0.04);
}
</style>
