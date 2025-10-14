<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, reactive, watch, onBeforeUnmount } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog';
import { Upload, X, Image as ImageIcon, Settings } from 'lucide-vue-next';
import Toast from './Toast.vue';

const MAX_NAME_LENGTH = 255;
const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB

interface SiteSettings {
  id?: number;
  site_name: string;
  site_logo: string | null;
  created_at?: string | null;
  updated_at?: string | null;
}

const page = usePage();
const siteSettings = computed(() => (page.props.siteSettings as SiteSettings) ?? {
  site_name: 'Laravel Starter Kit',
  site_logo: null,
});

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Site Settings', href: '/site-settings' },
];

const form = useForm({
  site_name: '',
  site_logo: null as File | null,
});

const logoPreview = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

// Populate form if data exists
onMounted(() => {
  if (siteSettings.value) {
    form.site_name = siteSettings.value.site_name || 'Laravel Starter Kit';
    if (siteSettings.value.site_logo) {
      logoPreview.value = siteSettings.value.site_logo;
    }
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

function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  
  if (file) {
    // Validate file type
    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/webp'];
    if (!validTypes.includes(file.type)) {
      showToast('error', 'Invalid file type. Please upload an image (JPEG, PNG, GIF, SVG, WEBP).');
      return;
    }

    // Validate file size
    if (file.size > MAX_FILE_SIZE) {
      showToast('error', 'File size exceeds 2MB. Please upload a smaller image.');
      return;
    }

    form.site_logo = file;
    
    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
      logoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
}

function triggerFileInput() {
  fileInputRef.value?.click();
}

function removeLogo() {
  form.site_logo = null;
  logoPreview.value = siteSettings.value.site_logo;
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
}

function removeExistingLogo() {
  confirmRemoveLogoOpen.value = true;
}

function submit() {
  // Client-side validation
  if (!form.site_name.trim()) {
    showToast('error', 'Site name is required.');
    return;
  }

  form.post('/site-settings', {
    forceFormData: true,
    onSuccess: () => {
      showToast('success', 'Site settings updated successfully!');
      // Reset the file input after successful upload
      if (fileInputRef.value) {
        fileInputRef.value.value = '';
      }
      form.site_logo = null;
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0] as string;
      showToast('error', firstError || 'Failed to save site settings. Please check the form.');
    },
  });
}

// Reset confirmation modal state
const confirmResetOpen = ref(false);
const isResetting = ref(false);

// Remove logo confirmation modal state
const confirmRemoveLogoOpen = ref(false);
const isRemovingLogo = ref(false);

const remainingNameChars = computed(() => MAX_NAME_LENGTH - form.site_name.length);

function requestReset() {
  confirmResetOpen.value = true;
}

function cancelReset() {
  confirmResetOpen.value = false;
}

function confirmReset() {
  isResetting.value = true;
  router.post('/site-settings/reset', {}, {
    onSuccess: () => {
      cancelReset();
      showToast('success', 'Site settings have been reset to default values.');
      logoPreview.value = null;
      form.site_logo = null;
      if (fileInputRef.value) {
        fileInputRef.value.value = '';
      }
    },
    onError: () => {
      showToast('error', 'Failed to reset site settings.');
    },
    onFinish: () => {
      isResetting.value = false;
    },
  });
}

function cancelRemoveLogo() {
  confirmRemoveLogoOpen.value = false;
}

function confirmRemoveLogo() {
  isRemovingLogo.value = true;
  router.post('/site-settings/remove-logo', {}, {
    onSuccess: () => {
      cancelRemoveLogo();
      showToast('success', 'Site logo removed successfully.');
      logoPreview.value = null;
      form.site_logo = null;
      if (fileInputRef.value) {
        fileInputRef.value.value = '';
      }
    },
    onError: () => {
      showToast('error', 'Failed to remove site logo.');
    },
    onFinish: () => {
      isRemovingLogo.value = false;
    },
  });
}
</script>

<template>
  <Head title="Site Settings" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-4xl p-4">
      <!-- Toast -->
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" :duration="3500" />

      <div class="mb-4 flex items-center justify-between">
        <div>
          <h1 class="flex items-center gap-2 text-xl font-semibold">
            <Settings class="h-5 w-5" />
            Site Settings
          </h1>
          <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            Configure your site name and logo. Only System Admin can access this page.
          </p>
        </div>
        <button
          v-if="siteSettings.id"
          @click="requestReset"
          class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 transition-all hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50"
        >
          Reset to Default
        </button>
      </div>

      <form @submit.prevent="submit" class="space-y-6 rounded-xl border border-sidebar-border/70 bg-white p-6 shadow-sm dark:border-sidebar-border dark:bg-neutral-950 sm:p-8">
        <!-- Status Badge -->
        <div v-if="siteSettings.id" class="flex items-center gap-2 rounded-lg bg-green-50 p-3 dark:bg-green-900/20">
          <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          <div class="flex-1">
            <p class="text-sm font-medium text-green-800 dark:text-green-300">Site settings are configured</p>
            <p class="text-xs text-green-600 dark:text-green-400">Last updated: {{ new Date(siteSettings.updated_at || '').toLocaleString() }}</p>
          </div>
        </div>
        <div v-else class="flex items-center gap-2 rounded-lg bg-amber-50 p-3 dark:bg-amber-900/20">
          <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Using default site settings</p>
        </div>

        <!-- Site Name -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
              Site Name <span class="text-red-500">*</span>
            </label>
            <span class="text-xs text-neutral-500" :class="{ 'text-red-500': remainingNameChars < 0 }">
              {{ remainingNameChars }} / {{ MAX_NAME_LENGTH }}
            </span>
          </div>
          <input 
            v-model="form.site_name" 
            type="text" 
            :maxlength="MAX_NAME_LENGTH"
            placeholder="e.g., My Awesome Website"
            class="w-full rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm outline-none transition-all focus:border-black focus:ring-2 focus:ring-black/10 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white dark:focus:border-white dark:focus:ring-white/10" 
            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/10': form.errors.site_name }"
          />
          <div v-if="form.errors.site_name" class="flex items-center gap-1 text-sm text-red-600">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>{{ form.errors.site_name }}</span>
          </div>
        </div>

        <!-- Site Logo -->
        <div class="space-y-2">
          <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-200">
            Site Logo <span class="text-xs text-neutral-500">(Optional)</span>
          </label>
          <p class="text-xs text-neutral-500 dark:text-neutral-400">
            Upload an image file (JPEG, PNG, GIF, SVG, WEBP). Maximum size: 2MB
          </p>

          <!-- Logo Preview -->
          <div v-if="logoPreview" class="relative mt-4 inline-block">
            <img 
              :src="logoPreview" 
              alt="Logo Preview" 
              class="h-32 w-32 rounded-lg border-2 border-neutral-200 object-contain dark:border-neutral-700"
            />
            <button
              v-if="form.site_logo"
              @click="removeLogo"
              type="button"
              class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white shadow-lg transition-all hover:bg-red-600"
            >
              <X class="h-4 w-4" />
            </button>
            <button
              v-else-if="siteSettings.site_logo"
              @click="removeExistingLogo"
              type="button"
              class="absolute -right-2 -top-2 rounded-full bg-red-500 p-1 text-white shadow-lg transition-all hover:bg-red-600"
            >
              <X class="h-4 w-4" />
            </button>
          </div>

          <!-- Upload Button -->
          <div class="mt-4">
            <input
              ref="fileInputRef"
              type="file"
              accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml,image/webp"
              @change="handleFileSelect"
              class="hidden"
            />
            <button
              @click="triggerFileInput"
              type="button"
              class="flex items-center gap-2 rounded-lg border-2 border-dashed border-neutral-300 bg-neutral-50 px-6 py-4 text-sm font-medium text-neutral-700 transition-all hover:border-neutral-400 hover:bg-neutral-100 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:border-neutral-600 dark:hover:bg-neutral-800"
            >
              <Upload class="h-5 w-5" />
              {{ logoPreview ? 'Change Logo' : 'Upload Logo' }}
            </button>
          </div>

          <div v-if="form.errors.site_logo" class="flex items-center gap-1 text-sm text-red-600">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>{{ form.errors.site_logo }}</span>
          </div>
        </div>

        <!-- Preview Section -->
        <div class="space-y-3 rounded-lg border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900">
          <p class="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
            Preview
          </p>
          <div class="flex items-center gap-3">
            <div v-if="logoPreview" class="flex h-10 w-10 items-center justify-center rounded-md bg-white dark:bg-neutral-800">
              <img :src="logoPreview" alt="Logo" class="h-8 w-8 object-contain" />
            </div>
            <div v-else class="flex h-10 w-10 items-center justify-center rounded-md bg-neutral-200 dark:bg-neutral-800">
              <ImageIcon class="h-5 w-5 text-neutral-500" />
            </div>
            <span class="text-sm font-semibold">{{ form.site_name || 'Site Name' }}</span>
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
            {{ isSubmitting ? 'Saving...' : 'Save Settings' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Reset Confirmation Modal -->
    <Dialog v-model:open="confirmResetOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Reset Site Settings</DialogTitle>
          <DialogDescription>
            This will reset the site name to "Laravel Starter Kit" and remove the logo. This action cannot be undone.
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

    <!-- Remove Logo Confirmation Modal -->
    <Dialog v-model:open="confirmRemoveLogoOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Remove Site Logo</DialogTitle>
          <DialogDescription>
            Are you sure you want to remove the current site logo? This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <button @click="cancelRemoveLogo" type="button" class="rounded-md border px-4 py-2 text-sm">Cancel</button>
          <button @click="confirmRemoveLogo" :disabled="isRemovingLogo" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60">
            {{ isRemovingLogo ? 'Removing...' : 'Remove' }}
          </button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
