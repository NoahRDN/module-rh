<template>
  <div class="login">
    <div class="panel">
      <p class="muted">Module RH</p>
      <h1>Connexion</h1>
      <p class="muted" style="margin-bottom: 12px;">Accès sécurisé aux APIs RH</p>

      <form class="grid" style="gap: 12px;" @submit.prevent="onLogin">
        <div>
          <label class="muted">Identifiant</label>
          <input class="input" v-model="identifiant" required />
        </div>
        <div>
          <label class="muted">Mot de passe</label>
          <input class="input" v-model="mdp" type="password" required />
        </div>
        <button class="btn" type="submit">Se connecter</button>
        <p class="muted" v-if="error">{{ error }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '../services/api'
import { useRouter } from 'vue-router'

const router = useRouter()
const identifiant = ref('')
const mdp = ref('')
const error = ref('')

const onLogin = async () => {
  try {
    const { data } = await api.post('/login', { identifiant: identifiant.value, mdp: mdp.value })
    localStorage.setItem('token', data.access_token)
    localStorage.setItem('role', data.user.role ?? '')
    router.push('/dashboard')
  } catch (e) {
    error.value = 'Identifiants invalides'
  }
}
</script>

<style scoped>
.login {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
  background: radial-gradient(120% 120% at 10% 10%, rgba(34, 197, 94, 0.08), transparent),
    radial-gradient(80% 80% at 90% 20%, rgba(59, 130, 246, 0.08), transparent),
    var(--bg);
}

.panel {
  width: min(420px, 100%);
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.7));
  border: 1px solid rgba(226, 232, 240, 0.9);
  border-radius: 18px;
  padding: 24px;
  box-shadow: 0 24px 80px rgba(15, 23, 42, 0.18);
  color: #0f172a;
}

h1 {
  margin: 6px 0;
}

/* Mode sombre : revert sur les vars */
:deep(body[data-theme='dark']) .panel {
  background: linear-gradient(145deg, #0c1325, #0f172a);
  border: 1px solid var(--border);
  color: var(--text);
  box-shadow: var(--card-shadow);
}
</style>
