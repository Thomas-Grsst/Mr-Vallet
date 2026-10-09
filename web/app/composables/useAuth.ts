import type { User } from '~/types/vallet'

type LoginResponse = {
  token: string
  user: User
}

export const useAuth = () => {
  const { $api } = useNuxtApp()
  const { token, setToken } = useAuthToken()
  const user = useState<User | null>('user', () => null)

  const fetchCurrentUser = async () => {
    if (!token.value) {
      user.value = null
      return
    }

    try {
      user.value = await $api<User>('/api/me')
    }
    catch {
      user.value = null
    }
  }

  const login = async (email: string, password: string) => {
    const response = await $api<LoginResponse>('/api/login', { method: 'POST', body: { email, password } })
    setToken(response.token)
    user.value = response.user
  }

  const logout = async () => {
    try {
      await $api('/api/logout', { method: 'POST' })
    }
    finally {
      setToken(null)
      user.value = null
    }
  }

  return { user, fetchCurrentUser, login, logout }
}
