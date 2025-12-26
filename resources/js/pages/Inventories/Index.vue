<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import Toast from '@/pages/SiteSettings/Toast.vue';
import { FileSpreadsheet, FileText, Search, RefreshCw, Eye, Trash2, CheckCircle, Printer } from 'lucide-vue-next';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Link } from '@inertiajs/vue3';

type ToastType = 'success' | 'error';

interface InventoryItem {
  id?: number;
  inventory_accountable: string;
  inventory_name: string;
  inventory_specification?: string | null;
  inventory_brand?: string | null;
  inventory_status: string;
}

interface InventoryGroup {
  inventory_accountable: string;
  items: InventoryItem[];
  total?: number;
}

interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
type PageItem = { type: 'page'; value: number } | { type: 'ellipsis' };

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Inventories', href: '/inventories' },
];

const groups = ref<InventoryGroup[]>([]);
const search = ref('');
const statusFilter = ref('');
const perPage = ref<number>(10);
const page = ref<number>(1);
const pagination = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const totalItems = computed(() => groups.value.reduce((sum, g) => sum + (g.total ?? g.items.length), 0));
const pages = computed<PageItem[]>(() => {
  const last = pagination.value.last_page;
  const current = page.value;
  if (last <= 7) return Array.from({ length: last }, (_, i) => ({ type: 'page', value: i + 1 }));
  const result: PageItem[] = [
    { type: 'page', value: 1 },
    { type: 'page', value: 2 },
  ];
  const start = Math.max(current - 1, 3);
  const end = Math.min(current + 1, last - 2);
  if (start > 3) result.push({ type: 'ellipsis' });
  for (let i = start; i <= end; i++) result.push({ type: 'page', value: i });
  if (end < last - 2) result.push({ type: 'ellipsis' });
  result.push({ type: 'page', value: last - 1 }, { type: 'page', value: last });
  return result;
});
const isLoading = ref(false);
const showingRange = computed(() => {
  const total = pagination.value.total;
  if (!total) return 'Showing 0 of 0';
  const start = (pagination.value.current_page - 1) * pagination.value.per_page + 1;
  const end = Math.min(pagination.value.current_page * pagination.value.per_page, total);
  return `Showing ${start}–${end} of ${total}`;
});
const newAccountable = ref('');
function goCreateAccountable() {
  const name = newAccountable.value.trim();
  if (!name) return;
  window.location.href = `/inventories/${encodeURIComponent(name)}`;
}

const toast = reactive<{ show: boolean; type: ToastType; message: string }>({
  show: false,
  type: 'success',
  message: '',
});


async function fetchGroups() {
  try {
    isLoading.value = true;
    const res = await axios.get('/api/inventories', {
      params: {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        perPage: perPage.value,
        page: page.value,
      },
    });
    if (res.data?.success) {
      groups.value = res.data.data;
      const p = res.data.pagination ?? { current_page: 1, last_page: 1, per_page: perPage.value, total: groups.value.length };
      pagination.value = {
        current_page: Number(p.current_page) || 1,
        last_page: Number(p.last_page) || 1,
        per_page: Number(p.per_page) || perPage.value,
        total: Number(p.total) || groups.value.length,
      };
      page.value = pagination.value.current_page;
    }
  } catch (e: any) {
    toast.type = 'error';
    toast.message = e?.response?.data?.message || 'Failed to load inventories';
    toast.show = true;
    window.setTimeout(() => (toast.show = false), 3000);
  } finally {
    isLoading.value = false;
  }
}

let searchTimer: number | null = null;
function onSearchInput() {
  if (searchTimer) window.clearTimeout(searchTimer);
  searchTimer = window.setTimeout(() => {
    page.value = 1;
    fetchGroups();
    searchTimer = null;
  }, 300);
}

function onPerPageChange() {
  page.value = 1;
  fetchGroups();
}

function goToPage(p: number) {
  if (p < 1 || p > pagination.value.last_page || p === page.value) return;
  page.value = p;
  fetchGroups();
}

function prevPage() {
  if (page.value > 1) {
    page.value -= 1;
    fetchGroups();
  }
}

function nextPage() {
  if (page.value < pagination.value.last_page) {
    page.value += 1;
    fetchGroups();
  }
}

onMounted(fetchGroups);

// Index page only lists accountables. Editing and deletion are handled in Show.

function exportExcel(accountable?: string) {
  const url = accountable ? `/api/inventories/export-excel?accountable=${encodeURIComponent(accountable)}` : '/api/inventories/export-excel';
  window.location.href = url;
}

function exportSummaryExcel() {
  window.location.href = '/api/inventories/export-summary-excel';
}

