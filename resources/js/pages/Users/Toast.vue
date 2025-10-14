<script setup lang="ts">
import { onBeforeUnmount, reactive, ref, watch } from 'vue';

interface Props {
  modelValue: boolean;
  type?: 'success' | 'error' | 'info' | 'warning';
  message?: string;
  duration?: number; // ms
}

const props = withDefaults(defineProps<Props>(), {
  type: 'info',
  message: '',
  duration: 3500,
});

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void;
}>();

let timer: number | undefined;

function close() {
  emit('update:modelValue', false);
  if (timer) window.clearTimeout(timer);
  timer = undefined;
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      if (timer) window.clearTimeout(timer);
      if (props.duration! > 0) {
        timer = window.setTimeout(() => close(), props.duration);
      }
    } else if (timer) {
      window.clearTimeout(timer);
      timer = undefined;
    }
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  if (timer) window.clearTimeout(timer);
});

const palette: Record<string, string> = {
  success:
    'border-green-300 bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-200',
  error:
    'border-red-300 bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-200',
  info:
    'border-blue-300 bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200',
  warning:
    'border-yellow-300 bg-yellow-50 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200',
};
</script>

<template>
  <transition name="fade">
    <div
      v-if="modelValue"
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
