<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { Plus, Pencil, Trash2, Search } from 'lucide-vue-next'
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

const items = ref([])
const categories = ref([])
const loading = ref(false)
const search = ref('')
const filterCategory = ref('')

const categoryOptions = computed(() =>
  categories.value.map((c) => ({ value: c.id, label: `${c.name} (${c.type})` })),
)
const filterOptions = computed(() =>
  categories.value.map((c) => ({ value: c.id, label: c.name })),
)

// form
const showForm = ref(false)
const editingId = ref(null)
const form = reactive({ code: '', name: '', category_id: '' })
const errors = ref({})
const saving = ref(false)

// hapus
const deleting = ref(null)
const deleteBusy = ref(false)

async function loadCategories() {
  try {
    const { data } = await api.get('/categories')
    categories.value = data
  } catch (e) {
    toast.error(errorMessage(e))
  }
}

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/coa', {
      params: {
        search: search.value || undefined,
        category_id: filterCategory.value || undefined,
      },
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
  Object.assign(form, { code: '', name: '', category_id: '' })
  errors.value = {}
  showForm.value = true
}

function openEdit(item) {
  editingId.value = item.id
  Object.assign(form, { code: item.code, name: item.name, category_id: item.category_id })
  errors.value = {}
  showForm.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    if (editingId.value) {
      await api.put(`/coa/${editingId.value}`, form)
      toast.success('COA diperbarui.')
    } else {
      await api.post('/coa', form)
      toast.success('COA ditambahkan.')
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
    await api.delete(`/coa/${deleting.value.id}`)
    toast.success('COA dihapus.')
    deleting.value = null
    await load()
  } catch (e) {
    toast.error(errorMessage(e)) // contoh: 409 "sudah dipakai di transaksi"
    deleting.value = null
  } finally {
    deleteBusy.value = false
  }
}

// Pencarian ditunda 300 ms 
let timer
watch(search, () => {
  clearTimeout(timer)
  timer = setTimeout(load, 300)
})
watch(filterCategory, load)

onMounted(() => {
  loadCategories()
  load()
})
</script>

<template>
  <PageCard title="Chart of Account (COA)">
    <template #actions>
      <BaseButton @click="openCreate"><Plus :size="16" /> Tambah</BaseButton>
    </template>

    <div class="mb-4 flex flex-wrap gap-3">
      <div class="relative w-full sm:w-64">
        <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input
          v-model="search" placeholder="Cari kode atau nama..."
          class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
        />
      </div>
      <div class="w-full sm:w-52">
        <BaseSelect v-model="filterCategory" :options="filterOptions" placeholder="Semua kategori" clearable />
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-200 text-gray-500">
          <tr>
            <th class="py-3 pr-4 font-medium">Kode</th>
            <th class="py-3 pr-4 font-medium">Nama</th>
            <th class="py-3 pr-4 font-medium">Kategori</th>
            <th class="py-3 text-right font-medium">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="4" class="py-8 text-center text-gray-400">Memuat...</td></tr>
          <tr v-else-if="!items.length"><td colspan="4" class="py-8 text-center text-gray-400">Belum ada data.</td></tr>
          <tr v-for="a in items" :key="a.id" class="border-b border-gray-100 last:border-0">
            <td class="py-3 pr-4 font-mono">{{ a.code }}</td>
            <td class="py-3 pr-4 font-medium">{{ a.name }}</td>
            <td class="py-3 pr-4">
              <span class="mr-2">{{ a.category?.name }}</span>
              <TypeBadge :type="a.category?.type" />
            </td>
            <td class="py-3 text-right">
              <button class="rounded p-2 text-gray-500 hover:bg-gray-100" title="Ubah" @click="openEdit(a)">
                <Pencil :size="16" />
              </button>
              <button class="rounded p-2 text-red-500 hover:bg-red-50" title="Hapus" @click="deleting = a">
                <Trash2 :size="16" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </PageCard>

  <BaseModal :show="showForm" :title="editingId ? 'Ubah COA' : 'Tambah COA'" @close="showForm = false">
    <form class="space-y-4" @submit.prevent="save">
      <BaseInput v-model="form.code" label="Kode" :error="errors.code" />
      <BaseInput v-model="form.name" label="Nama" :error="errors.name" />
      <BaseSelect v-model="form.category_id" label="Kategori" :options="categoryOptions" :error="errors.category_id" />
      <div class="flex justify-end gap-2 pt-2">
        <BaseButton type="button" variant="secondary" @click="showForm = false">Batal</BaseButton>
        <BaseButton :loading="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</BaseButton>
      </div>
    </form>
  </BaseModal>

  <ConfirmModal
    :show="!!deleting" :loading="deleteBusy"
    :message="`Hapus COA &quot;${deleting?.code} - ${deleting?.name}&quot;?`"
    @close="deleting = null" @confirm="remove"
  />
</template>