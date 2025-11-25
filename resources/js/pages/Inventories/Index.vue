<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import Toast from '@/pages/SiteSettings/Toast.vue';
import { FileSpreadsheet, FileText, Search, RefreshCw, Eye, Trash2 } from 'lucide-vue-next';
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
}

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Inventories', href: '/inventories' },
];

const groups = ref<InventoryGroup[]>([]);
const search = ref('');
const statusFilter = ref('');
const totalItems = computed(() => groups.value.reduce((sum, g) => sum + g.items.length, 0));
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
  const res = await axios.get('/api/inventories', { params: { search: search.value || undefined, status: statusFilter.value || undefined } });
  if (res.data?.success) groups.value = res.data.data;
}

onMounted(fetchGroups);

// Index page only lists accountables. Editing and deletion are handled in Show.

function exportExcel(accountable?: string) {
  const url = accountable ? `/api/inventories/export-excel?accountable=${encodeURIComponent(accountable)}` : '/api/inventories/export-excel';
  window.location.href = url;
}

function exportPdf(accountable?: string) {
  const url = accountable ? `/api/inventories/export-pdf/${encodeURIComponent(accountable)}` : '/api/inventories/export-pdf';
  window.location.href = url;
}

function namesPreview(group: InventoryGroup) {
  const names = group.items.map((i) => i.inventory_name);
  const max = 8;
  if (names.length <= max) return names.join(', ');
  const more = names.length - max;
  return `${names.slice(0, max).join(', ')} +${more} more`;
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

      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-1">
          <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">IT Inventories</h1>
          <p class="text-sm text-muted-foreground">Browse accountables and export per group. Manage items in each accountable’s page.</p>
        </div>
        <div class="flex items-center gap-2">
          <Badge variant="secondary">Groups: {{ groups.length }}</Badge>
          <Badge variant="outline">Items: {{ totalItems }}</Badge>
        </div>
        <div class="flex items-center gap-2">
          <Button class="w-full sm:w-auto" variant="secondary" @click="exportExcel()"><FileSpreadsheet class="size-4" /> Export All Excel</Button>
          <Button class="w-full sm:w-auto" variant="secondary" @click="exportPdf()"><FileText class="size-4" /> Export All PDF</Button>
        </div>
      </div>

      <Card>
          <CardHeader>
            <CardTitle>Quick Create Accountable</CardTitle>
            <CardDescription>Enter a new accountable name to start managing its items.</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
              <Input v-model="newAccountable" placeholder="e.g., IT Department" :aria-invalid="!newAccountable || !newAccountable.trim()" />
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
              <Input class="pl-8 w-full" v-model="search" placeholder="Search by accountable" @input="fetchGroups" />
            </div>
          </div>

          <div v-if="filteredGroups.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="group in filteredGroups" :key="group.inventory_accountable" class="overflow-hidden">
              <CardHeader class="border-b bg-muted/30">
                <CardTitle class="text-base sm:text-lg">{{ group.inventory_accountable }}</CardTitle>
                <CardDescription class="text-xs text-muted-foreground">{{ namesPreview(group) }}</CardDescription>
              </CardHeader>
              <CardFooter class="p-4 flex flex-row flex-wrap gap-2">
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
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
