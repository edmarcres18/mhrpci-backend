<script setup lang="ts">
import { CheckCircle2, XCircle, X } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
  modelValue: boolean;
  type: 'success' | 'error';
  message: string;
  duration?: number;
}

const props = withDefaults(defineProps<Props>(), {
  duration: 3000,
});

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
}>();

const bgClass = computed(() => {
  return props.type === 'success'
    ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800'
    : 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800';
});

const textClass = computed(() => {
  return props.type === 'success'
    ? 'text-green-800 dark:text-green-200'
    : 'text-red-800 dark:text-red-200';
});

const iconClass = computed(() => {
  return props.type === 'success'
    ? 'text-green-600 dark:text-green-400'
    : 'text-red-600 dark:text-red-400';
});

function close() {
  emit('update:modelValue', false);
}
</script>

<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="modelValue"
      class="fixed right-4 top-4 z-50 w-full max-w-sm rounded-lg border p-4 shadow-lg"
      :class="bgClass"
      role="alert"
    >
      <div class="flex items-start gap-3">
        <CheckCircle2 v-if="type === 'success'" class="h-5 w-5 flex-shrink-0" :class="iconClass" />
        <XCircle v-else class="h-5 w-5 flex-shrink-0" :class="iconClass" />
        <div class="flex-1 text-sm font-medium" :class="textClass">
          {{ message }}
        </div>
        <button
          @click="close"
          class="flex-shrink-0 rounded-lg p-1 hover:bg-black/5 dark:hover:bg-white/5"
          :class="textClass"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
    </div>
  </Transition>
</template>
