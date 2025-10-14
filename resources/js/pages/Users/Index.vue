<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import type { InertiaForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog';
import Toast from './Toast.vue';

interface User {
  id: number;
  name: string;
  email: string;
  role?: string;
  role_display?: string;
  created_at?: string | null;
}

interface Pagination<T> {
  data: T[];
  links: { url: string | null; label: string; active: boolean }[];
}

const page = usePage();
const users = computed(() => (page.props.users as Pagination<User>) ?? { data: [], links: [] });
const auth = computed(() => (page.props as any).auth || {});
const currentUserRole = computed<string | null>(() => auth.value?.user?.role ?? null);
const currentUserId = computed<number | null>(() => auth.value?.user?.id ?? null);
const initialFilters = computed(() => (page.props as any)?.filters ?? { search: '', perPage: 10 });
const search = ref<string>(initialFilters.value.search || '');
const perPage = ref<number>(Number(initialFilters.value.perPage) || 10);
let searchTimer: number | undefined;
watch(
  () => search.value,
  (val) => {
    if (searchTimer) window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
      router.get('/users', { search: val || undefined, perPage: perPage.value }, { preserveState: true, preserveScroll: true, replace: true });
    }, 350);
  }
);

watch(
  () => perPage.value,
  (val) => {
    // Reset to page 1 implicitly by not sending page parameter
    router.get('/users', { search: search.value || undefined, perPage: val }, { preserveState: true, preserveScroll: true, replace: true });
  }
);

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Users', href: '/users' },
];

// Delete confirmation modal state
const confirmOpen = ref(false);
const deletingId = ref<number | null>(null);
const deletingName = ref<string>('');
const isDeleting = ref(false);

function canDeleteUser(u: User) {
  // Prevent deleting self
  if (currentUserId.value && u.id === currentUserId.value) return false;
  // Admins cannot delete Admin or System Admin
  if (currentUserRole.value === 'admin') {
    return u.role !== 'admin' && u.role !== 'system_admin';
  }
  // System Admin (and other higher roles) can proceed; server still enforces final rules
  return true;
}

function requestDelete(id: number, name: string) {
  deletingId.value = id;
  deletingName.value = name;
  confirmOpen.value = true;
}

function cancelDelete() {
  confirmOpen.value = false;
  deletingId.value = null;
  deletingName.value = '';
}

// Toast state
type ToastType = 'success' | 'error';
const toast = reactive<{ show: boolean; type: ToastType; message: string }>({
  show: false,
  type: 'success',
  message: '',
});
let toastTimer: number | undefined;

function showToast(type: ToastType, message: string, duration = 3000) {
  toast.show = true;
  toast.type = type;
  toast.message = message;
  if (toastTimer) window.clearTimeout(toastTimer);
  toastTimer = window.setTimeout(() => (toast.show = false), duration);
}

function closeToast() {
  toast.show = false;
  if (toastTimer) window.clearTimeout(toastTimer);
  toastTimer = undefined;
}

onBeforeUnmount(() => {
  if (toastTimer) window.clearTimeout(toastTimer);
});

function confirmDelete() {
  if (!deletingId.value) return;
  isDeleting.value = true;
  router.delete(`/users/${deletingId.value}` , {
    onSuccess: () => {
      // Server redirect to index carries flash.success; component will remount and display it via onMounted.
      cancelDelete();
    },
    onError: () => {
      showToast('error', 'Failed to delete the user. Please try again.');
    },
    onFinish: () => {
      isDeleting.value = false;
    },
  });
}

