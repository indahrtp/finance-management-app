<script setup>
import BaseModal from './BaseModal.vue'
import BaseButton from './BaseButton.vue'

defineProps({
  show: Boolean,
  message: String,
  loading: Boolean,
  title: { type: String, default: 'Konfirmasi hapus' },
  confirmText: { type: String, default: 'Hapus' },
  loadingText: { type: String, default: 'Menghapus...' },
  variant: { type: String, default: 'danger' },
})
defineEmits(['close', 'confirm'])
</script>

<template>
  <BaseModal :show="show" :title="title" @close="$emit('close')">
    <p class="text-sm text-gray-600 dark:text-gray-300">{{ message }}</p>
    <div class="mt-6 flex justify-end gap-2">
      <BaseButton type="button" variant="secondary" @click="$emit('close')">Batal</BaseButton>
      <BaseButton type="button" :variant="variant" :loading="loading" @click="$emit('confirm')">
        {{ loading ? loadingText : confirmText }}
      </BaseButton>
    </div>
  </BaseModal>
</template>