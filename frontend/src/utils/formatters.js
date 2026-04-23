const normalizeMoneyValue = (value) => {
  if (value === null || value === undefined || value === '') return null

  const amount = Number(value)
  return Number.isFinite(amount) ? amount : null
}

export const formatMoneyAmount = (value, options = {}) => {
  const {
    unit = 'MGA',
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
