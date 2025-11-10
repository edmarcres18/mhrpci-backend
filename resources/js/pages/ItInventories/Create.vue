<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, reactive, watch, onBeforeUnmount } from 'vue';
import Toast from './Toast.vue';

interface Option { value: string; label: string }

const props = defineProps<{ statuses: string[]; categories: string[] }>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Inventories', href: '/it-inventories' },
  { title: 'Create', href: '/it-inventories/create' },
];

const form = useForm({
  asset_tag: '',
  category: '',
  type: '',
  brand: '',
  model: '',
  serial_number: '',
  status: 'stock',
  location: '',
  assigned_to: '',
  purchase_date: '',
  purchase_cost: '',
  supplier: '',
  warranty_expires_at: '',
  notes: '',
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
  if (!form.asset_tag.trim()) {
    showToast('error', 'Asset tag is required.');
    return;
  }
  if (!form.category.trim()) {
    showToast('error', 'Category is required.');
    return;
  }
  if (!form.status) {
    showToast('error', 'Status is required.');
    return;
  }

  form.post('/it-inventories', {
    onSuccess: () => {
      form.reset();
      showToast('success', 'Inventory created successfully!');
    },
    onError: () => {
      showToast('error', 'Failed to create inventory. Please check the form.');
    },
  });
}
</script>

<template>
  <Head title="Create Inventory" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-4xl p-4">
      <!-- Toast -->
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" :duration="3500" />

      <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Create Inventory</h1>
        <Link href="/it-inventories" class="text-sm text-primary hover:underline">Back to list</Link>
      </div>

      <form @submit.prevent="submit" class="space-y-6 rounded-xl border border-sidebar-border/70 bg-white p-6 shadow-sm dark:border-sidebar-border dark:bg-neutral-950 sm:p-8">
        <!-- Asset Tag & Category -->
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <label class="block text-sm font-semibold">Asset Tag <span class="text-red-500">*</span></label>
            <input v-model="form.asset_tag" type="text" placeholder="e.g., LAP-2025-001" class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white" :class="{ 'border-red-500': form.errors.asset_tag }" />
            <div v-if="form.errors.asset_tag" class="text-sm text-red-600">{{ form.errors.asset_tag }}</div>
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-semibold">Category <span class="text-red-500">*</span></label>
            <select v-model="form.category" class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white" :class="{ 'border-red-500': form.errors.category }">
              <option value="">Select a category</option>
              <option v-for="c in props.categories" :key="c" :value="c">{{ c }}</option>
            </select>
            <div v-if="form.errors.category" class="text-sm text-red-600">{{ form.errors.category }}</div>
          </div>
        </div>

        <!-- Brand, Model, Type -->
        <div class="grid gap-4 sm:grid-cols-3">
          <div class="space-y-2">
            <label class="block text-sm font-semibold">Brand</label>
            <input v-model="form.brand" type="text" placeholder="e.g., Lenovo" class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white" />
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-semibold">Model</label>
            <input v-model="form.model" type="text" placeholder="e.g., ThinkPad T14" class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white" />
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-semibold">Type</label>
            <input v-model="form.type" type="text" placeholder="e.g., Ultrabook" class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white" />
          </div>
        </div>

        <!-- Serial & Status & Location -->
        <div class="grid gap-4 sm:grid-cols-3">
          <div class="space-y-2">
            <label class="block text-sm font-semibold">Serial Number</label>
            <input v-model="form.serial_number" type="text" placeholder="e.g., S123456789" class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white" :class="{ 'border-red-500': form.errors.serial_number }" />
            <div v-if="form.errors.serial_number" class="text-sm text-red-600">{{ form.errors.serial_number }}</div>
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-semibold">Status <span class="text-red-500">*</span></label>
            <select v-model="form.status" class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white" :class="{ 'border-red-500': form.errors.status }">
              <option value="">Select a status</option>
              <option v-for="s in props.statuses" :key="s" :value="s">{{ s }}</option>
            </select>
            <div v-if="form.errors.status" class="text-sm text-red-600">{{ form.errors.status }}</div>
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-semibold">Location</label>
            <input v-model="form.location" type="text" placeholder="e.g., HQ - Room 204" class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white" />
          </div>
        </div>

        <!-- Assignment -->
        <div class="space-y-2">
          <label class="block text-sm font-semibold">Assigned To</label>
          <input v-model="form.assigned_to" type="text" placeholder="e.g., John Doe or HR Dept" class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white" :class="{ 'border-red-500': form.errors.assigned_to }" />
          <div v-if="form.errors.assigned_to" class="text-sm text-red-600">{{ form.errors.assigned_to }}</div>
        </div>

        <!-- Procurement -->
        <div class="grid gap-4 sm:grid-cols-3">
          <div class="space-y-2">
            <label class="block text-sm font-semibold">Purchase Date</label>
            <input v-model="form.purchase_date" type="date" class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white" />
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-semibold">Purchase Cost</label>
            <input v-model="form.purchase_cost" type="number" step="0.01" placeholder="e.g., 1250.00" class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white" />
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-semibold">Supplier</label>
            <input v-model="form.supplier" type="text" placeholder="e.g., Tech Supplier Inc." class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white" />
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <label class="block text-sm font-semibold">Warranty Expires</label>
            <input v-model="form.warranty_expires_at" type="date" class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white" />
          </div>
          <div class="space-y-2">
            <label class="block text-sm font-semibold">Notes</label>
            <textarea v-model="form.notes" rows="4" placeholder="Additional notes..." class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white"></textarea>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-6 dark:border-neutral-800 sm:flex-row sm:justify-between sm:items-center">
          <Link href="/it-inventories" class="rounded-md border px-4 py-2 text-sm">Cancel</Link>
          <button type="submit" :disabled="isSubmitting" class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-black/90 disabled:opacity-50 dark:bg-white dark:text-black">
            {{ isSubmitting ? 'Creating...' : 'Create Inventory' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>