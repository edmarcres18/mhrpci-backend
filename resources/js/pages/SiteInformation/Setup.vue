<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, reactive, watch, onBeforeUnmount } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog';
import Toast from './Toast.vue';

const MAX_EMAIL_LENGTH = 255;
const MAX_TEL_LENGTH = 50;
const MAX_PHONE_LENGTH = 50;
const MAX_FACEBOOK_LENGTH = 255;
const MAX_TELEGRAM_LENGTH = 100;
const MAX_VIBER_LENGTH = 50;
const MAX_WHATSAPP_LENGTH = 50;
const MAX_ADDRESS_LENGTH = 255;

interface SiteInformation {
  id?: number;
  email_address?: string | null;
  tel_no?: string | null;
  phone_no?: string | null;
  address?: string | null;
  telegram?: string | null;
  facebook?: string | null;
  viber?: string | null;
  whatsapp?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
}

const page = usePage();
const siteInformation = computed(() => (page.props.siteInformation as SiteInformation | null) ?? null);
const canResetSiteInfo = computed(() => (page.props.auth as any)?.canDeleteProducts ?? false);

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Site Information', href: '/site-information' },
];

const form = useForm({
  email_address: '',
  tel_no: '',
  phone_no: '',
  address: '',
  telegram: '',
  facebook: '',
  viber: '',
  whatsapp: '',
});

// Populate form if data exists
onMounted(() => {
  if (siteInformation.value) {
    form.email_address = siteInformation.value.email_address || '';
    form.tel_no = siteInformation.value.tel_no || '';
    form.phone_no = siteInformation.value.phone_no || '';
    form.address = siteInformation.value.address || '';
    form.telegram = siteInformation.value.telegram || '';
    form.facebook = siteInformation.value.facebook || '';
    form.viber = siteInformation.value.viber || '';
    form.whatsapp = siteInformation.value.whatsapp || '';
  }
});

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

function closeToast() {
  toast.show = false;
  if (toastTimer) window.clearTimeout(toastTimer);
  toastTimer = undefined;
}

onBeforeUnmount(() => {
  if (toastTimer) window.clearTimeout(toastTimer);
});

// Display server flash messages as toast
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
  // All fields are now optional, no client-side validation needed

  form.post('/site-information', {
    onSuccess: () => {
      showToast('success', siteInformation.value ? 'Site information updated successfully!' : 'Site information created successfully!');
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0] as string;
      showToast('error', firstError || 'Failed to save site information. Please check the form.');
    },
  });
}

// Reset confirmation modal state
const confirmResetOpen = ref(false);
const isResetting = ref(false);

const remainingEmailChars = computed(() => MAX_EMAIL_LENGTH - form.email_address.length);
const remainingTelChars = computed(() => MAX_TEL_LENGTH - form.tel_no.length);
const remainingPhoneChars = computed(() => MAX_PHONE_LENGTH - form.phone_no.length);
const remainingAddressChars = computed(() => MAX_ADDRESS_LENGTH - form.address.length);
const remainingFacebookChars = computed(() => MAX_FACEBOOK_LENGTH - form.facebook.length);
const remainingTelegramChars = computed(() => MAX_TELEGRAM_LENGTH - form.telegram.length);
const remainingViberChars = computed(() => MAX_VIBER_LENGTH - form.viber.length);
const remainingWhatsappChars = computed(() => MAX_WHATSAPP_LENGTH - form.whatsapp.length);

function requestReset() {
  confirmResetOpen.value = true;
}

function cancelReset() {
  confirmResetOpen.value = false;
}

function confirmReset() {
  isResetting.value = true;
  form.delete('/site-information', {
    onSuccess: () => {
      form.reset();
      cancelReset();
      showToast('success', 'Site information has been reset.');
    },
    onError: () => {
      showToast('error', 'Failed to reset site information.');
    },
    onFinish: () => {
      isResetting.value = false;
    },
  });
}
</script>

