<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Plus, Pencil, Trash2, Banknote, Landmark, Smartphone, Wallet } from 'lucide-vue-next'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { validationErrors, errorMessage } from '@/utils/errors'
import { rupiah } from '@/utils/format'
import PageCard from '@/components/ui/PageCard.vue'
import StatCard from '@/components/ui/StatCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'

const toast = useToast()

const typeUI = {
  cash: { label: 'Tunai', icon: Banknote },
  bank: { label: 'Rekening Bank', icon: Landmark },
  ewallet: { label: 'E-Wallet', icon: Smartphone },
}
const typeOptions = Object.entries(typeUI).map(([value, t]) => ({ value, label: t.label }))

const items = ref([])
const loading = ref(false)
const total = computed(() => items.value.reduce((sum, w) => sum + w.balance, 0))

const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', type: '', opening_balance: '' })
const errors = ref({})
const saving = ref(false)

const deleting = ref(null)
const deleteBusy = ref(false)

async function load() {
  loading.value = true
  try {
    items.value = (await api.get('/wallets')).data
  } catch (e) {
    toast.error(errorMessage(e))
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingId.value = null
  Object.assign(form, { name: '', type: '', opening_balance: 0 })
  errors.value = {}
  showForm.value = true
}

function openEdit(w) {
  editingId.value = w.id
  Object.assign(form, { name: w.name, type: w.type, opening_balance: w.opening_balance })
  errors.value = {}
  showForm.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const payload = { ...form, opening_balance: Number(form.opening_balance) || 0 }
    if (editingId.value) {
      await api.put(`/wallets/${editingId.value}`, payload)
      toast.success('Rekening diperbarui.')
    } else {
      await api.post('/wallets', payload)
      toast.success('Rekening ditambahkan.')
    }
    showForm.value = false
    await load()
  } catch (e) {
    if (e.response?.status === 422) errors.value = validationErrors(e)
    else toast.error(errorMessage(e))
  } finally {
    saving.value = false
  }
}

async function remove() {
  deleteBusy.value = true
  try {
    await api.delete(`/wallets/${deleting.value.id}`)
    toast.success('Rekening dihapus.')
    deleting.value = null
    await load()
  } catch (e) {
    toast.error(errorMessage(e))
    deleting.value = null
  } finally {
    deleteBusy.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <StatCard title="Total Saldo" :value="rupiah(total)" hint="Gabungan semua rekening dan dompet" tone="brand">
        <template #icon><Wallet :size="22" /></template>
      </StatCard>
    </div>

    <PageCard title="Rekening & Dompet">
      <template #actions>
        <BaseButton @click="openCreate"><Plus :size="16" /> Tambah</BaseButton>
      </template>

      <p v-if="loading" class="py-8 text-center text-gray-400">Memuat...</p>
      <p v-else-if="!items.length" class="py-8 text-center text-gray-400">Belum ada rekening.</p>

      <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div
          v-for="w in items" :key="w.id"
          class="rounded-2xl border border-gray-200 p-5 dark:border-gray-800"
        >
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="rounded-xl bg-brand-50 p-2.5 text-brand-500 dark:bg-brand-500/15">
                <component :is="typeUI[w.type].icon" :size="20" />
              </div>
              <div>
                <p class="font-semibold text-gray-900 dark:text-white">{{ w.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ typeUI[w.type].label }}</p>
              </div>
            </div>
            <div class="flex">
              <button class="rounded p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800" title="Ubah" @click="openEdit(w)">
                <Pencil :size="16" />
              </button>
              <button class="rounded p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/15" title="Hapus" @click="deleting = w">
                <Trash2 :size="16" />
              </button>
            </div>
          </div>

          <p class="mt-5 text-2xl font-semibold" :class="w.balance < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'">
            {{ rupiah(w.balance) }}
          </p>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Saldo awal {{ rupiah(w.opening_balance) }} · {{ w.transactions_count }} transaksi
          </p>
        </div>
      </div>

      <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
        Saldo = saldo awal + credit − debit dari transaksi berstatus posted.
      </p>
    </PageCard>
  </div>

  <BaseModal :show="showForm" :title="editingId ? 'Ubah rekening' : 'Tambah rekening'" @close="showForm = false">
    <form class="space-y-4" @submit.prevent="save">
      <BaseInput v-model="form.name" label="Nama" :error="errors.name" />
      <BaseSelect v-model="form.type" label="Jenis" :options="typeOptions" :error="errors.type" />
      <BaseInput v-model="form.opening_balance" type="number" label="Saldo awal (Rp)" :error="errors.opening_balance" />
      <div class="flex justify-end gap-2 pt-2">
        <BaseButton type="button" variant="secondary" @click="showForm = false">Batal</BaseButton>
        <BaseButton :loading="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</BaseButton>
      </div>
    </form>
  </BaseModal>

  <ConfirmModal
    :show="!!deleting" :loading="deleteBusy"
    :message="`Hapus rekening &quot;${deleting?.name}&quot;?`"
    @close="deleting = null" @confirm="remove"
  />
</template>