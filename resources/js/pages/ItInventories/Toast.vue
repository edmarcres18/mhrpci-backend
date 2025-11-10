<script setup lang="ts">
interface Props {
  modelValue: boolean;
  type?: 'success' | 'error' | 'info';
  message: string;
  duration?: number;
}

const props = defineProps<Props>();
const emit = defineEmits(['update:modelValue']);

function close() {
  emit('update:modelValue', false);
}

const palette: Record<string, string> = {
  success:
    'border-green-200 bg-green-50 text-green-800 shadow-green-200/50 dark:border-green-900 dark:bg-green-900/40 dark:text-green-100',
  error:
    'border-red-200 bg-red-50 text-red-800 shadow-red-200/50 dark:border-red-900 dark:bg-red-900/40 dark:text-red-100',
  info:
    'border-blue-200 bg-blue-50 text-blue-800 shadow-blue-200/50 dark:border-blue-900 dark:bg-blue-900/40 dark:text-blue-100',
};
</script>

<template>
  <transition name="fade">
    <div
      v-if="props.modelValue"
      class="fixed bottom-4 right-4 z-[1000] w-[calc(100vw-2rem)] max-w-md rounded-lg border p-4 shadow-xl backdrop-blur-sm sm:w-auto sm:min-w-80"
      role="status"
      aria-live="polite"
      aria-atomic="true"
      :class="palette[type || 'info']"
    >
      <div class="flex items-start gap-3">
        <div class="flex-1 break-words text-sm font-medium leading-relaxed">{{ message }}</div>
        <button
          type="button"
          class="shrink-0 rounded px-2 py-1 text-xs transition-colors hover:bg-black/10 dark:hover:bg-white/20"
          aria-label="Dismiss notification"
          @click="close"
        >
          ✕
        </button>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>