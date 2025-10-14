<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch, onMounted, onBeforeUnmount } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog';
import { Database, Download, Upload, Trash2, RefreshCw, HardDrive, Server, Table } from 'lucide-vue-next';
import Toast from './Toast.vue';

interface Backup {
  filename: string;
  size: string;
  size_bytes: number;
  created_at: string;
  timestamp: number;
}

interface DatabaseInfo {
  driver: string;
  connection: string;
  database: string;
  host?: string;
  path?: string;
  size?: string;
  tables: number | string;
}

const page = usePage();
const backups = computed(() => (page.props.backups as Backup[]) ?? []);
const databaseInfo = computed(() => (page.props.databaseInfo as DatabaseInfo) ?? {});

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Database Backup', href: '/database-backup' },
];

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

// Display server flash messages
const lastFlashMessage = ref<string | null>(null);
onMounted(() => {
  const anyPage: any = page.props;
  const msg = (anyPage?.flash?.success as string | undefined) || '';
  if (msg && msg !== lastFlashMessage.value) {
    showToast('success', msg);
    lastFlashMessage.value = msg;
  }
  const errMsg = (anyPage?.errors?.error as string | undefined) || '';
  if (errMsg && errMsg !== lastFlashMessage.value) {
    showToast('error', errMsg);
    lastFlashMessage.value = errMsg;
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

watch(
  () => (page.props as any)?.errors?.error as string | undefined,
  (val) => {
    const msg = val || '';
    if (msg && msg !== lastFlashMessage.value) {
      showToast('error', msg);
      lastFlashMessage.value = msg;
    }
  }
);

// Create backup
const isCreatingBackup = ref(false);

function createBackup() {
  isCreatingBackup.value = true;
  router.post('/database-backup/create', {}, {
    onSuccess: () => {
      // Handled by flash message
    },
    onError: () => {
      showToast('error', 'Failed to create backup. Please try again.');
    },
    onFinish: () => {
      isCreatingBackup.value = false;
    },
  });
}

// Delete backup
const confirmDeleteOpen = ref(false);
const deletingFilename = ref<string | null>(null);
const isDeleting = ref(false);

function requestDelete(filename: string) {
  deletingFilename.value = filename;
  confirmDeleteOpen.value = true;
}

function cancelDelete() {
  confirmDeleteOpen.value = false;
  deletingFilename.value = null;
}

function confirmDelete() {
  if (!deletingFilename.value) return;
  isDeleting.value = true;
  router.delete(`/database-backup/${deletingFilename.value}`, {
    onSuccess: () => {
      cancelDelete();
    },
    onError: () => {
      showToast('error', 'Failed to delete the backup. Please try again.');
    },
    onFinish: () => {
      isDeleting.value = false;
    },
  });
}

// Restore backup
const confirmRestoreOpen = ref(false);
const restoringFilename = ref<string | null>(null);
const isRestoring = ref(false);

function requestRestore(filename: string) {
  restoringFilename.value = filename;
  confirmRestoreOpen.value = true;
}

function cancelRestore() {
  confirmRestoreOpen.value = false;
  restoringFilename.value = null;
}

function confirmRestore() {
  if (!restoringFilename.value) return;
  isRestoring.value = true;
  router.post('/database-backup/restore', { filename: restoringFilename.value }, {
    onSuccess: () => {
      cancelRestore();
    },
    onError: () => {
      showToast('error', 'Failed to restore the backup. Please try again.');
    },
    onFinish: () => {
      isRestoring.value = false;
    },
  });
}

// Download backup
function downloadBackup(filename: string) {
  window.location.href = `/database-backup/download/${filename}`;
}

// Upload and restore
const uploadRestoreOpen = ref(false);
const uploadFile = ref<File | null>(null);
const isUploadRestoring = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);

function openUploadRestore() {
  uploadRestoreOpen.value = true;
  uploadFile.value = null;
}

function cancelUploadRestore() {
  uploadRestoreOpen.value = false;
  uploadFile.value = null;
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
}

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    uploadFile.value = target.files[0];
  }
}

function confirmUploadRestore() {
  if (!uploadFile.value) {
    showToast('error', 'Please select a SQL file to upload.');
    return;
  }

  const formData = new FormData();
  formData.append('backup_file', uploadFile.value);

  isUploadRestoring.value = true;

  router.post('/database-backup/upload-restore', formData, {
    forceFormData: true,
    onSuccess: () => {
      cancelUploadRestore();
    },
    onError: () => {
      showToast('error', 'Failed to upload and restore. Please check the file and try again.');
    },
    onFinish: () => {
      isUploadRestoring.value = false;
    },
  });
}

// Format date
function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>

