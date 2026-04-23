<template>
  <div class="login-page">
    <article class="card login-card">
      <div class="login-head">
        <p class="login-kicker">Connexion</p>
        <h1>Accéder au module RH</h1>
        <p class="login-subtitle">
          Renseignez votre identifiant et votre mot de passe pour ouvrir votre espace.
        </p>
      </div>

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

        <button class="btn auth-submit" type="submit" :disabled="loading">
          <AppIcon :name="loading ? 'refresh' : 'logout'" :size="18" />
          <span>{{ loading ? 'Connexion en cours...' : 'Se connecter' }}</span>
        </button>

        <p v-if="error" class="feedback error">{{ error }}</p>
      </form>
    </article>
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
  min-height: 100vh;
  display: grid;
  place-items: center;
  width: 100%;
  padding: clamp(24px, 5vw, 56px) clamp(18px, 6vw, 72px);
}

.login-card {
  width: 100%;
  max-width: 560px;
  margin-inline: auto;
  flex: 0 0 auto;
  display: grid;
  gap: 20px;
  padding: clamp(24px, 3vw, 32px);
  border-radius: 28px;
  box-shadow: var(--shadow-lg);
}

.login-head {
  display: grid;
  gap: 10px;
}

.login-kicker,
.field-label,
.feedback {
  margin: 0;
}

.login-kicker {
  color: var(--brand-600);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.login-head h1 {
  margin: 0;
  font-size: clamp(1.8rem, 3vw, 2.4rem);
  font-weight: 800;
  letter-spacing: -0.04em;
}

.login-subtitle {
  margin: 0;
  color: var(--muted);
  font-size: 0.96rem;
  line-height: 1.6;
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

.auth-submit {
  width: 100%;
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

@media (max-width: 640px) {
  .login-page {
    padding: 16px;
  }

  .login-card {
    padding: 22px;
    border-radius: 24px;
  }
}
</style>
