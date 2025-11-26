<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import Toast from '@/pages/SiteSettings/Toast.vue';
import { ArrowLeft } from 'lucide-vue-next';

type ToastType = 'success' | 'error';

interface UsageItem {
  id: number;
  consumable_id: number;
  quantity_used: number;
  purpose: string;
  used_by: string;
  date_used: string;
  notes?: string | null;
  consumable?: { id: number; name: string; brand?: string | null; unit: string };
}

interface PaginationMeta { current_page: number; last_page: number; per_page: number; total: number }

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Consumables', href: '/consumables' },
  { title: 'Usage History', href: '/consumables/usage-history' },
];

const items = ref<UsageItem[]>([]);
const perPage = ref<number>(10);
const page = ref<number>(1);
const pagination = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const filters = reactive({ consumable_id: '', used_by: '', purpose: '', date_from: '', date_to: '' });
const toast = reactive<{ show: boolean; type: ToastType; message: string }>({ show: false, type: 'success', message: '' });

async function fetchItems() {
  const res = await axios.get('/api/consumables/usages', { params: { ...filters, perPage: perPage.value, page: page.value } });
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

onMounted(fetchItems);
</script>

<template>
  <Head title="Usage History" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl p-4 space-y-6">
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" />

      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-1">
          <h1 class="text-2xl font-semibold">Usage History</h1>
          <div class="text-sm text-muted-foreground">Track consumable usage across the system</div>
        </div>
        <div class="flex items-center gap-2">
          <Link href="/consumables"><Button variant="secondary"><ArrowLeft class="size-4" /> Back</Button></Link>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
          <CardDescription>Filter usage records</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-3 sm:grid-cols-5">
            <Input v-model="filters.consumable_id" placeholder="Consumable ID" @change="fetchItems" />
            <Input v-model="filters.used_by" placeholder="Used By" @change="fetchItems" />
            <Input v-model="filters.purpose" placeholder="Purpose" @change="fetchItems" />
            <Input v-model="filters.date_from" type="date" @change="fetchItems" />
            <Input v-model="filters.date_to" type="date" @change="fetchItems" />
          </div>
        </CardContent>
      </Card>

      <div class="rounded-xl border overflow-hidden">
        <div class="relative w-full overflow-auto">
          <table class="w-full caption-bottom text-sm">
            <thead class="bg-neutral-50 text-left text-xs uppercase text-neutral-500 dark:bg-neutral-900/60 dark:text-neutral-400">
              <tr>
                <th class="px-4 py-3 font-medium">Date</th>
                <th class="px-4 py-3 font-medium">Consumable</th>
                <th class="px-4 py-3 font-medium">Qty</th>
                <th class="px-4 py-3 font-medium">Unit</th>
                <th class="px-4 py-3 font-medium">Used By</th>
                <th class="px-4 py-3 font-medium">Purpose</th>
                <th class="px-4 py-3 font-medium">Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in items" :key="u.id" class="border-t border-sidebar-border/70 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-900/60">
                <td class="px-4 py-3">{{ u.date_used }}</td>
                <td class="px-4 py-3">{{ u.consumable?.name || u.consumable_id }}</td>
                <td class="px-4 py-3">{{ u.quantity_used }}</td>
                <td class="px-4 py-3">{{ u.consumable?.unit || '—' }}</td>
                <td class="px-4 py-3">{{ u.used_by }}</td>
                <td class="px-4 py-3">{{ u.purpose }}</td>
                <td class="px-4 py-3">{{ u.notes }}</td>
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

