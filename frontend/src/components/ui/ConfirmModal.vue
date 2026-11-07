<script setup>
import BaseModal from './BaseModal.vue'
import BaseButton from './BaseButton.vue'

defineProps({ show: Boolean, message: String, loading: Boolean })
defineEmits(['close', 'confirm'])
</script>

<template>
  <BaseModal :show="show" title="Konfirmasi hapus" @close="$emit('close')">
    <p class="text-sm text-gray-600 dark:text-gray-300">{{ message }}</p>
    <div class="mt-6 flex justify-end gap-2">
      <BaseButton type="button" variant="secondary" @click="$emit('close')">Batal</BaseButton>
      <BaseButton type="button" variant="danger" :loading="loading" @click="$emit('confirm')">
        {{ loading ? 'Menghapus...' : 'Hapus' }}
      </BaseButton>
    </div>
  </BaseModal>
</template>