<template>
  <Head title="Site Information Setup" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-4xl p-4">
      <!-- Toast -->
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" :duration="3500" />

      <div class="mb-4 flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold">Site Information Setup</h1>
          <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            Configure your contact information for the website. Only one record is maintained.
          </p>
        </div>
        <button
          v-if="canResetSiteInfo && siteInformation"
          @click="requestReset"
          class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 transition-all hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50"
        >
          Reset
        </button>
      </div>

      <form @submit.prevent="submit" class="space-y-6 rounded-xl border border-sidebar-border/70 bg-white p-6 shadow-sm dark:border-sidebar-border dark:bg-neutral-950 sm:p-8">
        <!-- Status Badge -->
        <div v-if="siteInformation" class="flex items-center gap-2 rounded-lg bg-green-50 p-3 dark:bg-green-900/20">
          <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          <div class="flex-1">
            <p class="text-sm font-medium text-green-800 dark:text-green-300">Site information is configured</p>
            <p class="text-xs text-green-600 dark:text-green-400">Last updated: {{ new Date(siteInformation.updated_at || '').toLocaleString() }}</p>
          </div>
        </div>
        <div v-else class="flex items-center gap-2 rounded-lg bg-amber-50 p-3 dark:bg-amber-900/20">
          <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <p class="text-sm font-medium text-amber-800 dark:text-amber-300">No site information configured yet</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
          <!-- Email Address -->
          <div class="space-y-2 md:col-span-2">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
                Email Address <span class="text-xs text-neutral-500">(Optional)</span>
              </label>
              <span class="text-xs text-neutral-500" :class="{ 'text-red-500': remainingEmailChars < 0 }">
                {{ remainingEmailChars }} / {{ MAX_EMAIL_LENGTH }}
              </span>
            </div>
            <input 
              v-model="form.email_address" 
              type="email" 
              :maxlength="MAX_EMAIL_LENGTH"
              placeholder="e.g., contact@company.com"
              class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10" 
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.email_address }"
            />
            <div v-if="form.errors.email_address" class="flex items-center gap-1 text-sm text-red-600">
              <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span>{{ form.errors.email_address }}</span>
            </div>
          </div>

          <!-- Telephone Number -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
                Telephone Number <span class="text-xs text-neutral-500">(Optional)</span>
              </label>
              <span class="text-xs text-neutral-500" :class="{ 'text-red-500': remainingTelChars < 0 }">
                {{ remainingTelChars }} / {{ MAX_TEL_LENGTH }}
              </span>
            </div>
            <input 
              v-model="form.tel_no" 
              type="text" 
              :maxlength="MAX_TEL_LENGTH"
              placeholder="e.g., +1-234-567-8900"
              class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10" 
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.tel_no }"
            />
            <div v-if="form.errors.tel_no" class="flex items-center gap-1 text-sm text-red-600">
              <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span>{{ form.errors.tel_no }}</span>
            </div>
          </div>

          <!-- Phone Number -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
                Phone Number <span class="text-xs text-neutral-500">(Optional)</span>
              </label>
              <span class="text-xs text-neutral-500" :class="{ 'text-red-500': remainingPhoneChars < 0 }">
                {{ remainingPhoneChars }} / {{ MAX_PHONE_LENGTH }}
              </span>
            </div>
            <input 
              v-model="form.phone_no" 
              type="text" 
              :maxlength="MAX_PHONE_LENGTH"
              placeholder="e.g., +1-234-567-8900"
              class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10" 
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.phone_no }"
            />
            <div v-if="form.errors.phone_no" class="flex items-center gap-1 text-sm text-red-600">
              <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span>{{ form.errors.phone_no }}</span>
            </div>
          </div>

          <!-- Address (Optional) -->
          <div class="space-y-2 md:col-span-2">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
                Address <span class="text-xs text-neutral-500">(Optional)</span>
              </label>
              <span class="text-xs text-neutral-500" :class="{ 'text-red-500': remainingAddressChars < 0 }">
                {{ remainingAddressChars }} / {{ MAX_ADDRESS_LENGTH }}
              </span>
            </div>
            <input 
              v-model="form.address" 
              type="text" 
              :maxlength="MAX_ADDRESS_LENGTH"
              placeholder="e.g., 123 Main St, City, Country"
              class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10" 
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.address }"
            />
            <div v-if="form.errors.address" class="flex items-center gap-1 text-sm text-red-600">
              <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span>{{ form.errors.address }}</span>
            </div>
          </div>

          <!-- Facebook -->
          <div class="space-y-2 md:col-span-2">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
                Facebook Profile <span class="text-xs text-neutral-500">(Optional)</span>
              </label>
              <span class="text-xs text-neutral-500" :class="{ 'text-red-500': remainingFacebookChars < 0 }">
                {{ remainingFacebookChars }} / {{ MAX_FACEBOOK_LENGTH }}
              </span>
            </div>
            <input 
              v-model="form.facebook" 
              type="text" 
              :maxlength="MAX_FACEBOOK_LENGTH"
              placeholder="e.g., facebook.com/yourpage or yourpage"
              class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10" 
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.facebook }"
            />
            <div v-if="form.errors.facebook" class="flex items-center gap-1 text-sm text-red-600">
              <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span>{{ form.errors.facebook }}</span>
            </div>
          </div>

          <!-- Telegram (Optional) -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
                Telegram <span class="text-xs text-neutral-500">(Optional)</span>
              </label>
              <span class="text-xs text-neutral-500" :class="{ 'text-red-500': remainingTelegramChars < 0 }">
                {{ remainingTelegramChars }} / {{ MAX_TELEGRAM_LENGTH }}
              </span>
            </div>
            <input 
              v-model="form.telegram" 
              type="text" 
              :maxlength="MAX_TELEGRAM_LENGTH"
              placeholder="e.g., @username or t.me/username"
              class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10" 
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.telegram }"
            />
            <div v-if="form.errors.telegram" class="flex items-center gap-1 text-sm text-red-600">
              <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span>{{ form.errors.telegram }}</span>
            </div>
          </div>

          <!-- Viber (Optional) -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
                Viber <span class="text-xs text-neutral-500">(Optional)</span>
              </label>
              <span class="text-xs text-neutral-500" :class="{ 'text-red-500': remainingViberChars < 0 }">
                {{ remainingViberChars }} / {{ MAX_VIBER_LENGTH }}
              </span>
            </div>
            <input 
              v-model="form.viber" 
              type="text" 
              :maxlength="MAX_VIBER_LENGTH"
              placeholder="e.g., +1-234-567-8900"
              class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10" 
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.viber }"
            />
            <div v-if="form.errors.viber" class="flex items-center gap-1 text-sm text-red-600">
              <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span>{{ form.errors.viber }}</span>
            </div>
          </div>

          <!-- WhatsApp (Optional) -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
                WhatsApp <span class="text-xs text-neutral-500">(Optional)</span>
              </label>
              <span class="text-xs text-neutral-500" :class="{ 'text-red-500': remainingWhatsappChars < 0 }">
                {{ remainingWhatsappChars }} / {{ MAX_WHATSAPP_LENGTH }}
              </span>
            </div>
            <input 
              v-model="form.whatsapp" 
              type="text" 
              :maxlength="MAX_WHATSAPP_LENGTH"
              placeholder="e.g., +1-234-567-8900"
              class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10" 
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.whatsapp }"
            />
            <div v-if="form.errors.whatsapp" class="flex items-center gap-1 text-sm text-red-600">
              <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <span>{{ form.errors.whatsapp }}</span>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end border-t border-neutral-200 pt-6 dark:border-neutral-800">
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
            {{ isSubmitting ? 'Saving...' : (siteInformation ? 'Update Information' : 'Save Information') }}
          </button>
        </div>
      </form>
    </div>

    <!-- Reset Confirmation Modal -->
    <Dialog v-model:open="confirmResetOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Reset Site Information</DialogTitle>
          <DialogDescription>
            This action cannot be undone. This will permanently delete the site information and you'll need to set it up again.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <button @click="cancelReset" type="button" class="rounded-md border px-4 py-2 text-sm">Cancel</button>
          <button @click="confirmReset" :disabled="isResetting" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60">
            {{ isResetting ? 'Resetting...' : 'Reset' }}
          </button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
