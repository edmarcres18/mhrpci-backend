<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import Toast from '@/pages/SiteSettings/Toast.vue';
import { FileSpreadsheet, FileText, ArrowLeft, RefreshCw } from 'lucide-vue-next';

type ToastType = 'success' | 'error';

interface InventoryItem {
  id: number;
  item_code?: string; // Add item_code here
  inventory_accountable: string;
  inventory_name: string;
  inventory_specification?: string;
  inventory_brand?: string;
  inventory_status: string;
}

const props = defineProps<{ accountable: string }>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Inventories', href: '/inventories' },
  { title: props.accountable, href: `/inventories/${encodeURIComponent(props.accountable)}` },
];

const items = ref<InventoryItem[]>([]);
const search = ref('');
const statusFilter = ref('');

const toast = reactive<{ show: boolean; type: ToastType; message: string }>({
  show: false,
  type: 'success',
  message: '',
});

function showToast(type: ToastType, message: string) {
  toast.show = true;
  toast.type = type;
  toast.message = message;
  window.setTimeout(() => (toast.show = false), 3500);
}

async function fetchItems() {
  const res = await axios.get(`/api/inventories/by-accountable/${encodeURIComponent(props.accountable)}`, {
    params: { search: search.value || undefined, status: statusFilter.value || undefined },
  });
  if (res.data?.success) {
    items.value = (res.data.data as InventoryItem[]).map((it) => ({
      ...it,
      inventory_specification: it.inventory_specification ?? '',
      inventory_brand: it.inventory_brand ?? '',
    }));
  }
}

onMounted(fetchItems);

async function updateItem(item: InventoryItem) {
  try {
    const payload = {
      ...item,
      inventory_specification: item.inventory_specification || null,
      inventory_brand: item.inventory_brand || null,
    };
    const res = await axios.put(`/api/inventories/${item.id}`, payload);
    if (res.data?.success) showToast('success', 'Item updated');
  } catch (e: any) {
    const msg = e?.response?.data?.errors?.[0] || 'Update failed';
    showToast('error', msg);
  }
}

async function deleteItem(item: InventoryItem) {
  try {
    await axios.delete(`/api/inventories/${item.id}`);
    showToast('success', 'Item deleted');
    await fetchItems();
  } catch {
    showToast('error', 'Delete failed');
  }
}

const newItems = ref<Pick<InventoryItem, 'inventory_name' | 'inventory_specification' | 'inventory_brand' | 'inventory_status'>[]>([
  { inventory_name: '', inventory_specification: '', inventory_brand: '', inventory_status: 'active' },
]);
function addRow() {
  newItems.value.push({ inventory_name: '', inventory_specification: '', inventory_brand: '', inventory_status: 'active' });
}

function removeRow(index: number) {
  if (newItems.value.length > 1) newItems.value.splice(index, 1);
}

async function saveBatch() {
  const payload = {
    inventory_accountable: props.accountable,
    items: newItems.value.map(i => ({
      inventory_name: i.inventory_name,
      inventory_specification: i.inventory_specification || null,
      inventory_brand: i.inventory_brand || null,
      inventory_status: i.inventory_status || 'active',
    })),
  };
  try {
    const res = await axios.post('/api/inventories/batch', payload);
    if (res.data?.success) {
      showToast('success', 'Items added');
      newItems.value = [{ inventory_name: '', inventory_specification: '', inventory_brand: '', inventory_status: 'active' }];
      await fetchItems();
    }
  } catch (e: any) {
    const msg = e?.response?.data?.errors?.[0] || 'Failed to save';
    showToast('error', msg);
  }
}

function exportExcel() {
  const url = `/api/inventories/export-excel?accountable=${encodeURIComponent(props.accountable)}`;
  window.location.href = url;
}

function exportPdf() {
  const url = `/api/inventories/export-pdf/${encodeURIComponent(props.accountable)}`;
  window.location.href = url;
}

const filteredItems = computed(() => items.value);
</script>

