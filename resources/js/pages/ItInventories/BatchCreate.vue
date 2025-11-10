<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, onMounted, onBeforeUnmount } from 'vue';
import Toast from './Toast.vue';

const props = defineProps<{ statuses: string[]; categories: string[] }>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Inventories', href: '/it-inventories' },
  { title: 'Batch Create', href: '/it-inventories/batch-create' },
];

type AssetRow = {
  asset_tag: string;
  category: string;
  type: string;
  brand: string;
  model: string;
  serial_number: string;
  status: string;
  location: string;
  purchase_date: string;
  purchase_cost: string | number;
  supplier: string;
  warranty_expires_at: string;
  notes: string;
  color?: string;
};

function makeDefaultRow(): AssetRow {
  return {
    asset_tag: '',
    category: '',
    type: '',
    brand: '',
    model: '',
    serial_number: '',
    status: 'stock',
    location: '',
    purchase_date: '',
    purchase_cost: '',
    supplier: '',
    warranty_expires_at: '',
    notes: '',
    color: '',
  };
}

const form = useForm({
  assigned_to: '',
  assets: [makeDefaultRow()],
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
onBeforeUnmount(() => {
  if (toastTimer) window.clearTimeout(toastTimer);
});

const page = usePage();
const isSubmitting = computed(() => form.processing);

function addRow() {
  form.assets.push(makeDefaultRow());
}

function removeRow(index: number) {
  if (form.assets.length <= 1) return;
  form.assets.splice(index, 1);
}

function submit() {
  if (!form.assigned_to.trim()) {
    showToast('error', 'Accountable person name is required.');
    return;
  }
  if (form.assets.length === 0) {
    showToast('error', 'Please add at least one asset.');
    return;
  }
  const invalidIndex = form.assets.findIndex((row) => !row.asset_tag.trim() || !row.category.trim() || !row.status.trim());
  if (invalidIndex >= 0) {
    showToast('error', `Row ${invalidIndex + 1}: asset tag, category, and status are required.`);
    return;
  }

  form.post('/it-inventories/batch-store', {
    preserveScroll: true,
    onSuccess: () => {
      showToast('success', `Created ${form.assets.length} item(s) for ${form.assigned_to}.`);
      form.reset();
      form.assets = [makeDefaultRow()];
    },
    onError: () => {
      showToast('error', 'Failed to create items. Please check the inputs.');
    },
  });
}

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
</script>

<template>
  <Head title="Batch Create Inventories" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-5xl p-4">
      <!-- Toast -->
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" :duration="3500" />

      <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Batch Create by Accountable Person</h1>
        <Link href="/it-inventories" class="text-sm text-primary hover:underline">Back to list</Link>
      </div>

      <form @submit.prevent="submit" class="space-y-6 rounded-xl border border-sidebar-border/70 bg-white p-6 shadow-sm dark:border-sidebar-border dark:bg-neutral-950 sm:p-8">
        <!-- Accountable Person -->
        <div class="space-y-2">
          <label class="block text-sm font-semibold">Accountable Person <span class="text-red-500">*</span></label>
          <input v-model="form.assigned_to" type="text" placeholder="e.g., Jane Smith" class="w-full rounded-lg border px-4 py-3 text-sm dark:bg-neutral-900 dark:text-white" :class="{ 'border-red-500': form.errors.assigned_to }" />
          <div v-if="form.errors.assigned_to" class="text-sm text-red-600">{{ form.errors.assigned_to }}</div>
        </div>

        <!-- Assets Table -->
        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
          <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-800">
            <thead class="bg-neutral-50 text-xs dark:bg-neutral-900">
              <tr>
                <th class="px-3 py-2 text-left">Asset Tag*</th>
                <th class="px-3 py-2 text-left">Category*</th>
                <th class="px-3 py-2 text-left">Brand</th>
                <th class="px-3 py-2 text-left">Model</th>
                <th class="px-3 py-2 text-left">Type</th>
                <th class="px-3 py-2 text-left">Serial</th>
                <th class="px-3 py-2 text-left">Status*</th>
                <th class="px-3 py-2 text-left">Location</th>
                <th class="px-3 py-2 text-left">Color</th>
                <th class="px-3 py-2 text-left">Notes</th>
                <th class="px-3 py-2 text-left">Purchase Date</th>
                <th class="px-3 py-2 text-left">Cost</th>
                <th class="px-3 py-2 text-left">Supplier</th>
                <th class="px-3 py-2 text-left">Warranty</th>
                <th class="px-3 py-2 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 text-xs dark:divide-neutral-800">
              <tr v-for="(row, i) in form.assets" :key="i" class="align-top">
                <td class="px-3 py-2">
                  <input v-model="row.asset_tag" type="text" class="w-40 rounded border px-2 py-1 dark:bg-neutral-900 dark:text-white" :class="{ 'border-red-500': form.errors[`assets.${i}.asset_tag`] }" />
                  <div v-if="form.errors[`assets.${i}.asset_tag`]" class="mt-1 text-red-600">{{ form.errors[`assets.${i}.asset_tag`] }}</div>
                </td>
                <td class="px-3 py-2">
                  <select v-model="row.category" class="w-36 rounded border px-2 py-1 dark:bg-neutral-900 dark:text-white" :class="{ 'border-red-500': form.errors[`assets.${i}.category`] }">
                    <option value="">Select</option>
                    <option v-for="c in props.categories" :key="c" :value="c">{{ c }}</option>
                  </select>
                  <div v-if="form.errors[`assets.${i}.category`]" class="mt-1 text-red-600">{{ form.errors[`assets.${i}.category`] }}</div>
                </td>
                <td class="px-3 py-2"><input v-model="row.brand" type="text" class="w-32 rounded border px-2 py-1 dark:bg-neutral-900 dark:text-white" /></td>
                <td class="px-3 py-2"><input v-model="row.model" type="text" class="w-32 rounded border px-2 py-1 dark:bg-neutral-900 dark:text-white" /></td>
                <td class="px-3 py-2"><input v-model="row.type" type="text" class="w-32 rounded border px-2 py-1 dark:bg-neutral-900 dark:text-white" /></td>
                <td class="px-3 py-2"><input v-model="row.serial_number" type="text" class="w-36 rounded border px-2 py-1 dark:bg-neutral-900 dark:text-white" :class="{ 'border-red-500': form.errors[`assets.${i}.serial_number`] }" /></td>
                <td class="px-3 py-2">
                  <select v-model="row.status" class="w-28 rounded border px-2 py-1 dark:bg-neutral-900 dark:text-white" :class="{ 'border-red-500': form.errors[`assets.${i}.status`] }">
                    <option value="">Select</option>
                    <option v-for="s in props.statuses" :key="s" :value="s">{{ s }}</option>
                  </select>
                  <div v-if="form.errors[`assets.${i}.status`]" class="mt-1 text-red-600">{{ form.errors[`assets.${i}.status`] }}</div>
                </td>
                <td class="px-3 py-2"><input v-model="row.location" type="text" class="w-32 rounded border px-2 py-1 dark:bg-neutral-900 dark:text-white" /></td>
                <td class="px-3 py-2"><input v-model="row.color" type="text" placeholder="e.g., Black" class="w-28 rounded border px-2 py-1 dark:bg-neutral-900 dark:text-white" /></td>
                <td class="px-3 py-2"><input v-model="row.notes" type="text" class="w-40 rounded border px-2 py-1 dark:bg-neutral-900 dark:text-white" /></td>
                <td class="px-3 py-2"><input v-model="row.purchase_date" type="date" class="w-32 rounded border px-2 py-1 dark:bg-neutral-900 dark:text-white" /></td>
                <td class="px-3 py-2"><input v-model="row.purchase_cost" type="number" step="0.01" class="w-24 rounded border px-2 py-1 dark:bg-neutral-900 dark:text-white" /></td>
                <td class="px-3 py-2"><input v-model="row.supplier" type="text" class="w-32 rounded border px-2 py-1 dark:bg-neutral-900 dark:text-white" /></td>
                <td class="px-3 py-2"><input v-model="row.warranty_expires_at" type="date" class="w-32 rounded border px-2 py-1 dark:bg-neutral-900 dark:text-white" /></td>
                <td class="px-3 py-2 text-right">
                  <button type="button" @click="removeRow(i)" class="rounded-md border px-2 py-1 text-xs hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800" :disabled="form.assets.length <= 1">Remove</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between">
          <button type="button" @click="addRow" class="rounded-md border px-3 py-2 text-sm hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800">Add Asset Row</button>
          <div class="flex gap-3">
            <Link href="/it-inventories" class="rounded-md border px-4 py-2 text-sm">Cancel</Link>
            <button type="submit" :disabled="isSubmitting" class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-black/90 disabled:opacity-50 dark:bg-white dark:text-black">
              {{ isSubmitting ? 'Creating...' : 'Create Items' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>