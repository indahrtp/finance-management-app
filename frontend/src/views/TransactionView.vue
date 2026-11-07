<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { Plus, Pencil, Trash2, Search } from 'lucide-vue-next'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { validationErrors, errorMessage } from '@/utils/errors'
import { rupiah, tanggal } from '@/utils/format'
import PageCard from '@/components/ui/PageCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import PaginationBar from '@/components/ui/PaginationBar.vue'

const toast = useToast()

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 })
const coaList = ref([])
const loading = ref(false)

// filter
const filters = reactive({ search: '', coa_id: '', start_date: '', end_date: '' })
const page = ref(1)

const coaOptions = computed(() =>
  coaList.value.map((a) => ({ value: a.id, label: `${a.code} - ${a.name}` })),
)

// form
const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ date: '', coa_id: '', description: '', amount: '' })
const errors = ref({})
const saving = ref(false)

// COA yang dipilih di form menentukan sisi debit/credit
const selectedCoa = computed(() => coaList.value.find((a) => a.id === form.coa_id))
const side = computed(() => {
  const type = selectedCoa.value?.category?.type
  if (type === 'income') return 'credit'
  if (type === 'expense') return 'debit'
  return null
})
const amountLabel = computed(() => {
  if (side.value === 'credit') return 'Jumlah pemasukan (credit)'
  if (side.value === 'debit') return 'Jumlah pengeluaran (debit)'
  return 'Jumlah'
})

// hapus
const deleting = ref(null)
const deleteBusy = ref(false)

const today = () => new Date().toLocaleDateString('sv-SE')

