<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
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

interface Invitation {
  id: number;
  email: string;
  role: string;
  role_display: string;
  used: boolean;
  expires_at: string;
  invited_by: string;
  is_expired: boolean;
  is_valid: boolean;
  created_at: string;
}

interface Pagination<T> {
  data: T[];
  links: { url: string | null; label: string; active: boolean }[];
}

const page = usePage();
const invitations = computed(() => (page.props.invitations as Pagination<Invitation>) ?? { data: [], links: [] });
const initialFilters = computed(() => (page.props as any)?.filters ?? { search: '', perPage: 10 });
const search = ref<string>(initialFilters.value.search || '');
const perPage = ref<number>(Number(initialFilters.value.perPage) || 10);
let searchTimer: number | undefined;

watch(
  () => search.value,
  (val) => {
    if (searchTimer) window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
      router.get('/invitations', { search: val || undefined, perPage: perPage.value }, { preserveState: true, preserveScroll: true, replace: true });
    }, 350);
  }
);

watch(
  () => perPage.value,
  (val) => {
    router.get('/invitations', { search: search.value || undefined, perPage: val }, { preserveState: true, preserveScroll: true, replace: true });
  }
);

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Users', href: '/users' },
  { title: 'Invitations', href: '/invitations' },
];

// Delete confirmation modal state
const confirmOpen = ref(false);
const deletingId = ref<number | null>(null);
const deletingEmail = ref<string>('');
const isDeleting = ref(false);

function requestCancel(id: number, email: string) {
  deletingId.value = id;
  deletingEmail.value = email;
  confirmOpen.value = true;
}

