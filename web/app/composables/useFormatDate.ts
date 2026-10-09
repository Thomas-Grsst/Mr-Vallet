export const useFormatDate = () => {
  const formatDate = (isoDate: string | null) => {
    if (!isoDate) {
      return '—'
    }

    const [year, month, day] = isoDate.split('-')

    return `${day}/${month}/${year}`
  }

  return { formatDate }
}
