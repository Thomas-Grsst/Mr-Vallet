export const useAuthToken = () => {
  const cookie = useCookie<string | null>('vallet_token', { sameSite: 'strict', maxAge: 60 * 60 * 12 })
  const token = useState<string | null>('authToken', () => cookie.value ?? null)

  const setToken = (value: string | null) => {
    token.value = value
    cookie.value = value
  }

  return { token, setToken }
}
