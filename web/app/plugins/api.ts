import type { User } from '~/types/vallet'

export default defineNuxtPlugin(() => {
  const { token, setToken } = useAuthToken()
  const user = useState<User | null>('user', () => null)

  const api = $fetch.create({
    onRequest({ options }) {
      if (token.value) {
        options.headers.set('Authorization', `Bearer ${token.value}`)
      }
    },
    onResponseError({ response }) {
      if (response.status === 401) {
        setToken(null)
        user.value = null
      }
    },
  })

  return { provide: { api } }
})