function exportPdf(accountable?: string) {
  const url = accountable ? `/api/inventories/export-pdf/${encodeURIComponent(accountable)}` : '/api/inventories/export-pdf';
  window.location.href = url;
}

function printCodes(type: 'qr' | 'barcode', size: 'letter' | 'a4' = 'letter') {
  const url = `/api/inventories/codes/print/all?type=${type}&size=${size}`;
  window.open(url, '_blank');
}

function namesWithCount(group: InventoryGroup) {
  const names = group.items.map((i) => i.inventory_name).slice(0, 3);
  const remaining = Math.max(((group.total ?? group.items.length) - 3), 0);
  return { names, remaining };
}

const filteredGroups = computed(() => groups.value);

async function deleteAccountable(accountable: string) {
  if (!accountable) return;
  const ok = window.confirm(`Delete all items under "${accountable}"?`);
  if (!ok) return;
  try {
    const res = await axios.delete(`/api/inventories/by-accountable/${encodeURIComponent(accountable)}`);
    if (res.data?.success) {
      toast.type = 'success';
      toast.message = 'Accountable deleted';
      toast.show = true;
      window.setTimeout(() => (toast.show = false), 3000);
      await fetchGroups();
    }
  } catch (e: any) {
    toast.type = 'error';
    toast.message = e?.response?.data?.errors?.[0] || 'Delete failed';
    toast.show = true;
    window.setTimeout(() => (toast.show = false), 3000);
  }
}
</script>

