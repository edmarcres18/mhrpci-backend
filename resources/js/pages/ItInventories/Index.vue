<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog';
import Toast from './Toast.vue';

interface InventoryItem {
  id: number;
  asset_tag: string;
  category: string;
  type?: string | null;
  brand?: string | null;
  model?: string | null;
  serial_number?: string | null;
  status?: string;
  location?: string | null;
  assigned_to?: string | null;
  created_at?: string | null;
}

interface Pagination<T> {
  data: T[];
  links: { url: string | null; label: string; active: boolean }[];
}

const page = usePage();
const inventories = computed(() => (page.props.inventories as Pagination<InventoryItem>) ?? { data: [], links: [] });
const statuses = computed<string[]>(() => (page.props.statuses as string[]) ?? []);
const initialFilters = computed(() => (page.props as any)?.filters ?? { search: '', perPage: 10, status: '' });
const search = ref<string>(initialFilters.value.search || '');
const perPage = ref<number>(Number(initialFilters.value.perPage) || 10);
const status = ref<string>(initialFilters.value.status || '');
let searchTimer: number | undefined;

watch(
  () => search.value,
  (val) => {
    if (searchTimer) window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
      router.get('/it-inventories', { search: val || undefined, perPage: perPage.value, status: status.value || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }, 350);
  }
);

watch(
  () => perPage.value,
  (val) => {
    router.get('/it-inventories', { search: search.value || undefined, perPage: val, status: status.value || undefined }, { preserveState: true, preserveScroll: true, replace: true });
  }
);

watch(
  () => status.value,
  (val) => {
    router.get('/it-inventories', { search: search.value || undefined, perPage: perPage.value, status: val || undefined }, { preserveState: true, preserveScroll: true, replace: true });
  }
);

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Inventories', href: '/it-inventories' },
];

// Delete confirmation modal state
const confirmOpen = ref(false);
const deletingId = ref<number | null>(null);
const deletingLabel = ref<string>('');
const isDeleting = ref(false);

function requestDelete(id: number, label: string) {
  deletingId.value = id;
  deletingLabel.value = label;
  confirmOpen.value = true;
}

function cancelDelete() {
  confirmOpen.value = false;
  deletingId.value = null;
  deletingLabel.value = '';
}

// Toast state
type ToastType = 'success' | 'error';
const toast = reactive<{ show: boolean; type: ToastType; message: string }>({
  show: false,
  type: 'success',
  message: '',
});
let toastTimer: number | undefined;

function showToast(type: ToastType, message: string, duration = 3000) {
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

function confirmDelete() {
  if (!deletingId.value) return;
  isDeleting.value = true;
  router.delete(`/it-inventories/${deletingId.value}` , {
    onSuccess: () => {
      cancelDelete();
    },
    onError: () => {
      showToast('error', 'Failed to delete the item. Please try again.');
    },
    onFinish: () => {
      isDeleting.value = false;
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

function getStatusBadgeColor(st?: string) {
  switch (st) {
    case 'in_use':
      return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-200';
    case 'stock':
      return 'bg-neutral-100 text-neutral-800 dark:bg-neutral-900/30 dark:text-neutral-200';
    case 'repair':
      return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200';
    case 'retired':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200';
    case 'lost':
      return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200';
    default:
      return 'bg-neutral-100 text-neutral-800 dark:bg-neutral-900/30 dark:text-neutral-200';
  }
}
</script>

<template>
  <Head title="IT Inventories" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl p-4">
      <!-- Toast -->
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" :duration="3500" />

      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
          <h1 class="text-xl font-semibold">IT Inventories</h1>
        </div>
        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
          <div class="flex items-center gap-2">
            <label class="text-xs text-neutral-500">Per page</label>
            <select
              v-model.number="perPage"
              class="rounded-md border px-2 py-2 text-sm dark:bg-neutral-900 dark:text-white"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>
          <div class="relative flex-1 sm:w-72">
            <input
              v-model="search"
              type="text"
              placeholder="Search asset tag, brand, model, serial"
              class="w-full rounded-md border px-3 py-2 text-sm dark:bg-neutral-900 dark:text-white"
            />
          </div>
          <div class="flex items-center gap-2">
            <label class="text-xs text-neutral-500">Status</label>
            <select v-model="status" class="rounded-md border px-2 py-2 text-sm dark:bg-neutral-900 dark:text-white">
              <option value="">All</option>
              <option v-for="st in statuses" :key="st" :value="st">{{ st }}</option>
            </select>
          </div>
          <Link href="/it-inventories/create" class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-black/90 dark:bg-white dark:text-black">Add Inventory</Link>
          <Link href="/it-inventories/batch-create" class="rounded-md border px-4 py-2 text-sm font-medium shadow-sm hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800">Batch Add by Person</Link>
        </div>
      </div>

      <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 bg-white shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
        <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-800">
          <thead class="bg-neutral-50 dark:bg-neutral-900">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700 dark:text-neutral-300">Asset Tag</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700 dark:text-neutral-300">Category</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700 dark:text-neutral-300">Brand / Model</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700 dark:text-neutral-300">Status</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700 dark:text-neutral-300">Assigned To</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700 dark:text-neutral-300">Location</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-700 dark:text-neutral-300">Created</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-neutral-700 dark:text-neutral-300">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
            <tr v-for="inv in inventories.data" :key="inv.id" class="hover:bg-neutral-50 dark:hover:bg-neutral-900/40">
              <td class="px-4 py-3 text-sm">{{ inv.asset_tag }}</td>
              <td class="px-4 py-3 text-sm">{{ inv.category }}</td>
              <td class="px-4 py-3 text-sm">{{ inv.brand || '—' }} <span v-if="inv.model">/ {{ inv.model }}</span></td>
              <td class="px-4 py-3 text-sm">
                <span class="inline-flex rounded px-2 py-1 text-xs" :class="getStatusBadgeColor(inv.status)">{{ inv.status }}</span>
              </td>
              <td class="px-4 py-3 text-sm">{{ inv.assigned_to || '—' }}</td>
              <td class="px-4 py-3 text-sm">{{ inv.location || '—' }}</td>
              <td class="px-4 py-3 text-sm">{{ inv.created_at || '—' }}</td>
              <td class="px-4 py-3 text-sm">
                <div class="flex justify-end gap-2">
                  <Link :href="`/it-inventories/${inv.id}`" class="rounded-md border px-3 py-1 text-xs hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800">View</Link>
                  <Link :href="`/it-inventories/${inv.id}/edit`" class="rounded-md border px-3 py-1 text-xs hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800">Edit</Link>
                  <button @click="requestDelete(inv.id, inv.asset_tag)" class="rounded-md border border-red-300 px-3 py-1 text-xs text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/30">Delete</button>
                </div>
              </td>
            </tr>
            <tr v-if="inventories.data.length === 0">
              <td colspan="8" class="px-4 py-8 text-center text-sm text-neutral-500">No inventory items found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Confirm Delete Dialog -->
      <Dialog :open="confirmOpen" @update:open="(v:boolean) => (confirmOpen = v)">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Delete Inventory</DialogTitle>
            <DialogDescription>Are you sure you want to delete <strong>{{ deletingLabel }}</strong>? This action cannot be undone.</DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <button @click="cancelDelete" class="rounded-md border px-3 py-2 text-sm">Cancel</button>
            <button @click="confirmDelete" :disabled="isDeleting" class="rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50">{{ isDeleting ? 'Deleting...' : 'Delete' }}</button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>