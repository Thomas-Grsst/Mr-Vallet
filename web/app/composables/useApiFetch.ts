import type { UseFetchOptions } from 'nuxt/app'

export const useApiFetch = <T>(url: string, options: UseFetchOptions<T> = {}) =>
  useFetch(url, { ...options, $fetch: useNuxtApp().$api as typeof $fetch })
