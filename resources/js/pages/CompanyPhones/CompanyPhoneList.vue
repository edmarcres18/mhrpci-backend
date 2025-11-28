<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import Toast from '@/pages/SiteSettings/Toast.vue';
import { FileSpreadsheet, FileText, Plus, Upload, Trash2, PencilLine, RefreshCw, Search } from 'lucide-vue-next';

type ToastType = 'success' | 'error';

interface PhoneRecord {
  id: number;
  department: string;
  phone_number: string;
  person_in_charge: string;
  position: string;
  extension?: string | null;
}

interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Company Phones', href: '/company-phones' },
];

const items = ref<PhoneRecord[]>([]);
const search = ref('');
const department = ref('');
const perPage = ref<number>(10);
const page = ref<number>(1);
const pagination = ref<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const isLoading = ref(false);
const showingRange = computed(() => {
  const total = pagination.value.total;
  if (!total) return 'Showing 0 of 0';
  const start = (pagination.value.current_page - 1) * pagination.value.per_page + 1;
  const end = Math.min(pagination.value.current_page * pagination.value.per_page, total);
  return `Showing ${start}–${end} of ${total}`;
});

const toast = reactive<{ show: boolean; type: ToastType; message: string }>({ show: false, type: 'success', message: '' });

function showToast(type: ToastType, message: string, duration = 3000) {
  toast.show = true; toast.type = type; toast.message = message;
  window.setTimeout(() => (toast.show = false), duration);
}

function normalizeErrors(e: any): string {
  const errs = e?.response?.data?.errors;
  if (Array.isArray(errs)) return errs[0] || 'Request failed';
  if (typeof e?.message === 'string') return e.message;
  return 'Request failed';
}

async function fetchItems() {
  try {
    isLoading.value = true;
    const res = await axios.get('/api/company-phones', {
      params: {
        search: search.value || undefined,
        department: department.value || undefined,
        perPage: perPage.value,
        page: page.value,
      },
    });
    if (res.data?.success) {
      items.value = res.data.data || [];
      const p = res.data.pagination ?? { current_page: 1, last_page: 1, per_page: perPage.value, total: items.value.length };
      pagination.value = {
        current_page: Number(p.current_page) || 1,
        last_page: Number(p.last_page) || 1,
        per_page: Number(p.per_page) || perPage.value,
        total: Number(p.total) || items.value.length,
      };
      page.value = pagination.value.current_page;
    }
  } catch (e: any) {
    showToast('error', normalizeErrors(e));
  } finally {
    isLoading.value = false;
  }
}

let searchTimer: number | null = null;
function onSearchInput() {
  if (searchTimer) window.clearTimeout(searchTimer);
  searchTimer = window.setTimeout(() => { page.value = 1; fetchItems(); searchTimer = null; }, 300);
}
function onPerPageChange() { page.value = 1; fetchItems(); }
function prevPage() { if (page.value > 1) { page.value -= 1; fetchItems(); } }
function nextPage() { if (page.value < pagination.value.last_page) { page.value += 1; fetchItems(); } }

function exportExcel() { window.location.href = '/api/company-phones/export/excel'; }
function exportPdf() { window.location.href = '/api/company-phones/export/pdf'; }

async function deleteItem(id: number) {
  const ok = window.confirm('Delete this record?');
  if (!ok) return;
  try {
    const res = await axios.delete(`/api/company-phones/${id}`);
    if (res.data?.success) {
      showToast('success', 'Record deleted');
      fetchItems();
    }
  } catch (e: any) {
    showToast('error', normalizeErrors(e));
  }
}

onMounted(fetchItems);
</script>

<template>
  <Head title="Company Phones" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl p-4 space-y-6">
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" />

      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div class="space-y-1">
          <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight">Company Phones</h1>
          <p class="text-sm text-muted-foreground">Manage phone records, import/export and generate PDF</p>
        </div>
        <div class="flex items-center gap-2">
          <Link href="/company-phones/create"><Button class="w-full sm:w-auto"><Plus class="size-4" /> Add</Button></Link>
          <Link href="/company-phones/import"><Button class="w-full sm:w-auto" variant="secondary"><Upload class="size-4" /> Import</Button></Link>
          <Button class="w-full sm:w-auto" variant="secondary" @click="exportExcel"><FileSpreadsheet class="size-4" /> Export Excel</Button>
          <Button class="w-full sm:w-auto" variant="secondary" @click="exportPdf"><FileText class="size-4" /> Export PDF</Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
          <CardDescription>Search and filter phone records</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-3 sm:grid-cols-3">
            <div class="relative">
              <Search class="absolute left-2 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
              <Input class="pl-8 w-full" v-model="search" placeholder="Search by phone, person, position, extension" @input="onSearchInput" />
            </div>
            <Input v-model="department" placeholder="Department" @change="fetchItems" />
            <Button class="w-full sm:w-auto" variant="secondary" @click="fetchItems"><RefreshCw class="size-4" /> Refresh</Button>
          </div>
        </CardContent>
      </Card>

      <div class="rounded-xl border overflow-hidden">
        <div class="relative w-full overflow-auto">
          <table class="w-full caption-bottom text-sm">
            <thead class="bg-neutral-50 text-left text-xs uppercase text-neutral-500 dark:bg-neutral-900/60 dark:text-neutral-400">
              <tr>
                <th class="px-4 py-3 font-medium">Department</th>
                <th class="px-4 py-3 font-medium">Phone</th>
                <th class="px-4 py-3 font-medium">Person In Charge</th>
                <th class="px-4 py-3 font-medium">Position</th>
                <th class="px-4 py-3 font-medium">Ext.</th>
                <th class="px-4 py-3 text-right font-medium">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="e in items" :key="e.id" class="border-t border-sidebar-border/70 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-900/60">
                <td class="px-4 py-3">{{ e.department }}</td>
                <td class="px-4 py-3">{{ e.phone_number }}</td>
                <td class="px-4 py-3">{{ e.person_in_charge }}</td>
                <td class="px-4 py-3">{{ e.position }}</td>
                <td class="px-4 py-3">{{ e.extension || '' }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-2">
                    <Link :href="`/company-phones/${e.id}/edit`"><Button variant="ghost" size="icon" class="h-9 w-9"><PencilLine class="size-4" /><span class="sr-only">Edit</span></Button></Link>
                    <Button variant="destructive" size="icon" class="h-9 w-9" @click="deleteItem(e.id)"><Trash2 class="size-4" /><span class="sr-only">Delete</span></Button>
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
        <div class="text-sm text-muted-foreground">{{ showingRange }}</div>
        <div class="flex items-center gap-2">
          <Button variant="outline" size="sm" :disabled="page <= 1" @click="prevPage">Prev</Button>
          <div class="text-sm text-muted-foreground">Page {{ pagination.current_page }} of {{ pagination.last_page }}</div>
          <Button variant="outline" size="sm" :disabled="page >= pagination.last_page" @click="nextPage">Next</Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

