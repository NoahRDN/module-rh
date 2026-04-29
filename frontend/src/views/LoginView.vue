<template>
  <div class="login-page">
    <article class="card login-card">
      <div class="login-head">
        <p class="login-kicker">Connexion</p>
        <h1>Accéder au module RH</h1>
        <p class="login-subtitle">
          Renseignez votre identifiant et votre mot de passe pour ouvrir votre espace.
        </p>
        <div class="demo-credentials" aria-label="Identifiants de démonstration">
          <span class="demo-title">Accès démo</span>
          <p>
            <span>Identifiant :</span>
            <strong>admin@rh.test</strong>
          </p>
          <p>
            <span>Mot de passe :</span>
            <code>password</code>
          </p>
        </div>
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
          <span class="password-field">
            <input
              v-model="mdp"
              class="input password-input"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              placeholder="Votre mot de passe"
              required
            />
            <button
              class="password-toggle"
              type="button"
              :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
              :title="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
              @click="showPassword = !showPassword"
            >
              <AppIcon :name="showPassword ? 'eye-off' : 'eye'" :size="20" />
            </button>
          </span>
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
const showPassword = ref(false)

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

.demo-credentials {
  display: grid;
  gap: 8px;
  width: 100%;
  padding: 10px 12px;
  border: 1px solid rgba(99, 102, 241, 0.22);
  border-radius: 14px;
  color: var(--text);
  font-size: 0.86rem;
}

.demo-title {
  color: var(--brand-600);
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.demo-credentials p {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin: 0;
}

.demo-credentials p span {
  color: var(--muted);
  font-weight: 700;
}

.demo-credentials strong,
.demo-credentials code {
  min-width: 0;
  overflow-wrap: anywhere;
}

.demo-credentials strong {
  font-weight: 700;
}

.demo-credentials code {
  padding: 4px 8px;
  border-radius: 8px;
  background: rgba(15, 23, 42, 0.08);
  font-family: inherit;
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

.password-field {
  position: relative;
  display: block;
}

.password-input {
  width: 100%;
  padding-right: 54px;
}

.password-toggle {
  position: absolute;
  top: 50%;
  right: 12px;
  display: inline-grid;
  place-items: center;
  width: 34px;
  height: 34px;
  padding: 0;
  border: 0;
  border-radius: 10px;
  background: transparent;
  color: var(--muted);
  cursor: pointer;
  transform: translateY(-50%);
  transition: background-color 0.2s ease, color 0.2s ease;
}

.password-toggle:hover,
.password-toggle:focus-visible {
  background: rgba(79, 70, 229, 0.12);
  color: var(--brand-600);
  outline: none;
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

  .demo-credentials {
    align-items: stretch;
  }

  .demo-credentials p {
    align-items: flex-start;
    flex-direction: column;
    gap: 4px;
  }
}
</style>
