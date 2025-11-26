<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import Toast from '@/pages/SiteSettings/Toast.vue';

type ToastType = 'success' | 'error';

interface LogItem {
  id: number;
  action: string;
  consumable?: { id: number; name: string };
  user?: { id: number; name: string };
  changes?: Record<string, any> | null;
  created_at: string;
}

interface PaginationMeta { current_page: number; last_page: number; per_page: number; total: number }

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Consumables', href: '/consumables' },
  { title: 'Logs', href: '/consumables/logs' },
];

const items = ref<LogItem[]>([]);
const perPage = ref<number>(10);
const page = ref<number>(1);
const pagination = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const filters = reactive({ consumable_id: '', user_id: '', action: '', date_from: '', date_to: '' });
const toast = reactive<{ show: boolean; type: ToastType; message: string }>({ show: false, type: 'success', message: '' });

const fieldDefs = [
  { key: 'consumable_name', label: 'Name' },
  { key: 'consumable_brand', label: 'Brand' },
  { key: 'consumable_description', label: 'Description' },
  { key: 'current_quantity', label: 'Quantity' },
  { key: 'threshold_limit', label: 'Threshold' },
  { key: 'unit', label: 'Unit' },
];

function changesRows(item: LogItem) {
  const changes = item.changes || {} as any;
  const before = changes.before || {};
  const after = changes.after || {};
  const usage = changes.usage || null;
  const rows: Array<{ label: string; before?: any; after?: any }> = [];
  if (item.action === 'usage') {
    rows.push({ label: 'Quantity', before: before.current_quantity, after: after.current_quantity });
  } else {
    fieldDefs.forEach(f => {
      const b = before[f.key];
      const a = after[f.key];
      if (item.action === 'updated') {
        if (b !== a) rows.push({ label: f.label, before: b, after: a });
      } else if (item.action === 'created') {
        if (a !== undefined && a !== null && a !== '') rows.push({ label: f.label, after: a });
      } else {
        if (b !== undefined && b !== null && b !== '') rows.push({ label: f.label, before: b });
      }
    });
  }
  if (item.action === 'usage' && usage) {
    rows.push({ label: 'Used Qty', after: usage.quantity_used });
    rows.push({ label: 'Purpose', after: usage.purpose });
    rows.push({ label: 'Used By', after: usage.used_by });
    rows.push({ label: 'Date Used', after: usage.date_used });
    if (usage.notes) rows.push({ label: 'Notes', after: usage.notes });
  }
  return rows;
}

function actionLabel(action: string) {
  if (action === 'usage') return 'Used';
  if (action === 'force_deleted') return 'Deleted permanently';
  if (action === 'deleted') return 'Deleted';
  if (action === 'restored') return 'Restored';
  if (action === 'updated') return 'Updated';
  if (action === 'created') return 'Created';
  return action;
}

async function fetchItems() {
  const res = await axios.get('/api/consumables/logs', { params: { ...filters, perPage: perPage.value, page: page.value } });
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
  <Head title="Consumables Logs" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl p-4 space-y-6">
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" />

      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-1">
          <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">Consumables Logs</h1>
          <p class="text-sm text-muted-foreground">Who added, updated, or deleted consumables</p>
        </div>
        <div class="flex items-center gap-2">
          <Link href="/consumables"><Button variant="secondary">Back to Consumables</Button></Link>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
          <CardDescription>Filter logs</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-3 sm:grid-cols-5">
            <Input v-model="filters.consumable_id" placeholder="Consumable ID" @change="fetchItems" />
            <Input v-model="filters.user_id" placeholder="User ID" @change="fetchItems" />
            <Input v-model="filters.action" placeholder="Action (created/updated/deleted)" @change="fetchItems" />
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
                <th class="px-4 py-3 font-medium">Action</th>
                <th class="px-4 py-3 font-medium">Consumable</th>
                <th class="px-4 py-3 font-medium">By</th>
                <th class="px-4 py-3 font-medium">Changes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="l in items" :key="l.id" class="border-t border-sidebar-border/70 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-900/60">
                <td class="px-4 py-3">{{ l.created_at }}</td>
                <td class="px-4 py-3">{{ actionLabel(l.action) }}</td>
                <td class="px-4 py-3">{{ l.consumable?.name || l.consumable?.id }}</td>
                <td class="px-4 py-3">{{ l.user?.name || '—' }}</td>
                <td class="px-4 py-3">
                  <div class="space-y-1">
                    <div v-for="row in changesRows(l)" :key="row.label" class="text-xs">
                      <span class="font-medium">{{ row.label }}</span>
                      <template v-if="l.action === 'updated'">
                        : <span class="text-neutral-500">{{ row.before ?? '—' }}</span>
                        → <span class="text-neutral-900 dark:text-neutral-100">{{ row.after ?? '—' }}</span>
                      </template>
                      <template v-else-if="l.action === 'created'">
                        : <span class="text-neutral-900 dark:text-neutral-100">{{ row.after ?? '—' }}</span>
                      </template>
                      <template v-else>
                        : <span class="text-neutral-500">{{ row.before ?? '—' }}</span>
                      </template>
                    </div>
                    <div v-if="changesRows(l).length === 0" class="text-xs text-muted-foreground">No details</div>
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
