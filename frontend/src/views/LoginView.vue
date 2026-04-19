<template>
  <div class="login-page">
    <section class="hero">
      <div class="hero-copy">
        <p class="hero-kicker">Human resources platform</p>
        <h1>Portail RH unifié</h1>
        <p class="hero-subtitle">
          Accédez au tableau de bord, à l'annuaire des employés, aux départements et aux postes depuis
          une interface cohérente, plus claire et mieux hiérarchisée.
        </p>

        <div class="hero-pills">
          <span class="pill">Tableau de bord</span>
          <span class="pill">Employés</span>
          <span class="pill">Départements</span>
          <span class="pill">Postes</span>
        </div>
      </div>

      <div class="hero-actions">
        <div class="filters-panel">
          <div class="section-heading compact">
            <div>
              <p class="section-kicker">Accès</p>
              <h2>Un seul point d'entrée</h2>
            </div>
            <span class="section-chip">Sécurisé</span>
          </div>

          <p class="section-copy">
            RH, managers et employés accèdent au même produit avec une redirection adaptée selon leur
            rôle.
          </p>

          <div class="access-list">
            <article v-for="item in loginHighlights" :key="item.title" class="access-item">
              <div class="access-icon">
                <AppIcon :name="item.icon" :size="18" />
              </div>
              <div class="access-copy">
                <p class="access-title">{{ item.title }}</p>
                <span class="access-text">{{ item.text }}</span>
              </div>
            </article>
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

    <section class="content-grid">
      <article class="card section-card auth-card">
        <div class="section-heading">
          <div>
            <p class="section-kicker">Connexion</p>
            <h2>Accéder au module RH</h2>
          </div>

          <div class="auth-badge">
            <AppIcon name="shield" :size="18" />
            <span>Session sécurisée</span>
          </div>
        </div>

        <p class="section-copy">
          Renseignez votre identifiant et votre mot de passe pour ouvrir votre espace de travail.
        </p>

        <form class="auth-form" @submit.prevent="onLogin">
          <label class="field-card">
            <span class="field-label">Identifiant</span>
            <input
              v-model="identifiant"
              class="input"
              type="text"
              autocomplete="username"
              placeholder="Votre identifiant RH"
              required
            />
          </label>

          <label class="field-card">
            <span class="field-label">Mot de passe</span>
            <input
              v-model="mdp"
              class="input"
              type="password"
              autocomplete="current-password"
              placeholder="Votre mot de passe"
              required
            />
          </label>

          <div class="auth-footer">
            <p class="auth-note">
              La redirection après connexion dépend de votre rôle dans le module.
            </p>

            <button class="btn auth-submit" type="submit" :disabled="loading">
              <AppIcon :name="loading ? 'refresh' : 'logout'" :size="18" />
              <span>{{ loading ? 'Connexion en cours...' : 'Se connecter' }}</span>
            </button>
          </div>

          <p v-if="error" class="feedback error">{{ error }}</p>
        </form>
      </article>

      <aside class="card section-card insights-card">
        <div class="section-heading compact">
          <div>
            <p class="section-kicker">Références</p>
            <h2>Codes visuels repris</h2>
          </div>
        </div>

        <p class="summary-intro">
          Le login s'aligne sur les pages de référence en reprenant les mêmes marqueurs visuels.
        </p>

        <div class="overview-grid">
          <article v-for="item in accessCards" :key="item.title" class="overview-card">
            <div class="overview-top">
              <div class="overview-icon">
                <AppIcon :name="item.icon" :size="18" />
              </div>
              <span class="overview-chip">{{ item.tag }}</span>
            </div>
            <p class="overview-label">{{ item.title }}</p>
            <p class="overview-copy">{{ item.text }}</p>
          </article>
        </div>

        <div class="notes-card">
          <h3>Repères rapides</h3>
          <ul>
            <li v-for="note in securityNotes" :key="note">{{ note }}</li>
          </ul>
        </div>
      </aside>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppIcon from '../components/ui/AppIcon.vue'
import api from '../services/api'

const router = useRouter()
const identifiant = ref('')
const mdp = ref('')
const error = ref('')
const loading = ref(false)

const loginHighlights = [
  {
    icon: 'grid',
    title: 'Tableau de bord',
    text: 'Une entrée claire vers les vues de pilotage RH.',
  },
  {
    icon: 'users',
    title: 'Gestion employés',
    text: "Accès direct à l'annuaire et aux fiches collaborateurs.",
  },
  {
    icon: 'building',
    title: 'Structure interne',
    text: 'Navigation cohérente entre départements et organisations.',
  },
]

