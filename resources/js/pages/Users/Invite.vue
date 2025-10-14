<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, reactive, watch, onBeforeUnmount } from 'vue';
import Toast from './Toast.vue';

const MAX_EMAIL_LENGTH = 255;

interface Role {
  value: string;
  label: string;
}

interface Props {
  roles: Role[];
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Users', href: '/users' },
  { title: 'Invite User', href: '/users-invite' },
];

const form = useForm({
  email: '',
  role: 'staff',
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
  if (!form.email.trim()) {
    showToast('error', 'Email is required.');
    return;
  }

  if (!form.role) {
    showToast('error', 'Role is required.');
    return;
  }
  
  form.post('/users-invite', {
    onSuccess: () => {
      form.reset('email', 'role');
      form.role = 'staff'; // Reset to default
      showToast('success', 'Invitation sent successfully!');
    },
    onError: () => {
      const errors = form.errors;
      if (errors.email) {
        showToast('error', errors.email);
      } else if (errors.role) {
        showToast('error', errors.role);
      } else {
        showToast('error', 'Failed to send invitation. Please try again.');
      }
    },
  });
}

const remainingEmailChars = computed(() => MAX_EMAIL_LENGTH - form.email.length);
</script>

<template>
  <Head title="Invite User" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-3xl p-4">
      <!-- Toast -->
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" :duration="3500" />

      <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Invite User</h1>
        <Link href="/users" class="text-sm text-primary hover:underline">Back to users</Link>
      </div>

      <form @submit.prevent="submit" class="space-y-6 rounded-xl border border-sidebar-border/70 bg-white p-6 shadow-sm dark:border-sidebar-border dark:bg-neutral-950 sm:p-8">
        <div class="mb-4">
          <p class="text-sm text-neutral-600 dark:text-neutral-400">
            Send an invitation email to a new user. They will receive a secure link to register their account. 
            The invitation link will expire in 7 days.
          </p>
        </div>

        <!-- Email -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
              Email Address <span class="text-red-500">*</span>
            </label>
            <span class="text-xs text-neutral-500" :class="{ 'text-red-500': remainingEmailChars < 0 }">
              {{ remainingEmailChars }} / {{ MAX_EMAIL_LENGTH }}
            </span>
          </div>
          <input 
            v-model="form.email" 
            type="email" 
            :maxlength="MAX_EMAIL_LENGTH"
            placeholder="e.g., john.doe@example.com"
            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10" 
            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.email }"
          />
          <div v-if="form.errors.email" class="flex items-center gap-1 text-sm text-red-600">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>{{ form.errors.email }}</span>
          </div>
          <p class="text-xs text-neutral-500 dark:text-neutral-400">
            Enter the email address of the person you want to invite. They must not already have an account.
          </p>
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
            <option v-for="role in props.roles" :key="role.value" :value="role.value">{{ role.label }}</option>
          </select>
          <div v-if="form.errors.role" class="flex items-center gap-1 text-sm text-red-600">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>{{ form.errors.role }}</span>
          </div>
          <p class="text-xs text-neutral-500 dark:text-neutral-400">
            Select the role that will be assigned to the user when they register.
          </p>
        </div>

        <!-- Info Box -->
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-900/20">
          <div class="flex gap-3">
            <svg class="h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <div class="flex-1 text-sm text-blue-800 dark:text-blue-300">
              <p class="font-medium">What happens next?</p>
              <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
                <li>An email will be sent with a secure registration link</li>
                <li>The link expires in 7 days</li>
                <li>Once registered, the user will have the selected role</li>
                <li>You can change their role later from the user management page</li>
              </ul>
            </div>
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
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            {{ isSubmitting ? 'Sending...' : 'Send Invitation' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
