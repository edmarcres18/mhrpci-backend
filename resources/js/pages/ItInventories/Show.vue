<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface InventoryDetail {
  id: number;
  asset_tag: string;
  category: string;
  type?: string | null;
  brand?: string | null;
  model?: string | null;
  serial_number?: string | null;
  status: string;
  location?: string | null;
  assigned_to?: string | null;
  purchase_date?: string | null;
  purchase_cost?: string | number | null;
  supplier?: string | null;
  warranty_expires_at?: string | null;
  notes?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
}

const props = defineProps<{ inventory: InventoryDetail }>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Inventories', href: '/it-inventories' },
  { title: props.inventory.asset_tag, href: `/it-inventories/${props.inventory.id}` },
];
</script>

<template>
  <Head :title="`Inventory: ${props.inventory.asset_tag}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-4xl p-4">
      <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Inventory Details</h1>
        <div class="flex gap-2">
          <Link :href="`/it-inventories/${props.inventory.id}/edit`" class="rounded-md border px-3 py-2 text-sm hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800">Edit</Link>
          <Link href="/it-inventories" class="rounded-md border px-3 py-2 text-sm hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800">Back to list</Link>
        </div>
      </div>

      <div class="grid gap-6 sm:grid-cols-2">
        <div class="rounded-xl border border-sidebar-border/70 bg-white p-6 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
          <div class="space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-neutral-500">Asset Tag</span><span class="font-medium">{{ props.inventory.asset_tag }}</span></div>
            <div class="flex justify-between"><span class="text-neutral-500">Category</span><span class="font-medium">{{ props.inventory.category }}</span></div>
            <div class="flex justify-between"><span class="text-neutral-500">Type</span><span class="font-medium">{{ props.inventory.type || '—' }}</span></div>
            <div class="flex justify-between"><span class="text-neutral-500">Brand</span><span class="font-medium">{{ props.inventory.brand || '—' }}</span></div>
            <div class="flex justify-between"><span class="text-neutral-500">Model</span><span class="font-medium">{{ props.inventory.model || '—' }}</span></div>
            <div class="flex justify-between"><span class="text-neutral-500">Serial</span><span class="font-medium">{{ props.inventory.serial_number || '—' }}</span></div>
            <div class="flex justify-between"><span class="text-neutral-500">Status</span><span class="font-medium">{{ props.inventory.status }}</span></div>
            <div class="flex justify-between"><span class="text-neutral-500">Location</span><span class="font-medium">{{ props.inventory.location || '—' }}</span></div>
            <div class="flex justify-between"><span class="text-neutral-500">Assigned To</span><span class="font-medium">{{ props.inventory.assigned_to || '—' }}</span></div>
          </div>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-white p-6 shadow-sm dark:border-sidebar-border dark:bg-neutral-950">
          <div class="space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-neutral-500">Purchase Date</span><span class="font-medium">{{ props.inventory.purchase_date || '—' }}</span></div>
            <div class="flex justify-between"><span class="text-neutral-500">Purchase Cost</span><span class="font-medium">{{ props.inventory.purchase_cost || '—' }}</span></div>
            <div class="flex justify-between"><span class="text-neutral-500">Supplier</span><span class="font-medium">{{ props.inventory.supplier || '—' }}</span></div>
            <div class="flex justify-between"><span class="text-neutral-500">Warranty Expires</span><span class="font-medium">{{ props.inventory.warranty_expires_at || '—' }}</span></div>
            <div class="space-y-1"><div class="text-neutral-500">Notes</div><div class="rounded-md border p-3 text-sm dark:border-neutral-700">{{ props.inventory.notes || '—' }}</div></div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>