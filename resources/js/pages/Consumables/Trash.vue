<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import Toast from '@/pages/SiteSettings/Toast.vue';
import { ArrowLeft, RotateCcw, Trash2 } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter, DialogClose, DialogTrigger } from '@/components/ui/dialog';

type ToastType = 'success' | 'error';

interface Item {
  id: number;
  consumable_id: number;
  consumable_name: string;
  consumable_brand?: string | null;
  unit: string;
  current_quantity: number;
  threshold_limit: number;
  deleted_at?: string | null;
  deleted_by?: { id: number; name: string } | null;
  restore_status?: boolean;
}

interface PaginationMeta { current_page: number; last_page: number; per_page: number; total: number }

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Consumables', href: '/consumables' },
  { title: 'Recycle Bin', href: '/consumables/trash' },
];

const items = ref<Item[]>([]);
const perPage = ref<number>(10);
const page = ref<number>(1);
const pagination = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const filters = reactive({ search: '' });
const toast = reactive<{ show: boolean; type: ToastType; message: string }>({ show: false, type: 'success', message: '' });

const inertiaPage = usePage();
const canForceDelete = computed(() => (inertiaPage.props.auth as any)?.isSystemAdmin ?? false);

async function fetchItems() {
  const res = await axios.get('/api/consumables/trashed', { params: { ...filters, perPage: perPage.value, page: page.value } });
  if (res.data?.success) {
    items.value = res.data.data;
    const p = res.data.pagination;
    pagination.value = { current_page: Number(p.current_page), last_page: Number(p.last_page), per_page: Number(p.per_page), total: Number(p.total) };
    page.value = pagination.value.current_page;
  }
}

function onPerPageChange() { page.value = 1; fetchItems(); }
function prevPage() { if (page.value > 1) { page.value -= 1; fetchItems(); } }
function nextPage() { if (page.value < pagination.value.last_page) { page.value += 1; fetchItems(); } }

async function restore(id: number) {
  try {
    const res = await axios.post(`/api/consumables/${id}/restore`);
    if (res.data?.success) {
      toast.type = 'success'; toast.message = 'Restored'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
      fetchItems();
    }
  } catch {}
}

async function forceDelete(id: number) {
  try {
    await axios.delete(`/api/consumables/${id}/force`);
    toast.type = 'success'; toast.message = 'Deleted permanently'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
    fetchItems();
  } catch {}
}

onMounted(fetchItems);
</script>

<template>
  <Head title="Recycle Bin" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl p-4 space-y-6">
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" />

      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-1">
          <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">Recycle Bin</h1>
          <p class="text-sm text-muted-foreground">Soft-deleted consumables. Restore or delete permanently.</p>
        </div>
        <div class="flex items-center gap-2">
          <Link href="/consumables"><Button variant="secondary"><ArrowLeft class="size-4" /> Back</Button></Link>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
          <CardDescription>Find items in trash</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-3 sm:grid-cols-3">
            <Input v-model="filters.search" placeholder="Search by name/brand" @change="fetchItems" />
          </div>
        </CardContent>
      </Card>

      <div class="rounded-xl border overflow-hidden">
        <div class="relative w-full overflow-auto">
          <table class="w-full caption-bottom text-sm">
            <thead class="bg-neutral-50 text-left text-xs uppercase text-neutral-500 dark:bg-neutral-900/60 dark:text-neutral-400">
              <tr>
                <th class="px-4 py-3 font-medium">Deleted At</th>
                <th class="px-4 py-3 font-medium">Name</th>
                <th class="px-4 py-3 font-medium">Brand</th>
                <th class="px-4 py-3 font-medium">Quantity</th>
                <th class="px-4 py-3 font-medium">Unit</th>
                <th class="px-4 py-3 font-medium">Deleted By</th>
                <th class="px-4 py-3 font-medium"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in items" :key="c.id" class="border-t border-sidebar-border/70 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-900/60">
                <td class="px-4 py-3">{{ c.deleted_at }}</td>
                <td class="px-4 py-3">{{ c.consumable_name }}</td>
                <td class="px-4 py-3">{{ c.consumable_brand }}</td>
                <td class="px-4 py-3">{{ c.current_quantity }}</td>
                <td class="px-4 py-3">{{ c.unit }}</td>
                <td class="px-4 py-3">{{ c.deleted_by?.name || '—' }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-2">
                    <Button variant="secondary" size="sm" @click="restore(c.consumable_id)"><RotateCcw class="size-4" /> Restore</Button>
                    <Dialog v-if="canForceDelete">
                      <DialogTrigger as-child>
                        <Button variant="destructive" size="sm"><Trash2 class="size-4" /> Delete Permanently</Button>
                      </DialogTrigger>
                      <DialogContent>
                        <DialogHeader>
                          <DialogTitle>Delete Permanently?</DialogTitle>
                          <DialogDescription>This will permanently remove the consumable. Usage history and logs will be preserved.</DialogDescription>
                        </DialogHeader>
                        <DialogFooter class="gap-2">
                          <DialogClose as-child>
                            <Button variant="outline">Cancel</Button>
                          </DialogClose>
                          <Button variant="destructive" @click="forceDelete(c.consumable_id)">Confirm Delete</Button>
                        </DialogFooter>
                      </DialogContent>
                    </Dialog>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
        <div class="flex items-center gap-2">
          <label class="text-xs text-neutral-500">Per page</label>
          <select v-model.number="perPage" class="rounded-md border px-2 py-2 text-sm dark:bg-neutral-900 dark:text-white" @change="onPerPageChange">
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
        <div class="flex items-center gap-2">
          <Button variant="outline" size="sm" :disabled="page <= 1" @click="prevPage">Prev</Button>
          <div class="text-sm text-muted-foreground">Page {{ pagination.current_page }} of {{ pagination.last_page }}</div>
          <Button variant="outline" size="sm" :disabled="page >= pagination.last_page" @click="nextPage">Next</Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
