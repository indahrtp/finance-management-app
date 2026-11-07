export const todayStr = () => new Date().toLocaleDateString('sv-SE') // YYYY-MM-DD (waktu lokal)
export const MAX_MONTH = todayStr().slice(0, 7) // YYYY-MM bulan berjalan
export const MAX_YEAR = Number(MAX_MONTH.slice(0, 4))