<template>
  <Head title="IT Inventories" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl p-4 space-y-6">
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" />

      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-1 w-full">
          <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">IT Inventories</h1>
          <p class="text-sm text-muted-foreground">Browse accountables and export per group. Manage items in each accountable’s page.</p>
        </div>
        <div class="flex flex-col gap-2 w-full sm:w-auto">
          <div class="flex flex-wrap items-center gap-2">
            <Badge variant="secondary">Groups: {{ pagination.total }}</Badge>
            <Badge variant="outline">Items: {{ totalItems }}</Badge>
          </div>
          <div class="grid w-full gap-2 sm:w-auto sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-center lg:gap-2">
            <Button class="w-full sm:w-auto" @click="exportSummaryExcel"><FileSpreadsheet class="size-4" /> Export Summary Excel</Button>
            <Button class="w-full sm:w-auto" variant="secondary" @click="exportExcel()"><FileSpreadsheet class="size-4" /> Export All Excel</Button>
            <Button class="w-full sm:w-auto" variant="secondary" @click="exportPdf()"><FileText class="size-4" /> Export All PDF</Button>
            <TooltipProvider :delay-duration="0">
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button class="w-full sm:w-auto" variant="outline" @click="printCodes('qr')">
                    <Printer class="size-4" /> Print All QR (PDF)
                  </Button>
                </TooltipTrigger>
                <TooltipContent>Print all QR codes (Letter, PDF)</TooltipContent>
              </Tooltip>
            </TooltipProvider>
            <TooltipProvider :delay-duration="0">
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button class="w-full sm:w-auto" variant="outline" @click="printCodes('barcode')">
                    <Printer class="size-4" /> Print All Barcodes (PDF)
                  </Button>
                </TooltipTrigger>
                <TooltipContent>Print all barcodes (Letter, PDF)</TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Quick Create Accountable</CardTitle>
          <CardDescription>Enter a new accountable name to start managing its items.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
            <Input v-model="newAccountable" placeholder="e.g., IT Department" :aria-invalid="!newAccountable || !newAccountable.trim()" class="w-full" />
            <Button class="w-full sm:w-auto" @click="goCreateAccountable" :disabled="!newAccountable || !newAccountable.trim()">Create</Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Guide: IT Inventories</CardTitle>
          <CardDescription>Follow these steps to navigate, create, and manage IT inventories.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-6 sm:grid-cols-2">
            <div class="space-y-3">
              <div class="text-sm font-semibold">Overview</div>
              <ul class="list-disc pl-5 space-y-2 text-sm text-muted-foreground">
                <li>This page lists accountables only. Item details are shown in the accountable’s page.</li>
                <li>Use the search bar to quickly find an accountable by name.</li>
                <li>Click <span class="font-medium text-foreground">View</span> to open the accountable and manage its items.</li>
                <li>To create a new accountable, use <span class="font-medium text-foreground">Quick Create Accountable</span> above and press <span class="font-medium text-foreground">Create</span>.</li>
              </ul>
            </div>
            <div class="space-y-3">
              <div class="text-sm font-semibold">Actions</div>
              <ul class="list-disc pl-5 space-y-2 text-sm text-muted-foreground">
                <li>Export a group’s data using <span class="font-medium text-foreground">Excel</span> or <span class="font-medium text-foreground">PDF</span> from the card.</li>
                <li>On the accountable page, use the <span class="font-medium text-foreground">Add Items</span> section to add new rows.</li>
                <li>On the accountable page, you can add multiple items, edit existing ones, or delete items.</li>
                <li>Exports are professionally formatted with clear borders for easy reading and printing.</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>



      <Card>
        <CardHeader>
          <CardTitle>IT Accountables</CardTitle>
          <CardDescription>Select an accountable to view and manage items.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="mb-3">
            <div class="relative">
              <Search class="absolute left-2 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
              <Input class="pl-8 w-full" v-model="search" placeholder="Search by accountable" @input="onSearchInput" />
            </div>
          </div>

          <div v-if="filteredGroups.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="group in filteredGroups" :key="group.inventory_accountable" class="overflow-hidden">
              <CardHeader class="border-b bg-muted/30">
                <CardTitle class="text-base sm:text-lg text-start">{{ group.inventory_accountable }}</CardTitle>
              </CardHeader>
              <CardContent class="py-4">
                <div class="flex flex-wrap gap-3 text-xs sm:text-sm text-start">
                  <span v-for="name in namesWithCount(group).names" :key="name" class="inline-flex items-center gap-1">
                    <CheckCircle class="h-3.5 w-3.5 text-green-600" />
                    <span class="truncate">{{ name }}</span>
                  </span>
                  <span v-if="namesWithCount(group).remaining > 0" class="text-xs text-muted-foreground">+{{ namesWithCount(group).remaining }} more</span>
                </div>
              </CardContent>
              <CardFooter class="mt-auto p-4 flex flex-row flex-wrap items-center gap-2">
                <TooltipProvider :delay-duration="0">
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <Link :href="`/inventories/${encodeURIComponent(group.inventory_accountable)}`">
                        <Button variant="ghost" size="icon" class="h-9 w-9">
                          <Eye class="size-4" />
                          <span class="sr-only">View</span>
                        </Button>
                      </Link>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>View</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>

                <TooltipProvider :delay-duration="0">
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <Button variant="ghost" size="icon" class="h-9 w-9" @click="exportExcel(group.inventory_accountable)">
                        <FileSpreadsheet class="size-4" />
                        <span class="sr-only">Export Excel</span>
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Export Excel</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>

                <TooltipProvider :delay-duration="0">
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <Button variant="ghost" size="icon" class="h-9 w-9" @click="exportPdf(group.inventory_accountable)">
                        <FileText class="size-4" />
                        <span class="sr-only">Export PDF</span>
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Export PDF</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>

                <TooltipProvider :delay-duration="0">
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <Button variant="destructive" size="icon" class="h-9 w-9" @click="deleteAccountable(group.inventory_accountable)">
                        <Trash2 class="size-4" />
                        <span class="sr-only">Delete</span>
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Delete</p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>
              </CardFooter>
            </Card>
          </div>
          <div v-else class="rounded-xl border p-8 text-center">
            <div class="text-lg font-semibold">No accountables found</div>
            <div class="text-sm text-muted-foreground mt-1">Try adjusting your search or refresh the list.</div>
            <Button class="mt-4" variant="secondary" @click="fetchGroups"><RefreshCw class="size-4" /> Refresh</Button>
          </div>
          <nav v-if="filteredGroups.length" class="mt-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between" aria-label="Pagination">
            <div class="flex items-center gap-3">
              <span class="text-sm text-muted-foreground">{{ showingRange }}</span>
              <span class="text-sm text-muted-foreground">Per page</span>
              <select v-model.number="perPage" @change="onPerPageChange" :disabled="isLoading"
                class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-28 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]">
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
              <Button variant="outline" size="sm" :disabled="isLoading || page <= 1" @click="prevPage">Previous</Button>
              <template v-for="(item, idx) in pages" :key="item.type === 'page' ? 'p-' + item.value + '-' + page : 'e-' + page + '-' + idx">
                <Button v-if="item.type === 'page'" size="sm" :disabled="isLoading || item.value === page" :aria-current="item.value === page ? 'page' : undefined" :variant="item.value === page ? 'secondary' : 'outline'" @click="goToPage(item.value)">{{ item.value }}</Button>
                <span v-else class="text-muted-foreground px-2">…</span>
              </template>
              <Button variant="outline" size="sm" :disabled="isLoading || page >= pagination.last_page" @click="nextPage">Next</Button>
            </div>
          </nav>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
