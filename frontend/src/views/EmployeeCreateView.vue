<template>
  <div class="employee-create-page">
    <section class="hero hero-band hero-shared">
      <div class="hero-copy">
        <p class="hero-kicker">Employee onboarding</p>
        <h1>Nouvel employé</h1>
        <p class="hero-subtitle">
          Centralisez l’identité, les informations de contact et le rattachement métier dans un
          formulaire plus clair, mieux découpé et plus cohérent avec le reste de l’interface.
        </p>

        <div class="hero-pills">
          <span class="pill">Identité</span>
          <span class="pill">Contact</span>
          <span class="pill">Rattachement poste</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="action-row">
            <RouterLink to="/employes" class="btn btn-secondary">
              <AppIcon name="history" :size="18" />
              <span>Retour à l’annuaire</span>
            </RouterLink>
            <button class="btn" type="button" @click="createEmploye" :disabled="saving || loadingRefs">
              <AppIcon name="save" :size="18" />
              <span>{{ saving ? 'Enregistrement...' : 'Créer l’employé' }}</span>
            </button>
          </div>

          <div class="hero-meta-list">
            <p class="hero-meta">
              Postes disponibles:
              <strong>{{ formatInteger(postes.length) }}</strong>
            </p>
            <p class="hero-meta">
              Compte associé:
              <strong>Créé automatiquement</strong>
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="metric-grid">
      <article v-for="metric in metricCards" :key="metric.label" class="metric-card">
        <span class="metric-chip">{{ metric.tag }}</span>
        <p class="metric-label">{{ metric.label }}</p>
        <p class="metric-value">{{ metric.value }}</p>
        <p class="metric-caption">{{ metric.caption }}</p>
      </article>
    </section>

    <div v-if="loadingRefs" class="card loading-card">
      <p class="loading-title">Chargement des références…</p>
      <p class="muted">Les postes disponibles sont en cours de synchronisation.</p>
    </div>

    <template v-else>
      <section class="content-grid">
        <article class="card section-card form-card">
          <div class="section-heading">
            <div>
              <p class="section-kicker">Employee form</p>
              <h2>Informations du collaborateur</h2>
            </div>
            <span class="section-chip">Création</span>
          </div>

          <div v-if="message" class="status-banner" :class="messageType">
            <span class="status-dot"></span>
            <span>{{ message }}</span>
          </div>

          <form class="field-sections" @submit.prevent="createEmploye">
            <section class="field-group">
              <div class="field-group-head">
                <h3>Identité</h3>
                <p>Nom, prénom et identité administrative de base.</p>
              </div>

              <div class="fields-grid">
                <label class="field-card">
                  <span class="field-label">Nom</span>
                  <input v-model="form.nom" class="input" placeholder="Nom" required />
                </label>

                <label class="field-card">
                  <span class="field-label">Prénom</span>
                  <input v-model="form.prenom" class="input" placeholder="Prénom" required />
                </label>

                <label class="field-card">
                  <span class="field-label">Date de naissance</span>
                  <input v-model="form.date_naissance" class="input" type="date" />
                </label>

                <label class="field-card">
                  <span class="field-label">Date d’embauche</span>
                  <input v-model="form.date_embauche" class="input" type="date" required />
                </label>
              </div>
            </section>

            <section class="field-group">
              <div class="field-group-head">
                <h3>Contact</h3>
                <p>Coordonnées utilisées pour le compte associé et le suivi RH.</p>
              </div>

              <div class="fields-grid">
                <label class="field-card">
                  <span class="field-label">Email</span>
                  <input v-model="form.email" class="input" placeholder="Email" required type="email" />
                </label>

                <label class="field-card">
                  <span class="field-label">Téléphone</span>
                  <input v-model="form.telephone" class="input" placeholder="Téléphone" />
                </label>

                <label class="field-card full">
                  <span class="field-label">Adresse</span>
                  <input v-model="form.adresse" class="input" placeholder="Adresse" />
                </label>
              </div>
            </section>

            <section class="field-group">
              <div class="field-group-head">
                <h3>Rattachement métier</h3>
                <p>Le département sera automatiquement déduit du poste si nécessaire.</p>
              </div>

              <div class="fields-grid">
                <label class="field-card full">
                  <span class="field-label">Poste</span>
                  <select v-model="form.poste_id" class="select" required>
                    <option value="">Sélectionner un poste</option>
                    <option v-for="poste in postes" :key="poste.id" :value="poste.id">{{ poste.nom }}</option>
                  </select>
                </label>
              </div>
            </section>

            <section class="field-group">
              <div class="field-group-head">
                <h3>Photo</h3>
                <p>Ajoutez une photo pour la fiche employé et l’annuaire.</p>
              </div>

              <div class="photo-uploader">
                <img :src="photoPreview" alt="preview" class="photo-preview" />
                <div class="photo-copy">
                  <p class="photo-title">Aperçu du profil</p>
                  <p class="photo-subtitle">Formats image standard acceptés via import local.</p>
                  <input class="input" type="file" @change="onPhoto" accept="image/*" />
                </div>
              </div>
            </section>

            <div class="submit-row">
              <button class="btn" type="submit" :disabled="saving">
                <AppIcon name="save" :size="18" />
                <span>{{ saving ? 'Enregistrement...' : 'Enregistrer' }}</span>
              </button>
              <RouterLink to="/employes" class="btn btn-secondary">Annuler</RouterLink>
            </div>
          </form>
        </article>

        <aside class="card section-card insights-card">
          <div class="section-heading compact">
            <div>
              <p class="section-kicker">Overview</p>
              <h2>Résumé création</h2>
            </div>
          </div>

          <p class="summary-intro">
            Vérifiez rapidement l’état du formulaire avant enregistrement et contrôlez les informations
            clés de la future fiche employé.
          </p>

          <div class="overview-grid">
            <article v-for="card in overviewCards" :key="card.label" class="overview-card">
              <span class="overview-chip">{{ card.tag }}</span>
              <p class="overview-label">{{ card.label }}</p>
              <p class="overview-value overview-value--wrap">{{ card.value }}</p>
              <p class="overview-copy overview-value--wrap">{{ card.copy }}</p>
            </article>
          </div>

          <div class="notes-card">
            <h3>Repères rapides</h3>
            <ul>
              <li v-for="note in notes" :key="note">{{ note }}</li>
            </ul>
          </div>
        </aside>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import api from '../services/api'
