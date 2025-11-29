<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface EmailRecord {
  id: number;
  department: string;
  email: string;
  password?: string | null;
  person_in_charge: string;
  position: string;
  created_at?: string | null;
  updated_at?: string | null;
}

const props = defineProps<{ email: EmailRecord }>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Emails', href: '/emails' },
  { title: props.email.email, href: `/emails/${props.email.id}` },
];
</script>

<template>
  <Head :title="`Email: ${props.email.email}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-3xl p-4">
      <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Email Details</h1>
        <div class="flex items-center gap-3">
          <Link :href="`/emails/${props.email.id}/edit`" class="text-sm text-primary hover:underline">Edit</Link>
          <Link href="/emails" class="text-sm text-primary hover:underline">Back to list</Link>
        </div>
      </div>

      <div class="space-y-6 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Department</label>
            <p class="text-sm">{{ props.email.department }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Email</label>
            <p class="text-sm">{{ props.email.email }}</p>
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Password</label>
            <p class="font-mono text-sm break-all">{{ props.email.password || '—' }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Person In Charge</label>
            <p class="text-sm">{{ props.email.person_in_charge }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Position</label>
            <p class="text-sm">{{ props.email.position }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Created At</label>
            <p class="text-sm">{{ props.email.created_at ? new Date(props.email.created_at).toLocaleString() : 'N/A' }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Last Updated</label>
            <p class="text-sm">{{ props.email.updated_at ? new Date(props.email.updated_at).toLocaleString() : 'N/A' }}</p>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">ID</label>
            <p class="font-mono text-sm">{{ props.email.id }}</p>
          </div>
        </div>

        <hr class="border-neutral-200 dark:border-neutral-800" />

        <div class="flex items-center justify-end gap-3">
          <Link href="/emails" class="text-sm hover:underline">Back to List</Link>
          <Link 
            :href="`/emails/${props.email.id}/edit`" 
            class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 dark:bg-white dark:text-black dark:hover:bg-neutral-200"
          >
            Edit Email
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
  </template>
