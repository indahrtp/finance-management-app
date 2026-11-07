<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { Plus, Pencil, Trash2, Search, Paperclip, Check, Ban } from 'lucide-vue-next'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { validationErrors, errorMessage } from '@/utils/errors'
import { rupiah, tanggal } from '@/utils/format'
import { saveBlob } from '@/utils/download'
import { todayStr } from '@/utils/period'
import PageCard from '@/components/ui/PageCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import PaginationBar from '@/components/ui/PaginationBar.vue'

const toast = useToast()
const TODAY = todayStr()

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 })
const coaList = ref([])
const wallets = ref([])
const loading = ref(false)
const page = ref(1)

const statusUI = {
  draft: { text: 'Draft', cls: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' },
  posted: { text: 'Posted', cls: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' },
  void: { text: 'Void', cls: 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-400' },
}
const statusFilterOptions = [
  { value: 'posted', label: 'Posted' },
  { value: 'draft', label: 'Draft' },
  { value: 'void', label: 'Void' },
]

/* ---------- Filter ---------- */
const filters = reactive({ search: '', coa_id: '', wallet_id: '', status: '', start_date: '', end_date: '' })

const coaOptions = computed(() => coaList.value.map((a) => ({ value: a.id, label: `${a.code} - ${a.name}` })))
const walletOptions = computed(() => wallets.value.map((w) => ({ value: w.id, label: w.name })))

const ymd = (d) => d.toLocaleDateString('sv-SE')
const capToday = (d) => {
  const s = ymd(d)
  return s > TODAY ? TODAY : s
}

const presets = computed(() => {
  const n = new Date()
  const y = n.getFullYear()
  const m = n.getMonth()
  return [
    { key: 'all', label: 'Semua', start: '', end: '' },
    { key: 'month', label: 'Bulan ini', start: ymd(new Date(y, m, 1)), end: capToday(new Date(y, m + 1, 0)) },
    { key: 'last', label: 'Bulan lalu', start: ymd(new Date(y, m - 1, 1)), end: ymd(new Date(y, m, 0)) },
    { key: 'year', label: 'Tahun ini', start: ymd(new Date(y, 0, 1)), end: capToday(new Date(y, 11, 31)) },
  ]
})

const isActivePreset = (p) => filters.start_date === p.start && filters.end_date === p.end

function applyPreset(p) {
  filters.start_date = p.start
  filters.end_date = p.end
}

const rangeInvalid = computed(
  () => !!filters.start_date && !!filters.end_date && filters.start_date > filters.end_date,
)

const futureInvalid = computed(() => filters.start_date > TODAY || filters.end_date > TODAY)

function clearFilters() {
  Object.assign(filters, { search: '', coa_id: '', wallet_id: '', status: '', start_date: '', end_date: '' })
}

/* ---------- Data ---------- */
async function loadMaster() {
  try {
    const [coa, wal] = await Promise.all([api.get('/coa'), api.get('/wallets')])
    coaList.value = coa.data
    wallets.value = wal.data
  } catch (e) {
    toast.error(errorMessage(e))
  }
}

async function load() {
  if (rangeInvalid.value || futureInvalid.value) return
  loading.value = true
  try {
    const { data } = await api.get('/transactions', {
      params: {
        page: page.value,
        search: filters.search || undefined,
        coa_id: filters.coa_id || undefined,
        wallet_id: filters.wallet_id || undefined,
        status: filters.status || undefined,
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
      },
    })
    items.value = data.data
    meta.value = data
  } catch (e) {
    toast.error(errorMessage(e))
  } finally {
    loading.value = false
  }
}

const goTo = (p) => { page.value = p; load() }
const resetAndLoad = () => { page.value = 1; load() }

/* ---------- Form ---------- */
const showForm = ref(false)
const editingId = ref(null)
const form = reactive({
  date: '', coa_id: '', wallet_id: '', description: '', reference_no: '', notes: '', amount: '',
})
const errors = ref({})
const saving = ref(false)

const attachFile = ref(null)
const existingAttachment = ref('')
const removeAttachment = ref(false)

const selectedCoa = computed(() => coaList.value.find((a) => a.id === form.coa_id))
const side = computed(() => {
  const type = selectedCoa.value?.category?.type
  return type === 'income' ? 'credit' : type === 'expense' ? 'debit' : null
})
const amountLabel = computed(() =>
  side.value === 'credit' ? 'Jumlah pemasukan (credit)' : side.value === 'debit' ? 'Jumlah pengeluaran (debit)' : 'Jumlah',
)
const walletLabel = computed(() =>
  side.value === 'credit' ? 'Diterima di' : side.value === 'debit' ? 'Dibayar dari' : 'Rekening / Dompet',
)

const today = () => new Date().toLocaleDateString('sv-SE')

function openCreate() {
  editingId.value = null
  Object.assign(form, {
    date: today(), coa_id: '', wallet_id: '', description: '', amount: '',
  })
  attachFile.value = null
  existingAttachment.value = ''
  removeAttachment.value = false
  errors.value = {}
  showForm.value = true
}

function openEdit(t) {
  editingId.value = t.id
  Object.assign(form, {
    date: t.date,
    coa_id: t.coa_id,
    wallet_id: t.wallet_id,
    description: t.description || '',
    amount: Number(t.debit) > 0 ? Number(t.debit) : Number(t.credit),
  })
  attachFile.value = null
  existingAttachment.value = t.has_attachment ? t.attachment_name : ''
  removeAttachment.value = false
  errors.value = {}
  showForm.value = true
}

const fileInput = ref(null)
const dragging = ref(false)

const fileSize = (bytes) => (bytes < 1024 * 1024 ? `${Math.round(bytes / 1024)} KB` : `${(bytes / 1024 / 1024).toFixed(1)} MB`)

function setFile(f) {
  attachFile.value = null
  delete errors.value.file
  if (!f) return

  if (f.size > 2 * 1024 * 1024) {
    errors.value = { ...errors.value, file: 'Ukuran file maksimal 2 MB.' }
    return
  }
  if (!['image/jpeg', 'image/png', 'application/pdf'].includes(f.type)) {
    errors.value = { ...errors.value, file: 'Format yang diizinkan: JPG, PNG, atau PDF.' }
    return
  }
  attachFile.value = f
}

function onFile(e) {
  setFile(e.target.files[0])
  if (!attachFile.value) e.target.value = ''
}

function onDrop(e) {
  dragging.value = false
  setFile(e.dataTransfer.files[0])
}

function clearFile() {
  attachFile.value = null
  if (fileInput.value) fileInput.value.value = ''
}

async function syncAttachment(id) {
  if (attachFile.value) {
    const fd = new FormData()
    fd.append('file', attachFile.value)
    await api.post(`/transactions/${id}/attachment`, fd)
  } else if (removeAttachment.value) {
    await api.delete(`/transactions/${id}/attachment`)
  }
}

async function save() {
  errors.value = { ...(errors.value.file ? { file: errors.value.file } : {}) }

  if (!side.value) {
    errors.value = { ...errors.value, coa_id: 'Pilih COA terlebih dahulu.' }
    return
  }

  const amount = Number(form.amount) || 0
  const payload = {
    date: form.date,
    coa_id: form.coa_id,
    wallet_id: form.wallet_id,
    description: form.description,
    debit: side.value === 'debit' ? amount : 0,
    credit: side.value === 'credit' ? amount : 0,
  }

  saving.value = true
  try {
    const created = !editingId.value
    const res = created
      ? await api.post('/transactions', payload)
      : await api.put(`/transactions/${editingId.value}`, payload)

    try {
      await syncAttachment(res.data.id)
      toast.success(created ? 'Transaksi disimpan sebagai draft. Klik Posting agar masuk laporan.' : 'Transaksi diperbarui.')
    } catch (e) {
      toast.error(`Transaksi tersimpan, tetapi lampiran gagal diproses: ${validationErrors(e).file || errorMessage(e)}`)
    }

    showForm.value = false
    await load()
    loadMaster() 
  } catch (e) {
    if (e.response?.status === 422) {
      const err = validationErrors(e)
      err.amount = err.debit || err.credit
      errors.value = err
    } else {
      toast.error(errorMessage(e))
    }
  } finally {
    saving.value = false
  }
}

async function downloadAttachment(t) {
  try {
    const res = await api.get(`/transactions/${t.id}/attachment`, { responseType: 'blob' })
    saveBlob(res.data, t.attachment_name || 'lampiran')
  } catch {
    toast.error('Gagal mengunduh lampiran.')
  }
}

async function markPosted(t) {
  try {
    await api.post(`/transactions/${t.id}/post`)
    toast.success('Transaksi diposting.')
    await load()
  } catch (e) {
    toast.error(errorMessage(e))
  }
}

const voiding = ref(null)
const voidBusy = ref(false)
async function confirmVoid() {
  voidBusy.value = true
  try {
    await api.post(`/transactions/${voiding.value.id}/void`)
    toast.success('Transaksi dibatalkan.')
    voiding.value = null
    await load()
  } catch (e) {
    toast.error(errorMessage(e))
    voiding.value = null
  } finally {
    voidBusy.value = false
  }
}

const deleting = ref(null)
const deleteBusy = ref(false)
async function remove() {
  deleteBusy.value = true
  try {
    await api.delete(`/transactions/${deleting.value.id}`)
    toast.success('Transaksi dihapus.')
    deleting.value = null
    if (items.value.length === 1 && page.value > 1) page.value--
    await load()
  } catch (e) {
    toast.error(errorMessage(e))
    deleting.value = null
  } finally {
    deleteBusy.value = false
  }
}

let timer
watch(() => filters.search, () => {
  clearTimeout(timer)
  timer = setTimeout(resetAndLoad, 300)
})
watch(() => [filters.coa_id, filters.wallet_id, filters.status, filters.start_date, filters.end_date], resetAndLoad)

onMounted(() => {
  loadMaster()
  load()
})

const inputCls =
  'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100'
const actionCls = 'rounded p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800'
</script>

<template>
  <PageCard title="Transaksi">
    <template #actions>
      <BaseButton @click="openCreate"><Plus :size="16" /> Tambah</BaseButton>
    </template>

    <!-- Filter -->
    <div class="mb-4 space-y-3">
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div class="relative">
          <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input
            v-model="filters.search" placeholder="Cari deskripsi..."
            class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
          />
        </div>
        <BaseSelect v-model="filters.coa_id" :options="coaOptions" placeholder="Semua COA" clearable />
        <BaseButton variant="secondary" @click="clearFilters">Reset filter</BaseButton>
      </div>

      <div class="flex flex-wrap items-end gap-3">
        <div>
          <label for="f-start" class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Dari tanggal</label>
          <input id="f-start" v-model="filters.start_date" type="date" :max="filters.end_date || TODAY" :class="inputCls" />
        </div>
        <div>
          <label for="f-end" class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Sampai tanggal</label>
          <input id="f-end" v-model="filters.end_date" type="date" :min="filters.start_date || undefined" :max="TODAY" :class="inputCls" />
        </div>
        <div class="flex flex-wrap gap-2 pb-0.5">
          <button
            v-for="p in presets" :key="p.key" type="button" @click="applyPreset(p)"
            class="rounded-full border px-3 py-1.5 text-xs font-medium transition"
            :class="isActivePreset(p)
              ? 'border-brand-500 bg-brand-50 text-brand-500 dark:bg-brand-500/15'
              : 'border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800'"
          >
            {{ p.label }}
          </button>
        </div>
      </div>

      <p v-if="rangeInvalid" class="text-xs text-red-600 dark:text-red-400">
        Tanggal awal tidak boleh setelah tanggal akhir.
      </p>
      <p v-if="futureInvalid" class="text-xs text-red-600 dark:text-red-400">Tanggal tidak boleh melebihi hari ini.</p>
    </div>

    <!-- Tabel -->
    <div class="overflow-x-auto">
      <table class="w-full min-w-[980px] text-left text-sm">
        <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400">
          <tr>
            <th class="py-3 pr-4 font-medium">Tanggal</th>
            <th class="py-3 pr-4 font-medium">COA</th>
            <th class="py-3 pr-4 font-medium">Deskripsi</th>
            <th class="py-3 pr-4 font-medium">Rekening</th>
            <th class="py-3 pr-4 font-medium">Status</th>
            <th class="py-3 pr-4 text-right font-medium">Debit</th>
            <th class="py-3 pr-4 text-right font-medium">Credit</th>
            <th class="py-3 text-right font-medium">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="8" class="py-8 text-center text-gray-400">Memuat...</td></tr>
          <tr v-else-if="!items.length"><td colspan="8" class="py-8 text-center text-gray-400">Belum ada transaksi.</td></tr>
          <tr
            v-for="t in items" :key="t.id"
            class="border-b border-gray-100 last:border-0 dark:border-gray-800"
            :class="{ 'opacity-60': t.status === 'void' }"
          >
            <td class="whitespace-nowrap py-3 pr-4">{{ tanggal(t.date) }}</td>
            <td class="py-3 pr-4">
              <span class="font-mono text-gray-500 dark:text-gray-400">{{ t.coa?.code }}</span> {{ t.coa?.name }}
            </td>
            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ t.description || '-' }}</td>
            <td class="whitespace-nowrap py-3 pr-4 text-gray-600 dark:text-gray-300">{{ t.wallet?.name }}</td>
            <td class="py-3 pr-4">
              <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusUI[t.status].cls">{{ statusUI[t.status].text }}</span>
            </td>
            <td class="whitespace-nowrap py-3 pr-4 text-right" :class="[Number(t.debit) > 0 ? 'font-medium text-orange-600 dark:text-orange-400' : 'text-gray-300 dark:text-gray-600', { 'line-through': t.status === 'void' }]">
              {{ Number(t.debit) > 0 ? rupiah(t.debit) : '-' }}
            </td>
            <td class="whitespace-nowrap py-3 pr-4 text-right" :class="[Number(t.credit) > 0 ? 'font-medium text-emerald-600 dark:text-emerald-400' : 'text-gray-300 dark:text-gray-600', { 'line-through': t.status === 'void' }]">
              {{ Number(t.credit) > 0 ? rupiah(t.credit) : '-' }}
            </td>
            <td class="whitespace-nowrap py-3 text-right">
              <button v-if="t.has_attachment" :class="actionCls" title="Unduh lampiran" @click="downloadAttachment(t)">
                <Paperclip :size="16" />
              </button>
              <button v-if="t.status === 'draft'" class="rounded p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/15" title="Posting" @click="markPosted(t)">
                <Check :size="16" />
              </button>
              <button v-if="t.status !== 'void'" :class="actionCls" title="Ubah" @click="openEdit(t)">
                <Pencil :size="16" />
              </button>
              <button v-if="t.status === 'posted'" class="rounded p-2 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-500/15" title="Batalkan (void)" @click="voiding = t">
                <Ban :size="16" />
              </button>
              <button v-if="t.status === 'draft'" class="rounded p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/15" title="Hapus" @click="deleting = t">
                <Trash2 :size="16" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaginationBar
      :page="meta.current_page" :last-page="meta.last_page"
      :total="meta.total" :from="meta.from" :to="meta.to"
      @change="goTo"
    />
  </PageCard>

  <!-- Form -->
  <BaseModal :show="showForm" :title="editingId ? 'Ubah transaksi' : 'Tambah transaksi'" @close="showForm = false">
    <form class="space-y-4" @submit.prevent="save">
      <div class="grid gap-4 sm:grid-cols-2">
        <BaseInput v-model="form.date" type="date" label="Tanggal" :max="TODAY" :error="errors.date" />
        <BaseSelect v-model="form.coa_id" label="COA" :options="coaOptions" :error="errors.coa_id" />
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <BaseInput v-model="form.amount" type="number" :label="amountLabel" :error="errors.amount" />
          <p v-if="side" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            COA {{ side === 'credit' ? 'income' : 'expense' }}, disimpan di kolom {{ side }}.
          </p>
        </div>
        <BaseSelect v-model="form.wallet_id" :label="walletLabel" :options="walletOptions" :error="errors.wallet_id" />
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <BaseInput v-model="form.description" label="Deskripsi (opsional)" :error="errors.description" />
      </div>

      <!-- Lampiran -->
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Lampiran (opsional)</label>

        <div v-if="existingAttachment" class="mb-2 flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
          <span class="truncate" :class="removeAttachment ? 'text-gray-400 line-through' : ''">{{ existingAttachment }}</span>
          <button type="button" class="ml-3 shrink-0 text-xs font-medium text-red-600 hover:underline dark:text-red-400" @click="removeAttachment = !removeAttachment">
            {{ removeAttachment ? 'Batalkan hapus' : 'Hapus' }}
          </button>
        </div>

        <div
          class="rounded-xl border-2 border-dashed px-4 py-5 text-center transition focus-within:ring-2 focus-within:ring-brand-500/30"
          :class="dragging
            ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10'
            : errors.file
              ? 'border-red-400'
              : 'border-gray-300 hover:border-brand-500 dark:border-gray-700'"
          @dragover.prevent="dragging = true"
          @dragleave.prevent="dragging = false"
          @drop.prevent="onDrop"
        >
          <input ref="fileInput" id="attachment" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.pdf" @change="onFile" />

          <div v-if="attachFile" class="flex items-center justify-between gap-3 text-left">
            <div class="flex min-w-0 items-center gap-3">
              <FileText :size="20" class="shrink-0 text-brand-500" />
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">{{ attachFile.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ fileSize(attachFile.size) }}</p>
              </div>
            </div>
            <button type="button" class="shrink-0 text-xs font-medium text-red-600 hover:underline dark:text-red-400" @click="clearFile">
              Hapus
            </button>
          </div>

          <label v-else for="attachment" class="block cursor-pointer">
            <Upload :size="22" class="mx-auto text-gray-400" />
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
              <span class="font-medium text-brand-500">Klik untuk memilih file</span> atau seret ke sini
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              JPG, PNG, atau PDF, maksimal 2 MB.{{ existingAttachment ? ' File baru akan menggantikan lampiran lama.' : '' }}
            </p>
          </label>
        </div>

        <p v-if="errors.file" class="mt-1 text-xs text-red-600">{{ errors.file }}</p>
      </div>

      <p v-if="!editingId" class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">
        Transaksi disimpan sebagai <strong>draft</strong> dan belum dihitung di laporan.
        Klik ikon centang (Posting) di daftar transaksi untuk menyelesaikannya.
      </p>

      <div class="flex justify-end gap-2 pt-2">
        <BaseButton type="button" variant="secondary" @click="showForm = false">Batal</BaseButton>
        <BaseButton :loading="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</BaseButton>
      </div>
    </form>
  </BaseModal>

  <ConfirmModal
    :show="!!voiding" :loading="voidBusy"
    title="Batalkan transaksi" confirm-text="Batalkan transaksi" loading-text="Membatalkan..."
    :message="`Batalkan transaksi ${voiding ? tanggal(voiding.date) : ''} (${voiding?.coa?.name})? Transaksi tidak lagi dihitung di laporan dan tidak dapat diubah.`"
    @close="voiding = null" @confirm="confirmVoid"
  />

  <ConfirmModal
    :show="!!deleting" :loading="deleteBusy"
    :message="`Hapus draft transaksi ${deleting ? tanggal(deleting.date) : ''} (${deleting?.coa?.name})?`"
    @close="deleting = null" @confirm="remove"
  />
</template>