const metricCards = [
  {
    label: 'Espaces métier',
    value: '4',
    caption: 'Dashboard, employés, départements et postes servent de base au design.',
    tag: 'Design',
  },
  {
    label: "Point d'entrée",
    value: '1',
    caption: 'Une page plus propre pour commencer le parcours utilisateur.',
    tag: 'Flow',
  },
  {
    label: 'Accès rôle',
    value: '3',
    caption: 'RH, manager et employé sont redirigés selon leur profil.',
    tag: 'Roles',
  },
  {
    label: 'Interface',
    value: '100%',
    caption: 'Même grammaire visuelle que le reste du produit.',
    tag: 'UI',
  },
]

const accessCards = [
  {
    icon: 'sparkles',
    tag: 'Hero',
    title: "Bloc d'introduction",
    text: 'Grand hero teinté, chips et hiérarchie proches du tableau de bord.',
  },
  {
    icon: 'briefcase',
    tag: 'Cards',
    title: 'Cartes structurées',
    text: 'Panneaux, arrondis et ombres reprennent le style des pages métier.',
  },
  {
    icon: 'shield',
    tag: 'Forms',
    title: 'Formulaire cadre',
    text: 'Champs et CTA sont intégrés dans une vraie carte produit.',
  },
  {
    icon: 'building',
    tag: 'Consistency',
    title: 'Cohésion visuelle',
    text: "Le login ne semble plus isolé du reste de l'application.",
  },
]

const securityNotes = [
  'Le bouton reprend le style principal utilisé sur les autres pages.',
  "Les zones d'information sont découpées comme dans les vues employés et départements.",
  'La page reste responsive pour desktop et mobile.',
]

const onLogin = async () => {
  error.value = ''
  loading.value = true

  try {
    const { data } = await api.post('/login', {
      identifiant: identifiant.value,
      mdp: mdp.value,
    })

    const token = data?.access_token || data?.token

    if (!token) {
      error.value = 'Token absent dans la réponse.'
      return
    }

    localStorage.setItem('token', token)
    localStorage.setItem('role', data?.user?.role ?? '')
    localStorage.setItem('user_identifiant', data?.user?.identifiant ?? identifiant.value ?? '')
    localStorage.setItem('user_name', data?.user?.name ?? '')

    const role = data?.user?.role

    if (role === 'employe') {
      router.push('/self-service/profil')
      return
    }

    router.push('/dashboard')
  } catch (e) {
    error.value = e?.response?.data?.message || 'Identifiants invalides.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-height: 100vh;
  padding: 28px;
}

.hero {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 18px;
  padding: 28px;
  border: 1px solid rgba(79, 70, 229, 0.14);
  border-radius: 30px;
  background: var(--purple-100);
  box-shadow: var(--shadow-lg);
}

body[data-theme='dark'] .hero {
  background:
    linear-gradient(135deg, rgba(79, 70, 229, 0.18), rgba(15, 23, 42, 0)),
    rgba(15, 23, 42, 0.88);
}

.hero-copy {
  max-width: 760px;
}

.hero-kicker,
.section-kicker,
.metric-label,
.metric-caption,
.summary-intro,
.overview-label,
.overview-copy,
.section-copy,
.access-text,
.auth-note,
.feedback {
  margin: 0;
}

.hero-kicker,
.section-kicker {
  color: var(--brand-600);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.hero h1,
.section-heading h2 {
  margin: 8px 0 0;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.hero h1 {
  font-size: clamp(2rem, 3vw, 2.9rem);
  max-width: 720px;
}

.hero-subtitle {
  margin: 12px 0 0;
  max-width: 700px;
  color: var(--muted);
  font-size: 1rem;
  line-height: 1.7;
}

.hero-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 18px;
}

.hero-actions {
  display: flex;
  min-width: 320px;
  max-width: 360px;
  flex-direction: column;
  gap: 12px;
}

.filters-panel {
  display: grid;
  gap: 14px;
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.76);
}

body[data-theme='dark'] .filters-panel {
  background: rgba(15, 23, 42, 0.72);
}

.section-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.section-heading.compact {
  margin-bottom: 2px;
}

.section-heading h2 {
  font-size: 1.48rem;
}

.section-copy {
  color: var(--muted);
  font-size: 0.95rem;
  line-height: 1.65;
}

.section-chip,
.metric-chip,
.overview-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  padding: 7px 12px;
  border-radius: 999px;
  border: 1px solid rgba(79, 70, 229, 0.12);
  background: rgba(79, 70, 229, 0.1);
  color: var(--brand-600);
  font-size: 0.76rem;
  font-weight: 700;
}

