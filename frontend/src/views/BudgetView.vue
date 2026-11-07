<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Pencil, Plus, Trash2 } from 'lucide-vue-next'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { validationErrors, errorMessage } from '@/utils/errors'
import { rupiah } from '@/utils/format'
import PageCard from '@/components/ui/PageCard.vue'
import StatCard from '@/components/ui/StatCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'

const toast = useToast()

const month = ref(new Date().toLocaleDateString('sv-SE').slice(0, 7)) // YYYY-MM
const data = ref(null)
const loading = ref(false)

const monthLabel = computed(() =>
  new Date(`${month.value}-01T00:00:00`).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }),
)

const remaining = computed(() =>
  data.value ? data.value.summary.total_budget - data.value.summary.total_actual : 0,
)

const statusUI = {
  safe: { text: 'Aman', badge: 'bg-emerald-50 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400', bar: 'bg-emerald-500' },
  warning: { text: 'Hampir habis', badge: 'bg-amber-50 dark:bg-amber-500/15 text-amber-700 dark:text-amber-400', bar: 'bg-amber-500' },
  over: { text: 'Melebihi', badge: 'bg-red-50 dark:bg-red-500/15 text-red-700 dark:text-red-400', bar: 'bg-red-500' },
  none: { text: 'Belum diatur', badge: 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400', bar: 'bg-gray-300' },
}

async function load() {
  if (!month.value) return
  loading.value = true
  try {
    const res = await api.get('/budgets', { params: { month: month.value } })
    data.value = res.data
  } catch (e) {
    toast.error(errorMessage(e))
  } finally {
    loading.value = false
  }
}

// form atur anggaran
const showForm = ref(false)
const target = ref(null)
const amount = ref('')
const errors = ref({})
const saving = ref(false)

function openSet(row) {
  target.value = row
  amount.value = row.budget ?? ''
  errors.value = {}
  showForm.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    await api.post('/budgets', {
      category_id: target.value.category_id,
      month: month.value,
      amount: Number(amount.value) || 0,
    })
    toast.success('Anggaran disimpan.')
    showForm.value = false
    await load()
  } catch (e) {
    if (e.response?.status === 422) errors.value = validationErrors(e)
    else toast.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

// hapus
const deleting = ref(null)
const deleteBusy = ref(false)

async function remove() {
  deleteBusy.value = true
  try {
    await api.delete(`/budgets/${deleting.value.budget_id}`)
    toast.success('Anggaran dihapus.')
    deleting.value = null
    await load()
  } catch (e) {
    toast.error(errorMessage(e))
    deleting.value = null
  } finally {
    deleteBusy.value = false
  }
}

watch(month, load)
onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-xl font-semibold">Anggaran {{ monthLabel }}</h1>
      <input
        v-model="month" type="month"
        class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
      />
    </div>

    <p v-if="loading && !data" class="py-12 text-center text-gray-400">Memuat...</p>

    <template v-else-if="data">
      <div class="grid gap-4 sm:grid-cols-3" :class="{ 'opacity-60': loading }">
        <StatCard title="Total Anggaran" :value="rupiah(data.summary.total_budget)" hint="Kategori yang dianggarkan" tone="brand" />
        <StatCard title="Realisasi" :value="rupiah(data.summary.total_actual)" hint="Pengeluaran pada kategori tersebut" tone="orange" />
        <StatCard
          title="Sisa Anggaran" :value="rupiah(remaining)"
          :hint="remaining < 0 ? 'Melebihi anggaran' : 'Masih tersedia'"
          :tone="remaining < 0 ? 'orange' : 'green'"
        />
      </div>

      <p v-if="data.summary.unbudgeted_actual > 0" class="rounded-lg bg-amber-50 dark:bg-amber-500/15 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">
        Ada pengeluaran {{ rupiah(data.summary.unbudgeted_actual) }} pada kategori yang belum dianggarkan.
      </p>

      <PageCard title="Anggaran per Kategori">
        <div class="overflow-x-auto" :class="{ 'opacity-60': loading }">
          <table class="w-full min-w-[820px] text-left text-sm">
            <thead class="border-b border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400">
              <tr>
                <th class="py-3 pr-4 font-medium">Kategori</th>
                <th class="py-3 pr-4 text-right font-medium">Anggaran</th>
                <th class="py-3 pr-4 text-right font-medium">Realisasi</th>
                <th class="py-3 pr-4 text-right font-medium">Sisa</th>
                <th class="w-56 py-3 pr-4 font-medium">Pemakaian</th>
                <th class="py-3 text-right font-medium">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!data.items.length">
                <td colspan="6" class="py-8 text-center text-gray-400">Belum ada kategori expense.</td>
              </tr>
              <tr v-for="r in data.items" :key="r.category_id" class="border-b border-gray-100 dark:border-gray-800 last:border-0">
                <td class="py-3 pr-4 font-medium">{{ r.category }}</td>
                <td class="py-3 pr-4 text-right">{{ r.budget !== null ? rupiah(r.budget) : '-' }}</td>
                <td class="py-3 pr-4 text-right">{{ rupiah(r.actual) }}</td>
                <td class="py-3 pr-4 text-right font-medium" :class="r.remaining < 0 ? 'text-red-600 dark:text-red-400' : ''">
                  {{ r.remaining !== null ? rupiah(r.remaining) : '-' }}
                </td>
                <td class="py-3 pr-4">
                  <template v-if="r.budget !== null">
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                      <div class="h-full rounded-full transition-all" :class="statusUI[r.status].bar" :style="{ width: Math.min(r.percent, 100) + '%' }" />
                    </div>
                    <div class="mt-1.5 flex items-center justify-between">
                      <span class="text-xs text-gray-500 dark:text-gray-400">{{ r.percent }}%</span>
                      <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusUI[r.status].badge">
                        {{ statusUI[r.status].text }}
                      </span>
                    </div>
                  </template>
                  <span v-else class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusUI.none.badge">
                    {{ statusUI.none.text }}
                  </span>
                </td>
                <td class="whitespace-nowrap py-3 text-right">
                  <button
                    v-if="r.budget === null"
                    class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium text-brand-500 hover:bg-brand-50 dark:hover:bg-brand-500/15"
                    @click="openSet(r)"
                  >
                    <Plus :size="14" /> Atur
                  </button>
                  <template v-else>
                    <button class="rounded p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800" title="Ubah" @click="openSet(r)">
                      <Pencil :size="16" />
                    </button>
                    <button class="rounded p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/15" title="Hapus" @click="deleting = r">
                      <Trash2 :size="16" />
                    </button>
                  </template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
          Realisasi dihitung dari total debit dikurangi credit pada kategori tersebut di bulan terpilih.
        </p>
      </PageCard>
    </template>
  </div>

  <BaseModal :show="showForm" :title="`Anggaran ${target?.category ?? ''}`" @close="showForm = false">
    <form class="space-y-4" @submit.prevent="save">
      <p class="text-sm text-gray-500 dark:text-gray-400">Periode: {{ monthLabel }}</p>
      <BaseInput
        v-model="amount" type="number" label="Jumlah anggaran (Rp)"
        :error="errors.amount || errors.category_id"
      />
      <div class="flex justify-end gap-2 pt-2">
        <BaseButton type="button" variant="secondary" @click="showForm = false">Batal</BaseButton>
        <BaseButton :loading="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</BaseButton>
      </div>
    </form>
  </BaseModal>

  <ConfirmModal
    :show="!!deleting" :loading="deleteBusy"
    :message="`Hapus anggaran ${deleting?.category} untuk ${monthLabel}?`"
    @close="deleting = null" @confirm="remove"
  />
</template>