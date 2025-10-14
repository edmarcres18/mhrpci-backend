<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface User {
  id: number;
  name: string;
  email: string;
  role?: string;
  role_display?: string;
  email_verified_at?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
}

const props = defineProps<{ user: User }>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Users', href: '/users' },
  { title: props.user.name, href: `/users/${props.user.id}` },
];

function getRoleBadgeColor(role?: string) {
  switch (role) {
    case 'system_admin':
      return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-200';
    case 'admin':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200';
    case 'staff':
      return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200';
    default:
      return 'bg-neutral-100 text-neutral-800 dark:bg-neutral-900/30 dark:text-neutral-200';
  }
}
</script>

<template>
  <Head :title="`User: ${props.user.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-3xl p-4">
      <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">User Details</h1>
        <div class="flex items-center gap-3">
          <Link :href="`/users/${props.user.id}/edit`" class="text-sm text-primary hover:underline">Edit</Link>
          <Link href="/users" class="text-sm text-primary hover:underline">Back to list</Link>
        </div>
      </div>

      <div class="space-y-6 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
        <!-- User Avatar -->
        <div class="flex items-center gap-4">
          <div class="flex h-20 w-20 items-center justify-center rounded-full bg-neutral-100 text-3xl font-semibold text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
            {{ props.user.name.charAt(0).toUpperCase() }}
          </div>
          <div>
            <h2 class="text-2xl font-semibold">{{ props.user.name }}</h2>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ props.user.email }}</p>
          </div>
        </div>

        <hr class="border-neutral-200 dark:border-neutral-800" />

        <!-- User Information -->
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Role</label>
            <span class="inline-block rounded-full px-3 py-1 text-sm font-medium" :class="getRoleBadgeColor(props.user.role)">
              {{ props.user.role_display }}
            </span>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Email Verified</label>
            <p class="text-sm">
              <span v-if="props.user.email_verified_at" class="text-green-600 dark:text-green-400">
                ✓ Verified on {{ new Date(props.user.email_verified_at).toLocaleDateString() }}
              </span>
              <span v-else class="text-neutral-500 dark:text-neutral-400">Not verified</span>
            </p>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Created At</label>
            <p class="text-sm">{{ props.user.created_at ? new Date(props.user.created_at).toLocaleString() : 'N/A' }}</p>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Last Updated</label>
            <p class="text-sm">{{ props.user.updated_at ? new Date(props.user.updated_at).toLocaleString() : 'N/A' }}</p>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">User ID</label>
            <p class="font-mono text-sm">{{ props.user.id }}</p>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400">Email</label>
            <p class="text-sm">{{ props.user.email }}</p>
          </div>
        </div>

        <hr class="border-neutral-200 dark:border-neutral-800" />

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
          <Link href="/users" class="text-sm hover:underline">Back to List</Link>
          <Link 
            :href="`/users/${props.user.id}/edit`" 
            class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 dark:bg-white dark:text-black dark:hover:bg-neutral-200"
          >
            Edit User
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
