import { getCurrencyDisplayUnit, getStoredCurrency } from './currency'

const normalizeMoneyValue = (value) => {
  if (value === null || value === undefined || value === '') return null

  const amount = Number(value)
  return Number.isFinite(amount) ? amount : null
}

export const formatMoneyAmount = (value, options = {}) => {
  const {
    unit = getCurrencyDisplayUnit(getStoredCurrency()),
    empty = '—',
    minimumFractionDigits,
    maximumFractionDigits = 2,
  } = options

  const amount = normalizeMoneyValue(value)
  if (amount === null) return empty

  const hasDecimals = !Number.isInteger(amount)
  const minDigits = minimumFractionDigits ?? (hasDecimals ? 2 : 0)
  const maxDigits = maximumFractionDigits

  const formatted = new Intl.NumberFormat('en-US', {
    minimumFractionDigits: minDigits,
    maximumFractionDigits: maxDigits,
  }).format(amount)

  return unit ? `${formatted} ${unit}` : formatted
}

const parseDateValue = (value) => {
  if (value === null || value === undefined || value === '') return null

  if (value instanceof Date) {
    return Number.isNaN(value.getTime()) ? null : value
  }

  const raw = String(value).trim()
  if (!raw) return null

  const dateOnlyMatch = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/)
  const parsed = dateOnlyMatch
    ? new Date(Number(dateOnlyMatch[1]), Number(dateOnlyMatch[2]) - 1, Number(dateOnlyMatch[3]))
    : new Date(raw)

  return Number.isNaN(parsed.getTime()) ? null : parsed
}

export const formatDateValue = (value, options = {}) => {
  const { empty = '' } = options
  const parsed = parseDateValue(value)
  if (!parsed) return empty

  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
  }).format(parsed)
}

export const formatDateTimeValue = (value, options = {}) => {
  const { empty = '' } = options
  const parsed = parseDateValue(value)
  if (!parsed) return empty

  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(parsed)
}