// Display server flash messages as toast (e.g., from create/update/destroy)
const lastFlashMessage = ref<string | null>(null);
onMounted(() => {
  const anyPage: any = page.props;
  const msg = (anyPage?.flash?.success as string | undefined) || '';
  if (msg && msg !== lastFlashMessage.value) {
    showToast('success', msg);
    lastFlashMessage.value = msg;
  }
});
// Also react when Inertia updates props without a remount (same component visit)
watch(
  () => (page.props as any)?.flash?.success as string | undefined,
  (val) => {
    const msg = val || '';
    if (msg && msg !== lastFlashMessage.value) {
      showToast('success', msg);
      lastFlashMessage.value = msg;
    }
  }
);

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
  <Head title="Users" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl p-4">
      <!-- Toast -->
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" :duration="3500" />

      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
          <h1 class="text-xl font-semibold">Users</h1>
          <Link
            href="/invitations"
            class="text-sm text-neutral-500 hover:text-neutral-700 hover:underline dark:text-neutral-400 dark:hover:text-neutral-300"
          >
            View Invitations
          </Link>
        </div>
        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
          <div class="flex items-center gap-2">
            <label class="text-xs text-neutral-500">Per page</label>
            <select
              v-model.number="perPage"
              class="rounded-md border px-2 py-2 text-sm dark:bg-neutral-900 dark:text-white"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>
          <div class="relative flex-1 sm:w-72">
            <input
              v-model="search"
              type="text"
              placeholder="Search users..."
              class="w-full rounded-md border px-3 py-2 pl-3 pr-10 text-sm outline-none focus:ring-2 focus:ring-black dark:bg-neutral-900 dark:text-white"
            />
            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400">⌕</span>
          </div>
          <Link
            href="/users-invite"
            class="inline-flex items-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:bg-neutral-800"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            Invite User
          </Link>
          <Link
            href="/users/create"
            class="inline-flex items-center rounded-lg bg-black px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 dark:bg-white dark:text-black dark:hover:bg-neutral-200"
          >
            New User
          </Link>
        </div>
      </div>

      <div v-if="users.data.length === 0" class="rounded-xl border border-sidebar-border/70 p-10 text-center dark:border-sidebar-border">
        <p class="text-sm text-neutral-600 dark:text-neutral-300">No users yet. Create your first user.</p>
      </div>

      <div v-else class="space-y-4">
        <!-- Mobile list (xs) -->
        <div class="space-y-3 sm:hidden">
          <div v-for="u in users.data" :key="u.id" class="rounded-xl border border-sidebar-border/70 p-3 dark:border-neutral-800">
            <div class="flex items-center gap-3">
              <div class="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 font-semibold text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                {{ u.name.charAt(0).toUpperCase() }}
              </div>
              <div class="min-w-0 flex-1">
                <div class="truncate font-medium">{{ u.name }}</div>
                <div class="mt-0.5 truncate text-xs text-neutral-500 dark:text-neutral-400">{{ u.email }}</div>
                <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-xs font-medium" :class="getRoleBadgeColor(u.role)">
                  {{ u.role_display }}
                </span>
              </div>
            </div>
            <div class="mt-3 flex items-center justify-end gap-3 text-sm">
              <Link :href="`/users/${u.id}`" class="hover:underline">View</Link>
              <Link :href="`/users/${u.id}/edit`" class="hover:underline">Edit</Link>
              <button
                v-if="canDeleteUser(u)"
                @click="requestDelete(u.id, u.name)"
                class="text-red-600 hover:underline"
              >Delete</button>
            </div>
          </div>
          <!-- Mobile pagination -->
          <div v-if="users.links?.length" class="flex flex-wrap items-center justify-between gap-2 text-sm">
            <div class="text-neutral-500">Showing latest users</div>
            <div class="flex flex-wrap items-center gap-2">
              <template v-for="(link, idx) in users.links" :key="idx">
                <span v-if="!link.url" class="rounded-md px-3 py-1.5 text-sm text-neutral-400" v-html="link.label" />
                <Link v-else :href="link.url" class="rounded-md px-3 py-1.5 text-sm" :class="link.active ? 'bg-black text-white dark:bg-white dark:text-black' : 'hover:bg-neutral-100 dark:hover:bg-neutral-800'" v-html="link.label" />
              </template>
            </div>
          </div>
        </div>

        <!-- Table (sm and up) -->
        <div class="hidden overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border sm:block">
          <div class="relative w-full overflow-auto">
            <table class="w-full caption-bottom text-sm">
              <thead class="bg-neutral-50 text-left text-xs uppercase text-neutral-500 dark:bg-neutral-900/60 dark:text-neutral-400">
                <tr>
                  <th class="px-4 py-3 font-medium">Name</th>
                  <th class="px-4 py-3 font-medium">Email</th>
                  <th class="px-4 py-3 font-medium">Role</th>
                  <th class="px-4 py-3 font-medium">Created</th>
                  <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="u in users.data" :key="u.id" class="border-t border-sidebar-border/70 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-900/60">
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                      <div class="flex h-9 w-9 items-center justify-center rounded-full bg-neutral-100 font-semibold text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                        {{ u.name.charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <div class="font-medium">{{ u.name }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3">{{ u.email }}</td>
                  <td class="px-4 py-3">
                    <span class="inline-block rounded-full px-2 py-1 text-xs font-medium" :class="getRoleBadgeColor(u.role)">
                      {{ u.role_display }}
                    </span>
                  </td>
                  <td class="px-4 py-3">{{ new Date(u.created_at || '').toLocaleString() }}</td>
                  <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                      <Link :href="`/users/${u.id}`" class="hover:underline">View</Link>
                      <Link :href="`/users/${u.id}/edit`" class="hover:underline">Edit</Link>
                      <button
                        v-if="canDeleteUser(u)"
                        @click="requestDelete(u.id, u.name)"
                        class="text-red-600 hover:underline"
                      >Delete</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="users.links?.length" class="flex items-center justify-between border-t border-sidebar-border/70 p-3 text-sm dark:border-neutral-800">
            <div class="text-neutral-500">Showing latest users</div>
            <div class="flex flex-wrap items-center gap-2">
              <template v-for="(link, idx) in users.links" :key="idx">
                <span v-if="!link.url" class="rounded-md px-3 py-1.5 text-sm text-neutral-400" v-html="link.label" />
                <Link v-else :href="link.url" class="rounded-md px-3 py-1.5 text-sm" :class="link.active ? 'bg-black text-white dark:bg-white dark:text-black' : 'hover:bg-neutral-100 dark:hover:bg-neutral-800'" v-html="link.label" />
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Dialog v-model:open="confirmOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete user</DialogTitle>
          <DialogDescription>
            This action cannot be undone. This will permanently delete
            <span class="font-medium">{{ deletingName }}</span> and remove all their data.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <button @click="cancelDelete" type="button" class="rounded-md border px-4 py-2 text-sm">Cancel</button>
          <button @click="confirmDelete" :disabled="isDeleting" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60">Delete</button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
