<template>
  <div style="max-width:420px;margin:64px auto;">
    <h2>Connexion RH</h2>
    <form @submit.prevent="onLogin">
      <div>
        <label>Identifiant</label>
        <input v-model="identifiant" required />
      </div>
      <div>
        <label>Mot de passe</label>
        <input v-model="mdp" type="password" required />
      </div>
      <button type="submit">Se connecter</button>
      <p v-if="error" style="color:red">{{ error }}</p>
    </form>
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