async function loadCoa() {
  try {
    const { data } = await api.get('/coa')
    coaList.value = data
  } catch (e) {
    toast.error(errorMessage(e))
  }
}

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/transactions', {
      params: {
        page: page.value,
        search: filters.search || undefined,
        coa_id: filters.coa_id || undefined,
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

function goTo(p) {
  page.value = p
  load()
}

function resetAndLoad() {
  page.value = 1
  load()
}

function openCreate() {
  editingId.value = null
  Object.assign(form, { date: today(), coa_id: '', description: '', amount: '' })
  errors.value = {}
  showForm.value = true
}

function openEdit(t) {
  editingId.value = t.id
  const debit = Number(t.debit)
  Object.assign(form, {
    date: t.date,
    coa_id: t.coa_id,
    description: t.description || '',
    amount: debit > 0 ? debit : Number(t.credit),
  })
  errors.value = {}
  showForm.value = true
}

async function save() {
  errors.value = {}

  if (!side.value) {
    errors.value = { coa_id: 'Pilih COA terlebih dahulu.' }
    return
  }

  const amount = Number(form.amount) || 0
  const payload = {
    date: form.date,
    coa_id: form.coa_id,
    description: form.description,
    debit: side.value === 'debit' ? amount : 0,
    credit: side.value === 'credit' ? amount : 0,
  }

  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/transactions/${editingId.value}`, payload)
      toast.success('Transaksi diperbarui.')
    } else {
      await api.post('/transactions', payload)
      toast.success('Transaksi ditambahkan.')
    }
    showForm.value = false
    await load()
  } catch (e) {
    if (e.response?.status === 422) {
      const err = validationErrors(e)
      // debit/credit di backend = satu kolom "jumlah" di form
      err.amount = err.debit || err.credit
      errors.value = err
    } else {
      toast.error(errorMessage(e))
    }
  } finally {
    saving.value = false
  }
}

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

function clearFilters() {
  Object.assign(filters, { search: '', coa_id: '', start_date: '', end_date: '' })
}

let timer
watch(() => filters.search, () => {
  clearTimeout(timer)
  timer = setTimeout(resetAndLoad, 300)
})
watch(() => [filters.coa_id, filters.start_date, filters.end_date], resetAndLoad)

onMounted(() => {
  loadCoa()
  load()
})
</script>

<template>
  <PageCard title="Transaksi">
    <template #actions>
      <BaseButton @click="openCreate"><Plus :size="16" /> Tambah</BaseButton>
    </template>

    <!-- Filter -->
    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
      <div class="relative">
        <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input
          v-model="filters.search" placeholder="Cari deskripsi..."
          class="w-full rounded-lg border border-gray-300 dark:border-gray-700 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
        />
      </div>
      <BaseSelect v-model="filters.coa_id" :options="coaOptions" placeholder="Semua COA" clearable />
      <BaseInput v-model="filters.start_date" type="date" />
      <BaseInput v-model="filters.end_date" type="date" />
      <BaseButton variant="secondary" @click="clearFilters">Reset filter</BaseButton>
    </div>

    <!-- Tabel -->
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400">
          <tr>
            <th class="py-3 pr-4 font-medium">Tanggal</th>
            <th class="py-3 pr-4 font-medium">COA</th>
            <th class="py-3 pr-4 font-medium">Deskripsi</th>
            <th class="py-3 pr-4 text-right font-medium">Debit</th>
            <th class="py-3 pr-4 text-right font-medium">Credit</th>
            <th class="py-3 text-right font-medium">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="6" class="py-8 text-center text-gray-400">Memuat...</td></tr>
          <tr v-else-if="!items.length"><td colspan="6" class="py-8 text-center text-gray-400">Belum ada transaksi.</td></tr>
          <tr v-for="t in items" :key="t.id" class="border-b border-gray-100 dark:border-gray-800 last:border-0">
            <td class="whitespace-nowrap py-3 pr-4">{{ tanggal(t.date) }}</td>
            <td class="py-3 pr-4">
              <span class="font-mono text-gray-500 dark:text-gray-400">{{ t.coa?.code }}</span>
              {{ t.coa?.name }}
            </td>
            <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ t.description || '-' }}</td>
            <td class="whitespace-nowrap py-3 pr-4 text-right" :class="Number(t.debit) > 0 ? 'font-medium text-orange-600 dark:text-orange-400' : 'text-gray-300'">
              {{ Number(t.debit) > 0 ? rupiah(t.debit) : '-' }}
            </td>
            <td class="whitespace-nowrap py-3 pr-4 text-right" :class="Number(t.credit) > 0 ? 'font-medium text-emerald-600 dark:text-emerald-400' : 'text-gray-300'">
              {{ Number(t.credit) > 0 ? rupiah(t.credit) : '-' }}
            </td>
            <td class="whitespace-nowrap py-3 text-right">
              <button class="rounded p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800" title="Ubah" @click="openEdit(t)">
                <Pencil :size="16" />
              </button>
              <button class="rounded p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/15" title="Hapus" @click="deleting = t">
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
      <BaseInput v-model="form.date" type="date" label="Tanggal" :error="errors.date" />
      <BaseSelect v-model="form.coa_id" label="COA" :options="coaOptions" :error="errors.coa_id" />

      <div>
        <BaseInput v-model="form.amount" type="number" :label="amountLabel" :error="errors.amount" />
        <p v-if="side" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          COA ini bertipe {{ side === 'credit' ? 'income' : 'expense' }}, jadi nilai disimpan di kolom {{ side }}.
        </p>
      </div>

      <BaseInput v-model="form.description" label="Deskripsi (opsional)" :error="errors.description" />

      <div class="flex justify-end gap-2 pt-2">
        <BaseButton type="button" variant="secondary" @click="showForm = false">Batal</BaseButton>
        <BaseButton :loading="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</BaseButton>
      </div>
    </form>
  </BaseModal>

  <ConfirmModal
    :show="!!deleting" :loading="deleteBusy"
    :message="`Hapus transaksi ${deleting ? tanggal(deleting.date) : ''} (${deleting?.coa?.name})?`"
    @close="deleting = null" @confirm="remove"
  />
</template>