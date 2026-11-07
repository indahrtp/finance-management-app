export const rupiah = (n) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(n) || 0)

export const angka = (n) => new Intl.NumberFormat('id-ID').format(Number(n) || 0)

export const tanggal = (d) =>
  new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })

export const namaBulan = (ym, offset = 0, style = 'long') => {
  const [y, m] = ym.split('-').map(Number)
  return new Date(y, m - 1 + offset, 1).toLocaleDateString('id-ID', { month: style, year: 'numeric' })
}

export const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']