<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface PhoneRecord {
  id: number;
  department: string;
  phone_number: string;
  person_in_charge: string;
  position: string;
  extension?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
}

const props = defineProps<{ phone: PhoneRecord }>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Company Phones', href: '/company-phones' },
  { title: props.phone.phone_number, href: `/company-phones/${props.phone.id}` },
];
</script>

<template>
  <Head :title="`Phone: ${props.phone.phone_number}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-3xl p-4">
      <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Phone Details</h1>
        <div class="flex items-center gap-3">
          <Link :href="`/company-phones/${props.phone.id}/edit`" class="text-sm text-primary hover:underline">Edit</Link>
          <Link href="/company-phones" class="text-sm text-primary hover:underline">Back to list</Link>
        </div>
      </div>

      <div class="space-y-6 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Department</label>
            <p class="text-sm">{{ props.phone.department }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Phone Number</label>
            <p class="text-sm">{{ props.phone.phone_number }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Person In Charge</label>
            <p class="text-sm">{{ props.phone.person_in_charge }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Position</label>
            <p class="text-sm">{{ props.phone.position }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Extension</label>
            <p class="text-sm">{{ props.phone.extension || '—' }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Created At</label>
            <p class="text-sm">{{ props.phone.created_at ? new Date(props.phone.created_at).toLocaleString() : 'N/A' }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Last Updated</label>
            <p class="text-sm">{{ props.phone.updated_at ? new Date(props.phone.updated_at).toLocaleString() : 'N/A' }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">ID</label>
            <p class="font-mono text-sm">{{ props.phone.id }}</p>
          </div>
        </div>

        <hr class="border-neutral-200 dark:border-neutral-800" />

        <div class="flex items-center justify-end gap-3">
          <Link href="/company-phones" class="text-sm hover:underline">Back to List</Link>
          <Link 
            :href="`/company-phones/${props.phone.id}/edit`" 
            class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 dark:bg-white dark:text-black dark:hover:bg-neutral-200"
          >
            Edit Phone
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
  </template>

