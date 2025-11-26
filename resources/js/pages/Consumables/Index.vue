<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import Toast from '@/pages/SiteSettings/Toast.vue';
import { FileSpreadsheet, FileText, Plus, PencilLine, Trash2, ClipboardEdit } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter, DialogClose, DialogTrigger } from '@/components/ui/dialog';

type ToastType = 'success' | 'error';

interface Consumable {
  id: number;
  consumable_name: string;
  consumable_description?: string | null;
  consumable_brand?: string | null;
  current_quantity: number;
  threshold_limit: number;
  unit: string;
  stock_status: 'healthy' | 'low' | 'critical';
}

interface PaginationMeta { current_page: number; last_page: number; per_page: number; total: number }
type PageItem = { type: 'page'; value: number } | { type: 'ellipsis' };

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Consumables', href: '/consumables' },
];

const items = ref<Consumable[]>([]);
const search = ref('');
const brand = ref('');
const stock = ref('');
const perPage = ref<number>(10);
const page = ref<number>(1);
const pagination = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const pages = computed<PageItem[]>(() => {
  const last = pagination.value.last_page;
  const current = page.value;
  if (last <= 7) return Array.from({ length: last }, (_, i) => ({ type: 'page', value: i + 1 }));
  const result: PageItem[] = [ { type: 'page', value: 1 }, { type: 'page', value: 2 } ];
  const start = Math.max(current - 1, 3);
  const end = Math.min(current + 1, last - 2);
  if (start > 3) result.push({ type: 'ellipsis' });
  for (let i = start; i <= end; i++) result.push({ type: 'page', value: i });
  if (end < last - 2) result.push({ type: 'ellipsis' });
  result.push({ type: 'page', value: last - 1 }, { type: 'page', value: last });
  return result;
});
const isLoading = ref(false);

const toast = reactive<{ show: boolean; type: ToastType; message: string }>({ show: false, type: 'success', message: '' });

const usageTarget = ref<Consumable | null>(null);
const isUsageOpen = ref(false);
const usageForm = reactive({ quantity_used: 1, purpose: '', used_by: '', date_used: new Date().toISOString().slice(0,10), notes: '' });
const usageErrors = ref<string>('');

async function fetchItems() {
  try {
    isLoading.value = true;
    const res = await axios.get('/api/consumables', { params: { search: search.value || undefined, brand: brand.value || undefined, stock: stock.value || undefined, perPage: perPage.value, page: page.value } });
    if (res.data?.success) {
      items.value = res.data.data;
      const p = res.data.pagination;
      pagination.value = { current_page: Number(p.current_page), last_page: Number(p.last_page), per_page: Number(p.per_page), total: Number(p.total) };
      page.value = pagination.value.current_page;
    }
  } catch (e: any) {
    toast.type = 'error'; toast.message = e?.response?.data?.message || 'Failed to load consumables'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
  } finally { isLoading.value = false; }
}

function goToPage(p: number) { if (p < 1 || p > pagination.value.last_page || p === page.value) return; page.value = p; fetchItems(); }
function prevPage() { if (page.value > 1) { page.value -= 1; fetchItems(); } }
function nextPage() { if (page.value < pagination.value.last_page) { page.value += 1; fetchItems(); } }

function onPerPageChange() { page.value = 1; fetchItems(); }
let searchTimer: number | null = null;
function onSearchInput() { if (searchTimer) window.clearTimeout(searchTimer); searchTimer = window.setTimeout(() => { page.value = 1; fetchItems(); searchTimer = null; }, 300); }

function exportExcel() { window.location.href = '/api/consumables/export-excel'; }
function exportPdf() { window.location.href = '/api/consumables/export-pdf'; }

async function deleteItem(id: number) {
  const ok = window.confirm('Delete this consumable?');
  if (!ok) return;
  try {
    const res = await axios.delete(`/api/consumables/${id}`);
    if (res.data?.success) { toast.type = 'success'; toast.message = 'Deleted'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000); fetchItems(); }
  } catch (e: any) {
    toast.type = 'error'; toast.message = e?.response?.data?.errors?.[0] || 'Delete failed'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
  }
}

async function submitUsage() {
  if (!usageTarget.value) return;
  usageErrors.value = '';
  try {
    const res = await axios.post(`/api/consumables/${usageTarget.value.id}/usage`, usageForm);
    if (res.data?.success) {
      toast.type = 'success'; toast.message = res.data.low_stock ? 'Usage logged • Low stock' : 'Usage logged'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
      isUsageOpen.value = false;
      usageTarget.value = null;
      usageForm.quantity_used = 1; usageForm.purpose = ''; usageForm.used_by = ''; usageForm.date_used = new Date().toISOString().slice(0,10); usageForm.notes = '';
      fetchItems();
    }
  } catch (e: any) {
    usageErrors.value = e?.response?.data?.errors?.[0] || 'Failed to log usage';
  }
}

onMounted(fetchItems);

function statusClass(s: Consumable['stock_status']) {
  if (s === 'critical') return 'text-red-600';
  if (s === 'low') return 'text-amber-600';
  return 'text-green-600';
}
</script>

