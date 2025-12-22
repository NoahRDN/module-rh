<template>
  <div class="self-documents">
    <div class="page-header">
      <div>
        <h1>📁 Mes documents</h1>
        <p class="subtitle">Fichiers personnels et administratifs</p>
      </div>
      <button class="btn btn-secondary" @click="loadDocuments">Actualiser</button>
    </div>

    <div v-if="loading" class="card empty">Chargement...</div>

    <div v-else class="grid cards">
      <div v-for="doc in documents" :key="doc.id" class="card doc-card">
        <div class="doc-header">
          <div>
            <h3>{{ doc.type?.libelle || doc.type || 'Document' }}</h3>
            <p class="muted">Ajouté le {{ formatDate(doc.created_at) }}</p>
          </div>
          <span class="badge">{{ doc.statut || 'Disponible' }}</span>
        </div>
        <p class="muted">{{ doc.description || '—' }}</p>
        <div class="doc-meta">
          <span>Nom : {{ doc.nom || doc.filename || '—' }}</span>
          <span>Taille : {{ formatSize(doc.taille) }}</span>
        </div>
        <div class="doc-actions">
          <button class="btn btn-secondary btn-sm" @click="telecharger(doc)">📥 Télécharger</button>
        </div>
      </div>
      <div v-if="!documents.length" class="card empty">Aucun document</div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../../services/api'

const loading = ref(false)
const documents = ref([])

const loadDocuments = async () => {
  loading.value = true
  try {
    const res = await api.get('/v1/self-service/documents')
    documents.value = res.data?.data || res.data || []
  } catch (e) {
    console.error('Erreur documents:', e)
  } finally {
    loading.value = false
  }
}

const telecharger = async (doc) => {
  try {
    const response = await api.get(`/v1/documents/${doc.id}`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', doc.nom || doc.filename || `document_${doc.id}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (e) {
    alert('Téléchargement impossible')
  }
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('fr-FR') : '—'
const formatSize = (s) => {
  if (!s) return '—'
  const kb = s / 1024
  if (kb < 1024) return `${kb.toFixed(1)} Ko`
  return `${(kb / 1024).toFixed(1)} Mo`
}

onMounted(loadDocuments)
</script>

<style scoped>
.self-documents {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 16px;
}
.subtitle { color: #64748b; margin: 0; }
.grid.cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 12px;
}
.card {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px;
  background: #fff;
}
.doc-card { display: flex; flex-direction: column; gap: 8px; }
.doc-header { display: flex; justify-content: space-between; align-items: center; }
.badge { background: #e0e7ff; color: #4f46e5; padding: 4px 8px; border-radius: 8px; font-size: 12px; }
.muted { color: #94a3b8; font-size: 13px; }
.doc-meta { display: flex; flex-direction: column; gap: 4px; color: #475569; font-size: 13px; }
.doc-actions { display: flex; justify-content: flex-end; }
.btn { padding: 8px 12px; border-radius: 8px; border: none; cursor: pointer; }
.btn-primary { background: #2563eb; color: white; }
.btn-secondary { background: #e2e8f0; color: #1e293b; }
.btn-sm { padding: 6px 10px; font-size: 13px; }
.empty { text-align: center; color: #94a3b8; padding: 16px; grid-column: 1 / -1; }
</style>
