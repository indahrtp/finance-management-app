<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

defineProps({ delay: { type: Number, default: 0 } })

const el = ref(null)
const visible = ref(false)
let observer

onMounted(() => {
  if (!('IntersectionObserver' in window)) {
    visible.value = true
    return
  }
  observer = new IntersectionObserver(
    ([entry]) => {
      if (entry.isIntersecting) {
        visible.value = true
        observer.disconnect()
      }
    },
    { threshold: 0.15 },
  )
  observer.observe(el.value)
})

onBeforeUnmount(() => observer?.disconnect())
</script>

<template>
  <div
    ref="el"
    class="transition duration-700 ease-out motion-reduce:transition-none"
    :class="visible
      ? 'translate-y-0 opacity-100'
      : 'translate-y-6 opacity-0 motion-reduce:translate-y-0 motion-reduce:opacity-100'"
    :style="{ transitionDelay: delay + 'ms' }"
  >
    <slot />
  </div>
</template>