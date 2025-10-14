<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, reactive, watch, onBeforeUnmount } from 'vue';
import Toast from './Toast.vue';

const MAX_NAME_LENGTH = 100;
const MAX_EMAIL_LENGTH = 255;

interface Role {
  value: string;
  label: string;
}

const props = defineProps<{ roles: Role[] }>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Users', href: '/users' },
  { title: 'Create', href: '/users/create' },
];

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: '',
});

const page = usePage();

// Toast state
type ToastType = 'success' | 'error';
const toast = reactive<{ show: boolean; type: ToastType; message: string }>({
  show: false,
  type: 'success',
  message: '',
});
let toastTimer: number | undefined;
function showToast(type: ToastType, message: string, duration = 3500) {
  toast.show = true;
  toast.type = type;
  toast.message = message;
  if (toastTimer) window.clearTimeout(toastTimer);
  toastTimer = window.setTimeout(() => (toast.show = false), duration);
}
onBeforeUnmount(() => {
  if (toastTimer) window.clearTimeout(toastTimer);
});

// Watch for flash messages
const lastFlashMessage = ref<string | null>(null);
onMounted(() => {
  const anyPage: any = page.props;
  const msg = (anyPage?.flash?.success as string | undefined) || '';
  if (msg && msg !== lastFlashMessage.value) {
    showToast('success', msg);
    lastFlashMessage.value = msg;
  }
});
watch(
  () => (page.props as any)?.flash?.success as string | undefined,
  (val) => {
    const msg = val || '';
    if (msg && msg !== lastFlashMessage.value) {
      showToast('success', msg);
      lastFlashMessage.value = msg;
    }
  }
);

const isSubmitting = computed(() => form.processing);

function submit() {
  // Validate before submit
  if (!form.name.trim()) {
    showToast('error', 'Name is required.');
    return;
  }
  if (!form.email.trim()) {
    showToast('error', 'Email is required.');
    return;
  }
  
  form.post('/users', {
    onSuccess: () => {
      form.reset('name', 'email', 'password', 'password_confirmation', 'role');
      showToast('success', 'User created successfully!');
    },
    onError: () => {
      showToast('error', 'Failed to create user. Please check the form.');
    },
  });
}

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const remainingNameChars = computed(() => MAX_NAME_LENGTH - form.name.length);
const remainingEmailChars = computed(() => MAX_EMAIL_LENGTH - form.email.length);
</script>

<template>
  <Head title="Create User" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-3xl p-4">
      <!-- Toast -->
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" :duration="3500" />

      <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Create User</h1>
        <Link href="/users" class="text-sm text-primary hover:underline">Back to list</Link>
      </div>

      <form @submit.prevent="submit" class="space-y-6 rounded-xl border border-sidebar-border/70 bg-white p-6 shadow-sm dark:border-sidebar-border dark:bg-neutral-950 sm:p-8">
        <!-- Name -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
              Name <span class="text-red-500">*</span>
            </label>
            <span class="text-xs text-neutral-500" :class="{ 'text-red-500': remainingNameChars < 0 }">
              {{ remainingNameChars }} / {{ MAX_NAME_LENGTH }}
            </span>
          </div>
          <input 
            v-model="form.name" 
            type="text" 
            :maxlength="MAX_NAME_LENGTH"
            placeholder="e.g., John Doe"
            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10" 
            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.name }"
          />
          <div v-if="form.errors.name" class="flex items-center gap-1 text-sm text-red-600">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>{{ form.errors.name }}</span>
          </div>
        </div>

        <!-- Email -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
              Email <span class="text-red-500">*</span>
            </label>
            <span class="text-xs text-neutral-500" :class="{ 'text-red-500': remainingEmailChars < 0 }">
              {{ remainingEmailChars }} / {{ MAX_EMAIL_LENGTH }}
            </span>
          </div>
          <input 
            v-model="form.email" 
            type="email" 
            :maxlength="MAX_EMAIL_LENGTH"
            placeholder="e.g., john@example.com"
            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10" 
            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.email }"
          />
          <div v-if="form.errors.email" class="flex items-center gap-1 text-sm text-red-600">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>{{ form.errors.email }}</span>
          </div>
        </div>

        <!-- Password -->
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
            Password <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input 
              v-model="form.password" 
              :type="showPassword ? 'text' : 'password'" 
              placeholder="Enter a strong password"
              class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 pr-10 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10" 
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.password }"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-500 transition-colors hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200"
            >
              <svg v-if="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
              </svg>
            </button>
          </div>
          <div v-if="form.errors.password" class="flex items-center gap-1 text-sm text-red-600">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>{{ form.errors.password }}</span>
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
            Confirm Password <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input 
              v-model="form.password_confirmation" 
              :type="showPasswordConfirmation ? 'text' : 'password'" 
              placeholder="Re-enter your password"
              class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 pr-10 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10" 
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.password_confirmation }"
            />
            <button
              type="button"
              @click="showPasswordConfirmation = !showPasswordConfirmation"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-500 transition-colors hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200"
            >
              <svg v-if="showPasswordConfirmation" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
              </svg>
            </button>
          </div>
          <div v-if="form.errors.password_confirmation" class="flex items-center gap-1 text-sm text-red-600">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>{{ form.errors.password_confirmation }}</span>
          </div>
        </div>

        <!-- Role -->
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
            Role <span class="text-red-500">*</span>
          </label>
          <select 
            v-model="form.role" 
            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10"
            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.role }"
          >
            <option value="">Select a role</option>
            <option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option>
          </select>
          <div v-if="form.errors.role" class="flex items-center gap-1 text-sm text-red-600">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>{{ form.errors.role }}</span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-6 dark:border-neutral-800 sm:flex-row sm:justify-between sm:items-center">
          <Link 
            href="/users" 
            class="flex items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-6 py-3 text-sm font-medium text-neutral-700 transition-all hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:bg-neutral-800"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Cancel
          </Link>
          <button 
            :disabled="isSubmitting" 
            type="submit" 
            class="flex items-center justify-center gap-2 rounded-lg bg-black px-8 py-3 text-sm font-semibold text-white shadow-lg transition-all hover:bg-neutral-800 hover:shadow-xl active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-black dark:bg-white dark:text-black dark:hover:bg-neutral-200 dark:disabled:hover:bg-white"
          >
            <svg v-if="isSubmitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ isSubmitting ? 'Creating...' : 'Create User' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