.access-list {
  display: grid;
  gap: 12px;
}

.access-item {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 14px;
  border: 1px solid var(--border);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.72);
}

body[data-theme='dark'] .access-item {
  background: rgba(15, 23, 42, 0.72);
}

.access-icon,
.overview-icon,
.auth-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.access-icon,
.overview-icon {
  width: 40px;
  height: 40px;
  border-radius: 14px;
  background: rgba(79, 70, 229, 0.12);
  color: var(--brand-600);
  flex: none;
}

.access-copy {
  display: grid;
  gap: 4px;
}

.access-title,
.metric-value,
.overview-label,
.notes-card h3 {
  margin: 0;
}

.access-title {
  font-weight: 700;
  color: var(--text);
}

.access-text {
  color: var(--muted);
  font-size: 0.9rem;
  line-height: 1.55;
}

.metric-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.metric-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 10px;
  padding: 18px 20px;
  border: 1px solid var(--border);
  border-radius: 24px;
  background: var(--panel);
  box-shadow: var(--shadow-sm);
}

.metric-label {
  color: var(--muted);
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.metric-value {
  font-size: 1.82rem;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.metric-caption {
  color: var(--muted);
  font-size: 0.9rem;
  line-height: 1.5;
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.3fr) minmax(320px, 0.95fr);
  gap: 18px;
  align-items: start;
}

.section-card {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.auth-card {
  min-height: 100%;
}

.auth-badge {
  gap: 8px;
  padding: 8px 12px;
  border-radius: 999px;
  border: 1px solid rgba(79, 70, 229, 0.12);
  background: rgba(79, 70, 229, 0.1);
  color: var(--brand-600);
  font-size: 0.82rem;
  font-weight: 700;
}

.auth-form {
  display: grid;
  gap: 16px;
}

.field-card {
  display: grid;
  gap: 8px;
}

.field-label {
  color: var(--muted);
  font-size: 0.82rem;
  font-weight: 700;
}

.auth-footer {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-top: 4px;
}

.auth-note {
  max-width: 360px;
  color: var(--muted);
  font-size: 0.9rem;
  line-height: 1.55;
}

.auth-submit {
  min-width: 190px;
}

.feedback {
  padding: 12px 14px;
  border-radius: 16px;
  border: 1px solid rgba(240, 68, 56, 0.18);
  background: var(--danger-100);
  color: var(--danger-500);
  font-size: 0.92rem;
  font-weight: 600;
}

.insights-card {
  position: sticky;
  top: 28px;
}

.summary-intro {
  color: var(--muted);
  font-size: 0.92rem;
  line-height: 1.6;
}

.overview-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.overview-card {
  display: grid;
  gap: 10px;
  padding: 16px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.76);
}

body[data-theme='dark'] .overview-card {
  background: rgba(15, 23, 42, 0.72);
}

.overview-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.overview-label {
  font-weight: 700;
  color: var(--text);
}

.overview-copy {
  color: var(--muted);
  font-size: 0.88rem;
  line-height: 1.55;
}

.notes-card {
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.68);
}

body[data-theme='dark'] .notes-card {
  background: rgba(15, 23, 42, 0.68);
}

.notes-card h3 {
  font-size: 1rem;
  font-weight: 800;
}

.notes-card ul {
  display: grid;
  gap: 10px;
  padding-left: 18px;
  margin: 14px 0 0;
  color: var(--muted);
}

.notes-card li {
  line-height: 1.55;
}

@media (max-width: 1100px) {
  .metric-grid,
  .content-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 920px) {
  .login-page {
    padding: 20px;
  }

  .metric-grid,
  .content-grid,
  .overview-grid {
    grid-template-columns: 1fr;
  }

  .hero {
    padding: 22px;
  }

  .hero-actions {
    min-width: 100%;
    max-width: none;
  }

  .auth-footer {
    align-items: stretch;
  }

  .auth-submit {
    width: 100%;
  }

  .insights-card {
    position: static;
  }
}

@media (max-width: 640px) {
  .login-page {
    padding: 16px;
  }

  .hero,
  .card {
    border-radius: 24px;
  }

  .section-heading {
    flex-direction: column;
  }

  .hero h1 {
    font-size: 1.9rem;
  }
}
</style>
