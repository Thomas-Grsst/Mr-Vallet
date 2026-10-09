const DAY_MS = 24 * 60 * 60 * 1000

const toUtc = (isoDate: string) => {
  const [year, month, day] = isoDate.split('-').map(Number)
  return Date.UTC(year!, month! - 1, day!)
}

export const useIsoDate = () => {
  const addDays = (isoDate: string, days: number) => new Date(toUtc(isoDate) + days * DAY_MS).toISOString().slice(0, 10)

  const daysBetween = (from: string, to: string) => Math.round((toUtc(to) - toUtc(from)) / DAY_MS)

  const weekday = (isoDate: string) => new Date(toUtc(isoDate)).getUTCDay()

  return { addDays, daysBetween, weekday }
}
