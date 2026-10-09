type ApiErrorBody = {
  message?: string
  violations?: { message: string }[]
  errors?: Record<string, string[]>
}

export const useApiErrors = () => {
  const toMessages = (error: unknown): string[] => {
    const body = (error as { data?: ApiErrorBody })?.data

    if (body?.violations?.length) {
      return body.violations.map((violation) => violation.message)
    }

    if (body?.errors) {
      return Object.values(body.errors).flat()
    }

    return [body?.message ?? 'Erreur inattendue, réessayez.']
  }

  return { toMessages }
}