function cancelDelete() {
  confirmOpen.value = false;
  deletingId.value = null;
  deletingEmail.value = '';
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

function confirmCancel() {
  if (!deletingId.value) return;
  isDeleting.value = true;
  router.delete(`/invitations/${deletingId.value}`, {
    onSuccess: () => {
      cancelDelete();
    },
    onError: () => {
      showToast('error', 'Failed to cancel the invitation. Please try again.');
    },
    onFinish: () => {
      isDeleting.value = false;
    },
  });
}

function resendInvitation(id: number) {
  router.post(`/invitations/${id}/resend`, {}, {
    onSuccess: () => {
      showToast('success', 'Invitation resent successfully!');
    },
    onError: () => {
      showToast('error', 'Failed to resend invitation. Please try again.');
    },
  });
}

// Display server flash messages as toast
const lastFlashMessage = ref<string | null>(null);
onMounted(() => {
  const anyPage: any = page.props;
  const msg = (anyPage?.flash?.success as string | undefined) || '';
  if (msg && msg !== lastFlashMessage.value) {
    showToast('success', msg);
    lastFlashMessage.value = msg;
  }
});
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

function getStatusBadgeColor(invitation: Invitation) {
  if (invitation.used) {
    return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200';
  }
  if (invitation.is_expired) {
    return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200';
  }
  return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200';
}

function getStatusText(invitation: Invitation) {
  if (invitation.used) return 'Used';
  if (invitation.is_expired) return 'Expired';
  return 'Pending';
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleString();
}
</script>

<template>
  <Head title="Invitations" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl p-4">
      <!-- Toast -->
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" :duration="3500" />

      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-xl font-semibold">User Invitations</h1>
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
              placeholder="Search invitations..."
              class="w-full rounded-md border px-3 py-2 pl-3 pr-10 text-sm outline-none focus:ring-2 focus:ring-black dark:bg-neutral-900 dark:text-white"
            />
            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400">⌕</span>
          </div>
          <Link
            href="/users-invite"
            class="inline-flex items-center gap-2 rounded-lg bg-black px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 dark:bg-white dark:text-black dark:hover:bg-neutral-200"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New Invitation
          </Link>
        </div>
      </div>

      <div v-if="invitations.data.length === 0" class="rounded-xl border border-sidebar-border/70 p-10 text-center dark:border-sidebar-border">
        <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">No invitations found.</p>
        <Link href="/users-invite" class="mt-4 inline-block text-sm text-primary hover:underline">Send your first invitation</Link>
      </div>

      <div v-else class="space-y-4">
        <!-- Mobile list (xs) -->
        <div class="space-y-3 sm:hidden">
          <div v-for="inv in invitations.data" :key="inv.id" class="rounded-xl border border-sidebar-border/70 p-3 dark:border-neutral-800">
            <div class="flex items-start justify-between">
              <div class="min-w-0 flex-1">
                <div class="truncate font-medium">{{ inv.email }}</div>
                <div class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                  Invited by: {{ inv.invited_by }} • Role: {{ inv.role_display }}
                </div>
                <span class="mt-2 inline-block rounded-full px-2 py-0.5 text-xs font-medium" :class="getStatusBadgeColor(inv)">
                  {{ getStatusText(inv) }}
                </span>
              </div>
            </div>
            <div class="mt-3 text-xs text-neutral-500 dark:text-neutral-400">
              <div>Sent: {{ formatDate(inv.created_at) }}</div>
              <div>Expires: {{ formatDate(inv.expires_at) }}</div>
            </div>
            <div v-if="inv.is_valid" class="mt-3 flex items-center gap-3 text-sm">
              <button
                @click="resendInvitation(inv.id)"
                class="hover:underline"
              >Resend</button>
              <button
                @click="requestCancel(inv.id, inv.email)"
                class="text-red-600 hover:underline"
              >Cancel</button>
            </div>
          </div>
          <!-- Mobile pagination -->
          <div v-if="invitations.links?.length" class="flex flex-wrap items-center justify-between gap-2 text-sm">
            <div class="text-neutral-500">Showing latest invitations</div>
            <div class="flex flex-wrap items-center gap-2">
              <template v-for="(link, idx) in invitations.links" :key="idx">
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
                  <th class="px-4 py-3 font-medium">Email</th>
                  <th class="px-4 py-3 font-medium">Role</th>
                  <th class="px-4 py-3 font-medium">Status</th>
                  <th class="px-4 py-3 font-medium">Invited By</th>
                  <th class="px-4 py-3 font-medium">Sent</th>
                  <th class="px-4 py-3 font-medium">Expires</th>
                  <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="inv in invitations.data" :key="inv.id" class="border-t border-sidebar-border/70 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-900/60">
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <svg class="h-4 w-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                      </svg>
                      <span class="font-medium">{{ inv.email }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <span class="inline-block rounded-full px-2 py-1 text-xs font-medium bg-neutral-100 text-neutral-800 dark:bg-neutral-900/30 dark:text-neutral-200">
                      {{ inv.role_display }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <span class="inline-block rounded-full px-2 py-1 text-xs font-medium" :class="getStatusBadgeColor(inv)">
                      {{ getStatusText(inv) }}
                    </span>
                  </td>
                  <td class="px-4 py-3">{{ inv.invited_by }}</td>
                  <td class="px-4 py-3">{{ formatDate(inv.created_at) }}</td>
                  <td class="px-4 py-3">{{ formatDate(inv.expires_at) }}</td>
                  <td class="px-4 py-3 text-right">
                    <div v-if="inv.is_valid" class="flex items-center justify-end gap-3">
                      <button
                        @click="resendInvitation(inv.id)"
                        class="hover:underline"
                      >Resend</button>
                      <button
                        @click="requestCancel(inv.id, inv.email)"
                        class="text-red-600 hover:underline"
                      >Cancel</button>
                    </div>
                    <div v-else class="text-neutral-400">-</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="invitations.links?.length" class="flex items-center justify-between border-t border-sidebar-border/70 p-3 text-sm dark:border-neutral-800">
            <div class="text-neutral-500">Showing latest invitations</div>
            <div class="flex flex-wrap items-center gap-2">
              <template v-for="(link, idx) in invitations.links" :key="idx">
                <span v-if="!link.url" class="rounded-md px-3 py-1.5 text-sm text-neutral-400" v-html="link.label" />
                <Link v-else :href="link.url" class="rounded-md px-3 py-1.5 text-sm" :class="link.active ? 'bg-black text-white dark:bg-white dark:text-black' : 'hover:bg-neutral-100 dark:hover:bg-neutral-800'" v-html="link.label" />
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <Dialog v-model:open="confirmOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Cancel invitation</DialogTitle>
          <DialogDescription>
            This action cannot be undone. This will permanently cancel the invitation for
            <span class="font-medium">{{ deletingEmail }}</span> and they will no longer be able to register using this link.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <button @click="cancelDelete" type="button" class="rounded-md border px-4 py-2 text-sm">Cancel</button>
          <button @click="confirmCancel" :disabled="isDeleting" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60">Delete</button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
