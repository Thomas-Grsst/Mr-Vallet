import type { Ref } from 'vue'

type FetchStatus = 'idle' | 'pending' | 'success' | 'error'

export const useFirstLoad = (statuses: Ref<FetchStatus>[]) => {
  const hasLoaded = ref(false)

  const hasError = computed(() => statuses.some((status) => status.value === 'error'))

  watch(
    () => statuses.every((status) => status.value === 'success'),
    (allLoaded) => {
      if (allLoaded) {
        hasLoaded.value = true
      }
    },
    { immediate: true },
  )

  return {
    isFirstLoading: computed(() => !hasLoaded.value && !hasError.value),
    hasError,
    hasLoaded,
  }
}
