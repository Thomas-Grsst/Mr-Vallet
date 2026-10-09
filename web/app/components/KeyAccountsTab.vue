<script setup lang="ts">
import type { KeyAccount } from '~/types/vallet'

const { $api } = useNuxtApp()
const { user } = useAuth()
const { toMessages } = useApiErrors()
const { notice, notify } = useActionNotice()

const name = ref('')
const errors = ref<string[]>([])
const isSaving = ref(false)

const { data: accounts, status, refresh } = useApiFetch<KeyAccount[]>('/api/key-accounts', { default: () => [] })
const { isFirstLoading, hasError, hasLoaded } = useFirstLoad([status])

const run = async (action: () => Promise<unknown>) => {
  errors.value = []
  isSaving.value = true
  try {
    await action()
    await refresh()
  }
  catch (error) {
    errors.value = toMessages(error)
  }
  finally {
    isSaving.value = false
  }
}

const add = () => run(async () => {
  const added = await $api<KeyAccount>('/api/key-accounts', { method: 'POST', body: { name: name.value } })
  name.value = ''
  notify(`${added.name} ajouté aux grands comptes`)
})

const remove = (account: KeyAccount) => {
  if (!confirm(`Retirer ${account.name} des grands comptes ? Ses prochaines réservations n'exigeront plus de bon de commande.`)) {
    return
  }

  return run(async () => {
    await $api(`/api/key-accounts/${account.id}`, { method: 'DELETE' })
    notify(`${account.name} retiré des grands comptes`)
  })
}
</script>

<template>
  <section class="card">
    <ActionNotice :message="notice" />
    <p class="muted">Une réservation pour un grand compte n'est valable qu'avec un numéro de bon de commande.</p>

    <form v-if="user?.can_manage_key_accounts" class="filter-bar" @submit.prevent="add">
      <label>
        Ajouter un grand compte
        <input v-model="name" type="text" placeholder="Nom de l'entreprise">
      </label>
      <button class="button" type="submit" :disabled="isSaving">{{ isSaving ? 'Enregistrement…' : 'Ajouter' }}</button>
    </form>

    <div v-if="errors.length" class="alert alert--ko">
      <ul><li v-for="message in errors" :key="message">{{ message }}</li></ul>
    </div>

    <LoadError v-if="hasError" @retry="refresh()" />
    <LoadingMessage v-if="isFirstLoading" label="Chargement des grands comptes…" />
    <table v-if="hasLoaded">
      <thead>
        <tr>
          <th>Entreprise</th>
          <th v-if="user?.can_manage_key_accounts" />
        </tr>
      </thead>
      <tbody>
        <tr v-for="account in accounts" :key="account.id">
          <td><strong>{{ account.name }}</strong></td>
          <td v-if="user?.can_manage_key_accounts" class="cell-action">
            <button type="button" class="button button--ghost button--small" :disabled="isSaving" @click="remove(account)">Retirer</button>
          </td>
        </tr>
        <tr v-if="!accounts.length">
          <td colspan="2" class="muted">Aucun grand compte.</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>
