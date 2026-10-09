<script setup lang="ts">
type Tab = 'search' | 'planning' | 'history' | 'anomalies' | 'workshop'

const { user, fetchCurrentUser, logout } = useAuth()
const { formatDate } = useFormatDate()
const today = useRuntimeConfig().public.today

await fetchCurrentUser()

const tabs = computed(() => [
  { key: 'search' as Tab, label: user.value?.can_book ? 'Rechercher et réserver' : 'Rechercher' },
  { key: 'planning' as Tab, label: 'Planning' },
  { key: 'history' as Tab, label: 'Historique' },
  { key: 'anomalies' as Tab, label: 'Anomalies' },
  ...(user.value?.can_maintain ? [{ key: 'workshop' as Tab, label: 'Atelier' }] : []),
])

const activeTab = ref<Tab>('search')

watch(user, () => {
  activeTab.value = 'search'
})
</script>

<template>
  <div class="layout">
    <header class="header">
      <div>
        <h1 class="header__title">Vallet Location</h1>
        <p class="header__subtitle">Réservations des 7 agences · aujourd'hui : {{ formatDate(today) }}</p>
      </div>
      <div v-if="user" class="header__user">
        <span>{{ user.name }} · {{ user.role_label }}</span>
        <button type="button" class="button button--ghost" @click="logout">Se déconnecter</button>
      </div>
    </header>

    <LoginForm v-if="!user" />

    <template v-else>
    <nav class="tabs">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        type="button"
        class="tabs__item"
        :class="{ 'tabs__item--active': activeTab === tab.key }"
        @click="activeTab = tab.key"
      >
        {{ tab.label }}
      </button>
    </nav>

    <main class="content">
      <SearchTab v-if="activeTab === 'search'" />
      <PlanningTab v-else-if="activeTab === 'planning'" />
      <HistoryTab v-else-if="activeTab === 'history'" />
      <AnomaliesTab v-else-if="activeTab === 'anomalies'" />
      <WorkshopTab v-else />
    </main>
    </template>
  </div>
</template>

<style>
:root {
  --color-primary: #c2410c;
  --color-text: #1f2937;
  --color-muted: #6b7280;
  --color-border: #e5e7eb;
  --color-ok: #15803d;
  --color-ok-bg: #dcfce7;
  --color-ko: #b91c1c;
  --color-ko-bg: #fee2e2;
  --color-bg: #f9fafb;
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
  color: var(--color-text);
  background: var(--color-bg);
}

.layout {
  max-width: 1100px;
  margin: 0 auto;
  padding: 16px;
}

.header {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.header__title {
  margin: 0;
  color: var(--color-primary);
}

.header__subtitle {
  margin: 4px 0 0;
  color: var(--color-muted);
}

.header__user {
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 600;
}

.tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin: 16px 0;
  border-bottom: 2px solid var(--color-border);
}

.tabs__item {
  padding: 10px 16px;
  border: none;
  background: none;
  font-size: 1rem;
  cursor: pointer;
  color: var(--color-muted);
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
}

.tabs__item--active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
  font-weight: 600;
}

.card {
  background: #fff;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
}

.form-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: flex-end;
}

.form-row label {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 0.9rem;
  font-weight: 600;
}

input, select, button {
  font: inherit;
  padding: 8px 10px;
  border: 1px solid var(--color-border);
  border-radius: 6px;
}

.button {
  background: var(--color-primary);
  color: #fff;
  border: none;
  cursor: pointer;
  font-weight: 600;
}

.button:disabled {
  background: #d1d5db;
  color: #6b7280;
  border-color: #d1d5db;
  cursor: not-allowed;
}

.button--ghost {
  background: #fff;
  color: var(--color-primary);
  border: 1px solid var(--color-primary);
}

.table-wrapper {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  text-align: left;
  padding: 8px;
  border-bottom: 1px solid var(--color-border);
  vertical-align: top;
}

.badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 600;
}

.badge--ok {
  background: var(--color-ok-bg);
  color: var(--color-ok);
}

.badge--ko {
  background: var(--color-ko-bg);
  color: var(--color-ko);
}

.alert {
  padding: 12px;
  border-radius: 6px;
  margin-top: 12px;
}

.alert--ok {
  background: var(--color-ok-bg);
  color: var(--color-ok);
}

.alert--ko {
  background: var(--color-ko-bg);
  color: var(--color-ko);
}

.alert ul {
  margin: 0;
  padding-left: 20px;
}

.muted {
  color: var(--color-muted);
}
</style>
