<script setup lang="ts">
const { login } = useAuth()
const { toMessages } = useApiErrors()

const email = ref('')
const password = ref('')
const errors = ref<string[]>([])
const isSubmitting = ref(false)

const submit = async () => {
  errors.value = []
  isSubmitting.value = true
  try {
    await login(email.value, password.value)
  }
  catch (error) {
    errors.value = toMessages(error)
  }
  finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <form class="card login" @submit.prevent="submit">
    <h2>Connexion</h2>
    <label>
      E-mail
      <input v-model="email" type="email" autocomplete="username" required>
    </label>
    <label>
      Mot de passe
      <input v-model="password" type="password" autocomplete="current-password" required>
    </label>
    <button class="button" type="submit" :disabled="isSubmitting">Se connecter</button>
    <div v-if="errors.length" class="alert alert--ko">
      <ul><li v-for="message in errors" :key="message">{{ message }}</li></ul>
    </div>
  </form>
</template>

<style scoped>
.login {
  max-width: 360px;
  margin: 40px auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.login label {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-weight: 600;
}
</style>