<template>
  <Head title="Database Backup" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-7xl p-4">
      <!-- Toast -->
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" :duration="3500" />

      <div class="mb-6">
        <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Database Backup</h1>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Manage your database backups and restore points</p>
      </div>

      <!-- Database Info Card -->
      <div class="mb-6 rounded-xl border border-sidebar-border/70 bg-white p-4 dark:border-sidebar-border dark:bg-neutral-900 sm:p-6">
        <div class="mb-4 flex items-center gap-2">
          <Database class="h-5 w-5 text-neutral-600 dark:text-neutral-400" />
          <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Database Information</h2>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-lg bg-neutral-50 p-4 dark:bg-neutral-800/50">
            <div class="flex items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
              <Server class="h-4 w-4" />
              <span>Driver</span>
            </div>
            <div class="mt-2 font-semibold uppercase text-neutral-900 dark:text-white">{{ databaseInfo.driver || 'N/A' }}</div>
          </div>
          <div class="rounded-lg bg-neutral-50 p-4 dark:bg-neutral-800/50">
            <div class="flex items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
              <HardDrive class="h-4 w-4" />
              <span>Database</span>
            </div>
            <div class="mt-2 truncate font-semibold text-neutral-900 dark:text-white" :title="databaseInfo.database">{{ databaseInfo.database || 'N/A' }}</div>
          </div>
          <div class="rounded-lg bg-neutral-50 p-4 dark:bg-neutral-800/50">
            <div class="flex items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
              <Table class="h-4 w-4" />
              <span>Tables</span>
            </div>
            <div class="mt-2 font-semibold text-neutral-900 dark:text-white">{{ databaseInfo.tables }}</div>
          </div>
          <div class="rounded-lg bg-neutral-50 p-4 dark:bg-neutral-800/50">
            <div class="flex items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
              <Database class="h-4 w-4" />
              <span>Size</span>
            </div>
            <div class="mt-2 font-semibold text-neutral-900 dark:text-white">{{ databaseInfo.size || 'N/A' }}</div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mb-6 flex flex-col gap-3 sm:flex-row">
        <button
          @click="createBackup"
          :disabled="isCreatingBackup"
          class="inline-flex items-center justify-center gap-2 rounded-lg bg-black px-4 py-2.5 text-sm font-medium text-white hover:bg-neutral-800 disabled:opacity-60 dark:bg-white dark:text-black dark:hover:bg-neutral-200"
        >
          <Database class="h-4 w-4" />
          <span>{{ isCreatingBackup ? 'Creating Backup...' : 'Create Backup' }}</span>
        </button>
        <button
          @click="openUploadRestore"
          class="inline-flex items-center justify-center gap-2 rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700"
        >
          <Upload class="h-4 w-4" />
          <span>Upload & Restore</span>
        </button>
      </div>

      <!-- Backups List -->
      <div class="rounded-xl border border-sidebar-border/70 bg-white dark:border-sidebar-border dark:bg-neutral-900">
        <div class="border-b border-sidebar-border/70 p-4 dark:border-sidebar-border">
          <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Available Backups</h2>
          <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ backups.length }} backup{{ backups.length !== 1 ? 's' : '' }} available</p>
        </div>

        <div v-if="backups.length === 0" class="p-10 text-center">
          <Database class="mx-auto h-12 w-12 text-neutral-300 dark:text-neutral-700" />
          <p class="mt-4 text-sm text-neutral-600 dark:text-neutral-400">No backups yet. Create your first backup to get started.</p>
        </div>

        <!-- Mobile list -->
        <div v-else class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border sm:hidden">
          <div v-for="backup in backups" :key="backup.filename" class="p-4">
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-neutral-100 dark:bg-neutral-800">
                <Database class="h-5 w-5 text-neutral-600 dark:text-neutral-400" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="truncate font-medium text-neutral-900 dark:text-white" :title="backup.filename">
                  {{ backup.filename }}
                </div>
                <div class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                  {{ formatDate(backup.created_at) }}
                </div>
                <div class="mt-1 text-xs font-medium text-neutral-600 dark:text-neutral-300">
                  {{ backup.size }}
                </div>
              </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <button
                @click="downloadBackup(backup.filename)"
                class="inline-flex items-center gap-1.5 rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700"
              >
                <Download class="h-3.5 w-3.5" />
                Download
              </button>
              <button
                @click="requestRestore(backup.filename)"
                class="inline-flex items-center gap-1.5 rounded-md border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50"
              >
                <RefreshCw class="h-3.5 w-3.5" />
                Restore
              </button>
              <button
                @click="requestDelete(backup.filename)"
                class="inline-flex items-center gap-1.5 rounded-md border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50"
              >
                <Trash2 class="h-3.5 w-3.5" />
                Delete
              </button>
            </div>
          </div>
        </div>

        <!-- Desktop table -->
        <div class="hidden sm:block">
          <div class="relative w-full overflow-auto">
            <table class="w-full caption-bottom text-sm">
              <thead class="bg-neutral-50 text-left text-xs uppercase text-neutral-500 dark:bg-neutral-900/60 dark:text-neutral-400">
                <tr>
                  <th class="px-4 py-3 font-medium">Backup File</th>
                  <th class="px-4 py-3 font-medium">Created At</th>
                  <th class="px-4 py-3 font-medium">Size</th>
                  <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="backup in backups" :key="backup.filename" class="border-t border-sidebar-border/70 hover:bg-neutral-50 dark:border-neutral-800 dark:hover:bg-neutral-900/60">
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                      <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-neutral-100 dark:bg-neutral-800">
                        <Database class="h-4 w-4 text-neutral-600 dark:text-neutral-400" />
                      </div>
                      <div class="truncate font-medium" :title="backup.filename">{{ backup.filename }}</div>
                    </div>
                  </td>
                  <td class="px-4 py-3">{{ formatDate(backup.created_at) }}</td>
                  <td class="px-4 py-3">
                    <span class="rounded-full bg-neutral-100 px-2 py-1 text-xs font-medium text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                      {{ backup.size }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <button
                        @click="downloadBackup(backup.filename)"
                        class="inline-flex items-center gap-1.5 rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700"
                        title="Download"
                      >
                        <Download class="h-3.5 w-3.5" />
                        <span class="hidden lg:inline">Download</span>
                      </button>
                      <button
                        @click="requestRestore(backup.filename)"
                        class="inline-flex items-center gap-1.5 rounded-md border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50"
                        title="Restore"
                      >
                        <RefreshCw class="h-3.5 w-3.5" />
                        <span class="hidden lg:inline">Restore</span>
                      </button>
                      <button
                        @click="requestDelete(backup.filename)"
                        class="inline-flex items-center gap-1.5 rounded-md border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 dark:border-red-700 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50"
                        title="Delete"
                      >
                        <Trash2 class="h-3.5 w-3.5" />
                        <span class="hidden lg:inline">Delete</span>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Dialog v-model:open="confirmDeleteOpen">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>Delete backup</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete this backup file? This action cannot be undone.
            <div class="mt-2 rounded-md bg-neutral-100 p-2 dark:bg-neutral-800">
              <code class="break-all text-xs text-neutral-700 dark:text-neutral-300">{{ deletingFilename }}</code>
            </div>
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <button @click="cancelDelete" type="button" class="rounded-md border px-4 py-2 text-sm hover:bg-neutral-50 dark:hover:bg-neutral-800">Cancel</button>
          <button @click="confirmDelete" :disabled="isDeleting" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60">Delete</button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Restore Confirmation Modal -->
    <Dialog v-model:open="confirmRestoreOpen">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>Restore database</DialogTitle>
          <DialogDescription>
            <div class="space-y-2">
              <p class="text-red-600 dark:text-red-400 font-medium">⚠️ Warning: This will replace your current database!</p>
              <p>All current data will be lost and replaced with the backup data. Make sure you have a recent backup before proceeding.</p>
              <div class="mt-2 rounded-md bg-neutral-100 p-2 dark:bg-neutral-800">
                <code class="break-all text-xs text-neutral-700 dark:text-neutral-300">{{ restoringFilename }}</code>
              </div>
            </div>
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <button @click="cancelRestore" type="button" class="rounded-md border px-4 py-2 text-sm hover:bg-neutral-50 dark:hover:bg-neutral-800">Cancel</button>
          <button @click="confirmRestore" :disabled="isRestoring" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60">
            {{ isRestoring ? 'Restoring...' : 'Restore' }}
          </button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Upload & Restore Modal -->
    <Dialog v-model:open="uploadRestoreOpen">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>Upload & Restore</DialogTitle>
          <DialogDescription>
            <div class="space-y-2">
              <p class="text-red-600 dark:text-red-400 font-medium">⚠️ Warning: This will replace your current database!</p>
              <p>Upload a SQL backup file to restore your database. All current data will be lost.</p>
            </div>
          </DialogDescription>
        </DialogHeader>
        <div class="py-4">
          <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-2">
            Select SQL File
          </label>
          <input
            ref="fileInputRef"
            type="file"
            accept=".sql"
            @change="handleFileChange"
            class="block w-full text-sm text-neutral-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-neutral-100 file:text-neutral-700 hover:file:bg-neutral-200 dark:file:bg-neutral-800 dark:file:text-neutral-300 dark:hover:file:bg-neutral-700"
          />
          <p v-if="uploadFile" class="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
            Selected: {{ uploadFile.name }} ({{ (uploadFile.size / 1024 / 1024).toFixed(2) }} MB)
          </p>
        </div>
        <DialogFooter>
          <button @click="cancelUploadRestore" type="button" class="rounded-md border px-4 py-2 text-sm hover:bg-neutral-50 dark:hover:bg-neutral-800">Cancel</button>
          <button @click="confirmUploadRestore" :disabled="isUploadRestoring || !uploadFile" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60">
            {{ isUploadRestoring ? 'Uploading...' : 'Upload & Restore' }}
          </button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