import AppIcon from '../components/ui/AppIcon.vue'

const router = useRouter()
const postes = ref([])
const message = ref('')
const messageType = ref('info')
const saving = ref(false)
const loadingRefs = ref(false)
const photoPreview = ref('https://via.placeholder.com/160?text=EMP')

const form = ref({
  nom: '',
  prenom: '',
  email: '',
  telephone: '',
  adresse: '',
  date_naissance: '',
  date_embauche: '',
  poste_id: '',
  photo: '',
})

const completedFields = computed(() =>
  [
    form.value.nom,
    form.value.prenom,
    form.value.email,
    form.value.date_embauche,
    form.value.poste_id,
    form.value.telephone,
    form.value.adresse,
    form.value.date_naissance,
    form.value.photo,
  ].filter(Boolean).length,
)

const selectedPosteLabel = computed(() => {
  const poste = postes.value.find((item) => String(item.id) === String(form.value.poste_id))
  return poste?.nom || 'Non sélectionné'
})

const metricCards = computed(() => [
  {
    label: 'Champs complétés',
    value: formatInteger(completedFields.value),
    caption: 'Lecture rapide de progression du formulaire',
    tag: 'Progress',
  },
  {
    label: 'Postes disponibles',
    value: formatInteger(postes.value.length),
    caption: 'Référentiel chargé pour le rattachement métier',
    tag: 'Roles',
  },
  {
    label: 'Compte utilisateur',
    value: form.value.email ? 'Prêt' : 'En attente',
    caption: 'Le compte employé sera généré à partir de l’email',
    tag: 'Access',
  },
  {
    label: 'Photo profil',
    value: form.value.photo ? 'Ajoutée' : 'Optionnelle',
    caption: 'Aperçu disponible avant validation',
    tag: 'Profile',
  },
])

