<script setup lang="ts">
import { onMounted, onBeforeUnmount, watch, ref } from 'vue';

const props = withDefaults(defineProps<{
  modelValue: boolean;
  type?: 'success' | 'error';
  message?: string;
  duration?: number;
}>(), {
  type: 'success',
  message: '',
  duration: 3500,
});

const emit = defineEmits<{ (e: 'update:modelValue', v: boolean): void }>();

let timer: number | undefined;
const visible = ref<boolean>(props.modelValue);

function close() {
  visible.value = false;
  emit('update:modelValue', false);
}

function startTimer() {
  if (timer) window.clearTimeout(timer);
  timer = window.setTimeout(() => close(), props.duration);
}

onMounted(() => {
  if (props.modelValue) {
    visible.value = true;
    startTimer();
  }
});

onBeforeUnmount(() => {
  if (timer) window.clearTimeout(timer);
});

watch(() => props.modelValue, (val) => {
  visible.value = val;
  if (val) startTimer();
});
</script>

<template>
  <transition name="toast-fade">
    <div v-if="visible" class="fixed right-4 top-4 z-50">
      <div
        class="flex items-start gap-3 rounded-lg px-4 py-3 text-sm shadow-lg"
        :class="[
          props.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white',
        ]"
      >
        <span class="mt-0.5">{{ props.message }}</span>
        <button @click="close" class="ml-auto rounded px-2 py-1 text-xs hover:bg-black/10">Close</button>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.toast-fade-enter-from,
.toast-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.98);
}
</style>