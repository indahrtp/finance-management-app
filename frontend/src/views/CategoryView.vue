<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { Plus, Pencil, Trash2 } from 'lucide-vue-next'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { validationErrors, errorMessage } from '@/utils/errors'
import PageCard from '@/components/ui/PageCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import TypeBadge from '@/components/ui/TypeBadge.vue'

const toast = useToast()

const typeOptions = [
  { value: 'income', label: 'Income (pemasukan)' },
  { value: 'expense', label: 'Expense (pengeluaran)' },
]

const items = ref([])
const loading = ref(false)
const filterType = ref('')

// form
const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', type: '' })
const errors = ref({})
const saving = ref(false)

// hapus
const deleting = ref(null)
const deleteBusy = ref(false)

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/categories', {
      params: { type: filterType.value || undefined },
    })
    items.value = data
  } catch (e) {
    toast.error(errorMessage(e))
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingId.value = null
  Object.assign(form, { name: '', type: '' })
  errors.value = {}
  showForm.value = true
}

function openEdit(item) {
  editingId.value = item.id
  Object.assign(form, { name: item.name, type: item.type })
  errors.value = {}
  showForm.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    if (editingId.value) {
      await api.put(`/categories/${editingId.value}`, form)
      toast.success('Kategori diperbarui.')
    } else {
      await api.post('/categories', form)
      toast.success('Kategori ditambahkan.')
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
    await api.delete(`/categories/${deleting.value.id}`)
    toast.success('Kategori dihapus.')
    deleting.value = null
    await load()
  } catch (e) {
    toast.error(errorMessage(e)) // contoh: 409 "masih dipakai oleh COA"
    deleting.value = null
  } finally {
    deleteBusy.value = false
  }
}

watch(filterType, load)
onMounted(load)
</script>

<template>
  <PageCard title="Kategori">
    <template #actions>
      <div class="flex items-center gap-2">
        <div class="w-44">
          <BaseSelect v-model="filterType" :options="typeOptions" placeholder="Semua tipe" clearable />
        </div>
        <BaseButton @click="openCreate"><Plus :size="16" /> Tambah</BaseButton>
      </div>
    </template>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-200 text-gray-500">
          <tr>
            <th class="py-3 pr-4 font-medium">Nama</th>
            <th class="py-3 pr-4 font-medium">Tipe</th>
            <th class="py-3 pr-4 text-right font-medium">Jumlah COA</th>
            <th class="py-3 text-right font-medium">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="4" class="py-8 text-center text-gray-400">Memuat...</td></tr>
          <tr v-else-if="!items.length"><td colspan="4" class="py-8 text-center text-gray-400">Belum ada data.</td></tr>
          <tr v-for="c in items" :key="c.id" class="border-b border-gray-100 last:border-0">
            <td class="py-3 pr-4 font-medium">{{ c.name }}</td>
            <td class="py-3 pr-4"><TypeBadge :type="c.type" /></td>
            <td class="py-3 pr-4 text-right">{{ c.coa_count }}</td>
            <td class="py-3 text-right">
              <button class="rounded p-2 text-gray-500 hover:bg-gray-100" title="Ubah" @click="openEdit(c)">
                <Pencil :size="16" />
              </button>
              <button class="rounded p-2 text-red-500 hover:bg-red-50" title="Hapus" @click="deleting = c">
                <Trash2 :size="16" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </PageCard>

  <BaseModal :show="showForm" :title="editingId ? 'Ubah kategori' : 'Tambah kategori'" @close="showForm = false">
    <form class="space-y-4" @submit.prevent="save">
      <BaseInput v-model="form.name" label="Nama" :error="errors.name" />
      <BaseSelect v-model="form.type" label="Tipe" :options="typeOptions" :error="errors.type" />
      <div class="flex justify-end gap-2 pt-2">
        <BaseButton type="button" variant="secondary" @click="showForm = false">Batal</BaseButton>
        <BaseButton :loading="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</BaseButton>
      </div>
    </form>
  </BaseModal>

  <ConfirmModal
    :show="!!deleting" :loading="deleteBusy"
    :message="`Hapus kategori &quot;${deleting?.name}&quot;?`"
    @close="deleting = null" @confirm="remove"
  />
</template>