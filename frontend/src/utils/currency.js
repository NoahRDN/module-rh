const STORAGE_KEY = 'app_currency'
const DEFAULT_CURRENCY = 'MGA'
const ALLOWED_CURRENCIES = ['MGA', 'EUR', 'USD']

const normalizeCurrency = (value) => {
  const code = String(value || '').trim().toUpperCase()
  return ALLOWED_CURRENCIES.includes(code) ? code : DEFAULT_CURRENCY
}

export const getCurrencyOptions = () => [...ALLOWED_CURRENCIES]

export const getStoredCurrency = () => {
  if (typeof window === 'undefined') return DEFAULT_CURRENCY
  return normalizeCurrency(localStorage.getItem(STORAGE_KEY))
}

export const setStoredCurrency = (value) => {
  const normalized = normalizeCurrency(value)
  if (typeof window !== 'undefined') {
    localStorage.setItem(STORAGE_KEY, normalized)
  }
  return normalized
}

export const resolveCurrency = (value) => normalizeCurrency(value)
