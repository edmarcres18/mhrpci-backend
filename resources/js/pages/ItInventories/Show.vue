<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const inventory = (page.props as any).inventory;

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Inventories', href: '/it-inventories' },
  { title: 'Details', href: `/it-inventories/${inventory.id}` },
];
</script>

<template>
  <Head :title="`Inventory: ${inventory.inventory_name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-4xl p-4">
      <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Inventory Details</h1>
        <div class="flex items-center gap-2 text-sm">
          <Link :href="`/it-inventories/${inventory.id}/edit`" class="rounded-md border px-3 py-2 hover:bg-neutral-100 dark:hover:bg-neutral-800">Edit</Link>
          <Link href="/it-inventories" class="rounded-md border px-3 py-2 hover:bg-neutral-100 dark:hover:bg-neutral-800">Back</Link>
        </div>
      </div>

      <div class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-neutral-800">
        <div class="grid grid-cols-1 gap-0 sm:grid-cols-2">
          <div class="border-b border-sidebar-border/70 p-4 dark:border-neutral-800 sm:border-r">
            <div class="text-xs uppercase text-neutral-500">Inventory Name</div>
            <div class="mt-1 font-medium">{{ inventory.inventory_name }}</div>
          </div>
          <div class="border-b border-sidebar-border/70 p-4 dark:border-neutral-800">
            <div class="text-xs uppercase text-neutral-500">Accountable By</div>
            <div class="mt-1">{{ inventory.accountable_by_name || '—' }}</div>
          </div>

          <div class="border-b border-sidebar-border/70 p-4 dark:border-neutral-800 sm:col-span-2">
            <div class="text-xs uppercase text-neutral-500">Descriptions</div>
            <div class="mt-1 whitespace-pre-line">{{ inventory.descriptions || '—' }}</div>
          </div>

          <div class="p-4 sm:col-span-2">
            <div class="text-xs uppercase text-neutral-500">Remarks</div>
            <div class="mt-1 whitespace-pre-line">{{ inventory.remarks || '—' }}</div>
          </div>
        </div>
      </div>

      <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
        <div class="rounded-lg border p-4">
          <div class="text-xs uppercase text-neutral-500">Created</div>
          <div class="mt-1">{{ new Date(inventory.created_at || '').toLocaleString() }}</div>
        </div>
        <div class="rounded-lg border p-4">
          <div class="text-xs uppercase text-neutral-500">Updated</div>
          <div class="mt-1">{{ new Date(inventory.updated_at || '').toLocaleString() }}</div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>