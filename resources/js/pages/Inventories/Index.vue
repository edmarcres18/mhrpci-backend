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
import { FileSpreadsheet, FileText, Search, RefreshCw } from 'lucide-vue-next';
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

function exportPdf(accountable: string) {
  const url = `/api/inventories/export-pdf/${encodeURIComponent(accountable)}`;
  window.location.href = url;
}

const filteredGroups = computed(() => groups.value);
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
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Quick Create Accountable</CardTitle>
          <CardDescription>Enter a new accountable name to start managing its items.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
            <Input v-model="newAccountable" placeholder="e.g., IT Department" />
            <Button class="w-full sm:w-auto" @click="goCreateAccountable">Create</Button>
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
          <div class="mb-3 grid gap-3 sm:grid-cols-3">
            <div class="relative">
              <Search class="absolute left-2 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
              <Input class="pl-8" v-model="search" placeholder="Search by accountable" @input="fetchGroups" />
            </div>
            <Button variant="secondary" @click="fetchGroups"><RefreshCw class="size-4" /> Refresh</Button>
            <div class="hidden sm:block"></div>
          </div>

          <div v-if="filteredGroups.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="group in filteredGroups" :key="group.inventory_accountable" class="overflow-hidden">
              <CardHeader class="border-b bg-muted/30">
                <CardTitle class="text-base sm:text-lg">{{ group.inventory_accountable }}</CardTitle>
                <CardDescription>{{ group.items.length }} items</CardDescription>
              </CardHeader>
              <CardFooter class="p-4 flex flex-col sm:flex-row sm:flex-wrap gap-2">
                <Link class="w-full sm:w-auto" :href="`/inventories/${encodeURIComponent(group.inventory_accountable)}`">
                  <Button class="w-full sm:w-auto">View</Button>
                </Link>
                <Button class="w-full sm:w-auto" variant="secondary" @click="exportExcel(group.inventory_accountable)"><FileSpreadsheet class="size-4" /> Excel</Button>
                <Button class="w-full sm:w-auto" variant="secondary" @click="exportPdf(group.inventory_accountable)"><FileText class="size-4" /> PDF</Button>
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
