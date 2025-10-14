<script setup lang="ts">
import { computed, watch } from 'vue';
import { CheckCircle, XCircle } from 'lucide-vue-next';

interface Props {
  modelValue: boolean;
  type?: 'success' | 'error';
  message?: string;
  duration?: number;
}

const props = withDefaults(defineProps<Props>(), {
  type: 'success',
  message: '',
  duration: 3500,
});

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
}>();

const isVisible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const bgColor = computed(() =>
  props.type === 'success'
    ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
    : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
);

const textColor = computed(() =>
  props.type === 'success'
    ? 'text-green-800 dark:text-green-300'
    : 'text-red-800 dark:text-red-300'
);

const iconColor = computed(() =>
  props.type === 'success'
    ? 'text-green-600 dark:text-green-400'
    : 'text-red-600 dark:text-red-400'
);
</script>

<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 translate-y-2"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 translate-y-2"
  >
    <div
      v-if="isVisible"
      :class="[
        'fixed right-4 top-4 z-50 flex items-center gap-3 rounded-lg border px-4 py-3 shadow-lg',
        bgColor,
      ]"
      role="alert"
    >
      <CheckCircle v-if="type === 'success'" :class="['h-5 w-5', iconColor]" />
      <XCircle v-else :class="['h-5 w-5', iconColor]" />
      <p :class="['text-sm font-medium', textColor]">{{ message }}</p>
      <button
        @click="isVisible = false"
        :class="['ml-2 rounded p-1 hover:bg-black/5 dark:hover:bg-white/5', textColor]"
      >
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
            clip-rule="evenodd"
          />
        </svg>
      </button>
    </div>
  </Transition>
</template>