const overviewCards = computed(() => [
  {
    label: 'Identité',
    value: `${form.value.nom || '—'} ${form.value.prenom || ''}`.trim() || 'Non renseignée',
    copy: 'Nom complet du collaborateur en cours de création.',
    tag: 'Identity',
  },
  {
    label: 'Poste sélectionné',
    value: selectedPosteLabel.value,
    copy: 'Le département sera hérité du poste si besoin.',
    tag: 'Role',
  },
  {
    label: 'Email',
    value: form.value.email || 'Non renseigné',
    copy: 'Sera utilisé pour le compte employé créé automatiquement.',
    tag: 'Access',
  },
  {
    label: 'Date d’embauche',
    value: form.value.date_embauche || 'Non renseignée',
    copy: 'Champ essentiel pour la création effective du profil.',
    tag: 'HR',
  },
])

const notes = computed(() => [
  form.value.email
    ? 'Un compte utilisateur employé sera créé automatiquement à partir de cet email.'
    : 'Sans email, aucun compte utilisateur associé ne pourra être exploité correctement.',
  form.value.poste_id
    ? 'Le département sera déduit automatiquement du poste sélectionné.'
    : 'Le poste reste obligatoire pour rattacher correctement le collaborateur.',
  form.value.photo
    ? 'Une photo de profil est déjà prête pour la fiche employé.'
    : 'La photo reste facultative mais améliore la lisibilité dans l’annuaire.',
])

const fetchAllPostes = async () => {
  loadingRefs.value = true
  try {
    const collected = []
    let page = 1
    let lastPage = 1

    do {
      const { data } = await api.get('/v1/postes', { params: { page } })
      const rows = data.data || []
      collected.push(...rows)

      if (data.meta) {
        lastPage = data.meta.last_page
      } else if (data.last_page !== undefined) {
        lastPage = data.last_page
      } else {
        lastPage = 1
      }

      page += 1
    } while (page <= lastPage)

    postes.value = collected
  } finally {
    loadingRefs.value = false
  }
}

const onPhoto = (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = () => {
    form.value.photo = reader.result
    photoPreview.value = reader.result
  }
  reader.readAsDataURL(file)
}

const createEmploye = async () => {
  if (saving.value) return

  saving.value = true
  message.value = ''

  try {
    const payload = { ...form.value }
    payload.poste_id = payload.poste_id || null
    payload.photo = payload.photo || null
    payload.date_naissance = payload.date_naissance || null

    await api.post('/v1/employes', payload)

    message.value = 'Employé créé avec succès.'
    messageType.value = 'success'
    setTimeout(() => router.push('/employes'), 500)
  } catch (error) {
    message.value = error.response?.data?.message || 'Erreur lors de la création.'
    messageType.value = 'warning'
  } finally {
    saving.value = false
  }
}

const formatInteger = (value) => new Intl.NumberFormat('fr-FR').format(Number(value || 0))

onMounted(fetchAllPostes)
</script>

<style scoped>
.field-group-head h3 {
  margin: 8px 0 0;
  font-weight: 800;
  letter-spacing: 0;
}

.field-sections {
  display: grid;
  gap: 22px;
}

.field-group {
  display: grid;
  gap: 16px;
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: 22px;
  background: rgba(248, 250, 252, 0.78);
}

body[data-theme='dark'] .field-group {
  background: rgba(15, 23, 42, 0.46);
}

.field-group-head {
  display: grid;
  gap: 6px;
}

.field-group-head h3 {
  font-size: 1.1rem;
}

.field-group-head p,
.photo-subtitle {
  color: var(--muted);
  font-size: 0.92rem;
  line-height: 1.6;
}

.fields-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.photo-uploader {
  display: flex;
  align-items: center;
  gap: 18px;
}

.photo-preview {
  width: 88px;
  height: 88px;
  flex: none;
  border-radius: 22px;
  object-fit: cover;
  border: 1px solid var(--border);
  background: rgba(248, 250, 252, 0.8);
}

.photo-copy {
  display: grid;
  gap: 8px;
  width: 100%;
}

.photo-title {
  margin: 0;
  font-weight: 700;
}

.photo-subtitle {
  margin: 0;
}

.submit-row {
  display: flex;
  gap: 10px;
}

.submit-row > * {
  width: fit-content;
}

@media (max-width: 900px) {
  .fields-grid {
    grid-template-columns: 1fr;
  }

  .photo-uploader {
    align-items: flex-start;
    flex-direction: column;
  }
}

@media (max-width: 680px) {
  .submit-row {
    flex-direction: column;
    align-items: stretch;
  }

  .submit-row > * {
    width: 100%;
  }
}
</style>