<template>
  <Head :title="`IT Inventories — ${props.accountable}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl p-4 space-y-6">
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" />

      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-1">
          <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">{{ props.accountable }}</h1>
          <p class="text-sm text-muted-foreground">View, add, edit, and delete items for this accountable. Use search and filters to find items quickly.</p>
        </div>
        <div class="flex items-center gap-2">
          <Badge variant="secondary">Items: {{ items.length }}</Badge>
          <Button variant="secondary" @click="exportExcel"><FileSpreadsheet class="size-4" /> Excel</Button>
          <Button variant="secondary" @click="exportPdf"><FileText class="size-4" /> PDF</Button>
          <Link href="/inventories"><Button variant="secondary"><ArrowLeft class="size-4" /> Back</Button></Link>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Guide: IT Inventories</CardTitle>
          <CardDescription>Follow these steps to create, edit, and manage items within this accountable.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-6 sm:grid-cols-2">
            <div class="space-y-3">
              <div class="text-sm font-semibold">Overview</div>
              <ul class="list-disc pl-5 space-y-2 text-sm text-muted-foreground">
                <li>This page shows all items under the selected accountable.</li>
                <li>Use the search and status filter to narrow down items.</li>
                <li>Exports generate professional Excel/PDF files with clear borders.</li>
                <li>Use the <span class="font-medium text-foreground">Add Items</span> section below to add new rows.</li>
              </ul>
            </div>
            <div class="space-y-3">
              <div class="text-sm font-semibold">Actions</div>
              <ul class="list-disc pl-5 space-y-2 text-sm text-muted-foreground">
                <li>Add multiple new items using the batch form below.</li>
                <li>Edit item fields inline; changes are saved immediately.</li>
                <li>Delete items when they are no longer needed.</li>
                <li>Use <span class="font-medium text-foreground">Save Items</span> to commit newly added rows.</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Add Items</CardTitle>
          <CardDescription>Create multiple items and save as a batch.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col sm:flex-row gap-2">
              <Button class="w-full sm:w-auto" variant="secondary" @click="addRow">Add Row</Button>
              <Button class="w-full sm:w-auto" @click="saveBatch">Save Items</Button>
            </div>
          </div>

          <div class="mt-4 md:hidden space-y-3">
            <Card v-for="(row, idx) in newItems" :key="idx" class="rounded-xl border">
              <CardContent class="space-y-3 py-4">
                <div class="space-y-2">
                  <Label>Name</Label>
                  <Input v-model="row.inventory_name" placeholder="Name" />
                </div>
                <div class="space-y-2">
                  <Label>Specification</Label>
                  <Input v-model="row.inventory_specification" placeholder="Specification" />
                </div>
                <div class="space-y-2">
                  <Label>Brand</Label>
                  <Input v-model="row.inventory_brand" placeholder="Brand" />
                </div>
                <div class="space-y-2">
                  <Label>Status</Label>
                  <Input v-model="row.inventory_status" placeholder="Status" />
                </div>
                <div class="flex">
                  <Button class="w-full" variant="secondary" size="sm" @click="removeRow(idx)">Remove</Button>
                </div>
              </CardContent>
            </Card>
          </div>

          <div class="mt-4 overflow-x-auto rounded-lg border hidden md:block">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b bg-muted/50">
                  <th class="p-2 text-left">Name</th>
                  <th class="p-2 text-left">Specification</th>
                  <th class="p-2 text-left">Brand</th>
                  <th class="p-2 text-left">Status</th>
                  <th class="p-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in newItems" :key="idx" class="border-b hover:bg-muted/30">
                  <td class="p-2"><Input v-model="row.inventory_name" placeholder="Name" /></td>
                  <td class="p-2"><Input v-model="row.inventory_specification" placeholder="Specification" /></td>
                  <td class="p-2"><Input v-model="row.inventory_brand" placeholder="Brand" /></td>
                  <td class="p-2"><Input v-model="row.inventory_status" placeholder="Status" /></td>
                  <td class="p-2 text-right">
                    <Button variant="secondary" size="sm" @click="removeRow(idx)">Remove</Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Items</CardTitle>
          <CardDescription>Search, edit inline, or delete items.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="mb-3 grid gap-3 sm:grid-cols-3">
            <div class="relative">
              <Input class="w-full" v-model="search" placeholder="Search items (name, brand, spec)" @input="fetchItems" />
            </div>
            <Input class="w-full" v-model="statusFilter" placeholder="Status (e.g., active)" @input="fetchItems" />
            <Button class="w-full sm:w-auto" variant="secondary" @click="fetchItems"><RefreshCw class="size-4" /> Refresh</Button>
          </div>

          <div v-if="filteredItems.length" class="overflow-x-auto hidden md:block">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b bg-muted/50">
                  <th class="p-2 text-left">Name</th>
                  <th class="p-2 text-left">Code</th>
                  <th class="p-2 text-left hidden md:table-cell">Specification</th>
                  <th class="p-2 text-left hidden md:table-cell">Brand</th>
                  <th class="p-2 text-left">Status</th>
                  <th class="p-2"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in filteredItems" :key="item.id" class="border-b hover:bg-muted/30">
                  <td class="p-2"><Input v-model="item.inventory_name" @change="updateItem(item)" /></td>
                  <td class="p-2"><Input :model-value="item.item_code" readonly /></td>
                  <td class="p-2 hidden md:table-cell"><Input v-model="item.inventory_specification" @change="updateItem(item)" /></td>
                  <td class="p-2 hidden md:table-cell"><Input v-model="item.inventory_brand" @change="updateItem(item)" /></td>
                  <td class="p-2"><Input v-model="item.inventory_status" @change="updateItem(item)" /></td>
                  <td class="p-2 text-right">
                    <Button variant="secondary" size="sm" @click="deleteItem(item)">Delete</Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="filteredItems.length" class="md:hidden space-y-3">
            <Card v-for="item in filteredItems" :key="item.id" class="rounded-xl border">
              <CardContent class="space-y-3 py-4">
                <div class="space-y-2">
                  <Label>Name</Label>
                  <Input v-model="item.inventory_name" @change="updateItem(item)" />
                </div>
                <div class="space-y-2">
                  <Label>Code</Label>
                  <Input :model-value="item.item_code" readonly />
                </div>
                <div class="space-y-2">
                  <Label>Specification</Label>
                  <Input v-model="item.inventory_specification" @change="updateItem(item)" />
                </div>
                <div class="space-y-2">
                  <Label>Brand</Label>
                  <Input v-model="item.inventory_brand" @change="updateItem(item)" />
                </div>
                <div class="space-y-2">
                  <Label>Status</Label>
                  <Input v-model="item.inventory_status" @change="updateItem(item)" />
                </div>
                <div class="flex">
                  <Button class="w-full" variant="secondary" size="sm" @click="deleteItem(item)">Delete</Button>
                </div>
              </CardContent>
            </Card>
          </div>
          <div v-else class="rounded-xl border p-8 text-center">
            <div class="text-lg font-semibold">No items found</div>
            <div class="text-sm text-muted-foreground mt-1">Try adjusting your search or add new items below.</div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
 </template>
