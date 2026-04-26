const STORAGE_KEY = 'app_currency'
const CATALOG_STORAGE_KEY = 'app_currency_catalog'
const DEFAULT_CURRENCY = 'MGA'
const DEFAULT_CATALOG = [
  { code: 'MGA', libelle: 'Ariary malgache', symbole: 'MGA', active: true },
  { code: 'EUR', libelle: 'Euro', symbole: '€', active: true },
  { code: 'USD', libelle: 'Dollar américain', symbole: '$', active: true },
]

const normalizeCurrency = (value) => {
  const code = String(value || '').trim().toUpperCase()
  return /^[A-Z0-9_-]{2,10}$/.test(code) ? code : DEFAULT_CURRENCY
}

const normalizeCurrencyItem = (item) => {
  const code = normalizeCurrency(item?.code)
  return {
    code,
    libelle: String(item?.libelle || code).trim() || code,
    symbole: String(item?.symbole || '').trim() || code,
    active: item?.active !== false,
  }
}

const fallbackCatalog = () => DEFAULT_CATALOG.map((item) => ({ ...item }))

export const setCurrencyCatalog = (items) => {
  const normalized = Array.isArray(items)
    ? items.map(normalizeCurrencyItem).filter((item, index, array) => array.findIndex((x) => x.code === item.code) === index)
    : []

  const catalog = normalized.length ? normalized : fallbackCatalog()
  if (typeof window !== 'undefined') {
    localStorage.setItem(CATALOG_STORAGE_KEY, JSON.stringify(catalog))
  }
  return catalog
}

export const getCurrencyOptions = () => {
  if (typeof window === 'undefined') return fallbackCatalog()
  try {
    const raw = localStorage.getItem(CATALOG_STORAGE_KEY)
    if (!raw) return fallbackCatalog()
    const parsed = JSON.parse(raw)
    return Array.isArray(parsed) && parsed.length ? parsed.map(normalizeCurrencyItem) : fallbackCatalog()
  } catch (error) {
    return fallbackCatalog()
  }
}

export const getCurrencyDisplayUnit = (code = getStoredCurrency()) => {
  const target = normalizeCurrency(code)
  const found = getCurrencyOptions().find((item) => item.code === target)
  return found?.symbole || found?.code || target
}

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