<template>
  <Head title="IT Consumables" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl p-4 space-y-6">
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" />

      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-1">
          <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">IT Consumables</h1>
          <p class="text-sm text-muted-foreground">Manage consumables, track usage, and monitor stock levels.</p>
        </div>
                <div class="flex items-center gap-2">
                  <Link href="/consumables/create"><Button variant="secondary"><Plus class="size-4" /> Add Consumable</Button></Link>
                  <Button variant="secondary" @click="exportExcel"><FileSpreadsheet class="size-4" /> Excel</Button>
                  <Button variant="secondary" @click="exportPdf"><FileText class="size-4" /> PDF</Button>
                  <Link href="/consumables/usage-history"><Button variant="secondary">Usage History</Button></Link>
                  <Link href="/consumables/logs"><Button variant="secondary">Logs</Button></Link>
                  <Link href="/consumables/trash"><Button variant="secondary">Recycle Bin</Button></Link>
                </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
          <CardDescription>Search and filter consumables</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-3 sm:grid-cols-3">
            <div class="relative">
              <Input v-model="search" placeholder="Search by name, brand..." @input="onSearchInput" />
              <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400">⌕</span>
            </div>
            <div>
              <Input v-model="brand" placeholder="Brand" @change="fetchItems" />
            </div>
            <div>
              <select v-model="stock" class="w-full rounded-md border px-3 py-2 text-sm dark:bg-neutral-900 dark:text-white" @change="fetchItems">
                <option value="">All stock</option>
                <option value="healthy">Healthy</option>
                <option value="low">Low</option>
                <option value="critical">Critical</option>
              </select>
            </div>
          </div>
        </CardContent>
      </Card>

      <div class="rounded-xl border overflow-hidden">
        <div class="relative w-full overflow-auto">
          <table class="w-full caption-bottom text-sm">
            <thead class="bg-neutral-50 text-left text-xs uppercase text-neutral-500 dark:bg-neutral-900/60 dark:text-neutral-400">
              <tr>
                <th class="px-4 py-3 font-medium">Name</th>
                <th class="px-4 py-3 font-medium">Brand</th>
                <th class="px-4 py-3 font-medium">Quantity</th>
                <th class="px-4 py-3 font-medium">Threshold</th>
                <th class="px-4 py-3 font-medium">Unit</th>
                <th class="px-4 py-3 font-medium">Status</th>
                <th class="px-4 py-3 text-right font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in items" :key="c.id" class="border-t border-sidebar-border/70 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-900/60">
                <td class="px-4 py-3">
                  <div class="font-medium">{{ c.consumable_name }}</div>
                  <div class="text-xs text-neutral-500">{{ c.consumable_description }}</div>
                </td>
                <td class="px-4 py-3">{{ c.consumable_brand || '—' }}</td>
                <td class="px-4 py-3">{{ c.current_quantity }}</td>
                <td class="px-4 py-3">{{ c.threshold_limit }}</td>
                <td class="px-4 py-3">{{ c.unit }}</td>
                <td class="px-4 py-3"><span :class="statusClass(c.stock_status)" class="font-semibold capitalize">{{ c.stock_status }}</span></td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-2">
                    <Link :href="`/consumables/${c.id}/edit`"><Button variant="ghost" size="icon" class="h-8 w-8"><PencilLine class="size-4" /></Button></Link>
                    <Dialog v-model:open="isUsageOpen">
                      <DialogTrigger as-child>
                        <Button variant="ghost" size="icon" class="h-8 w-8" @click="usageTarget = c; isUsageOpen = true"><ClipboardEdit class="size-4" /></Button>
                      </DialogTrigger>
                      <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                          <DialogTitle>Log Usage</DialogTitle>
                          <DialogDescription>Deduct stock and record usage</DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-3">
                          <div>
                            <label class="text-xs text-neutral-500">Consumable</label>
                            <div class="text-sm font-semibold">{{ usageTarget?.consumable_name }}</div>
                          </div>
                          <div class="grid grid-cols-2 gap-3">
                            <div>
                              <label class="text-xs text-neutral-500">Quantity Used</label>
                              <Input type="number" v-model.number="usageForm.quantity_used" min="1" />
                            </div>
                            <div>
                              <label class="text-xs text-neutral-500">Date Used</label>
                              <Input type="date" v-model="usageForm.date_used" />
                            </div>
                          </div>
                          <div>
                            <label class="text-xs text-neutral-500">Purpose</label>
                            <Input v-model="usageForm.purpose" placeholder="Purpose" />
                          </div>
                          <div>
                            <label class="text-xs text-neutral-500">Used By</label>
                            <Input v-model="usageForm.used_by" placeholder="Name" />
                          </div>
                          <div>
                            <label class="text-xs text-neutral-500">Notes</label>
                            <Input v-model="usageForm.notes" placeholder="Optional" />
                          </div>
                          <div v-if="usageErrors" class="text-sm text-red-600">{{ usageErrors }}</div>
                        </div>
                        <DialogFooter class="gap-2">
                          <DialogClose as-child>
                            <Button variant="outline">Cancel</Button>
                          </DialogClose>
                          <Button @click="submitUsage">Log Usage</Button>
                        </DialogFooter>
                      </DialogContent>
                    </Dialog>
                    <Button variant="destructive" size="icon" class="h-8 w-8" @click="deleteItem(c.id)"><Trash2 class="size-4" /></Button>
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
          <div class="flex items-center gap-1">
            <button v-for="p in pages" :key="JSON.stringify(p)" class="rounded-md px-3 py-1.5 text-sm" :class="p.type === 'page' && p.value === page ? 'bg-black text-white dark:bg-white dark:text-black' : 'hover:bg-neutral-100 dark:hover:bg-neutral-800'" @click="p.type === 'page' && goToPage(p.value)">
              <span v-if="p.type === 'ellipsis'">…</span>
              <span v-else>{{ p.value }}</span>
            </button>
          </div>
          <Button variant="outline" size="sm" :disabled="page >= pagination.last_page" @click="nextPage">Next</Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
