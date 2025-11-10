<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, router, Link } from '@inertiajs/vue3';
import { reactive, onBeforeUnmount } from 'vue';
import Toast from './Toast.vue';

const page = usePage();
const inventory = (page.props as any).inventory;

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Inventories', href: '/it-inventories' },
  { title: 'Edit', href: `/it-inventories/${inventory.id}/edit` },
];

const form = useForm({
  _method: 'PUT',
  inventory_name: inventory.inventory_name || '',
  descriptions: inventory.descriptions || '',
  accountable_by_name: inventory.accountable_by_name || '',
  remarks: inventory.remarks || '',
});

// Toast state
type ToastType = 'success' | 'error';
const toast = reactive<{ show: boolean; type: ToastType; message: string }>({ show: false, type: 'success', message: '' });
let toastTimer: number | undefined;
function showToast(type: ToastType, message: string, duration = 3500) {
  toast.show = true;
  toast.type = type;
  toast.message = message;
  if (toastTimer) window.clearTimeout(toastTimer);
  toastTimer = window.setTimeout(() => (toast.show = false), duration);
}
onBeforeUnmount(() => { if (toastTimer) window.clearTimeout(toastTimer); });

function submit() {
  form.post(`/it-inventories/${inventory.id}`, {
    onSuccess: () => {
      showToast('success', 'Inventory updated successfully.');
      router.visit('/it-inventories');
    },
    onError: () => {
      showToast('error', 'Please fix the errors and try again.');
    },
  });
}

function cancel() { router.visit('/it-inventories'); }

// client-side limits
const limits = {
  inventory_name: 255,
  descriptions: 5000,
  accountable_by_name: 255,
  remarks: 2000,
};

</script>

<template>
  <Head title="Edit Inventory" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-3xl p-4">
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" :duration="3500" />

      <h1 class="mb-4 text-xl font-semibold">Edit Inventory</h1>

      <form @submit.prevent="submit" class="space-y-5">
        <div>
          <label for="inventory_name" class="mb-1 block text-sm font-medium">Inventory Name</label>
          <input id="inventory_name" v-model="form.inventory_name" type="text" :maxlength="limits.inventory_name" class="w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-black dark:bg-neutral-900 dark:text-white" />
          <div v-if="form.errors.inventory_name" class="mt-1 text-sm text-red-600">{{ form.errors.inventory_name }}</div>
        </div>

        <div>
          <label for="accountable_by_name" class="mb-1 block text-sm font-medium">Accountable By</label>
          <input id="accountable_by_name" v-model="form.accountable_by_name" type="text" :maxlength="limits.accountable_by_name" class="w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-black dark:bg-neutral-900 dark:text-white" />
          <div v-if="form.errors.accountable_by_name" class="mt-1 text-sm text-red-600">{{ form.errors.accountable_by_name }}</div>
        </div>

        <div>
          <label for="descriptions" class="mb-1 block text-sm font-medium">Descriptions</label>
          <textarea id="descriptions" v-model="form.descriptions" :maxlength="limits.descriptions" rows="6" class="w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-black dark:bg-neutral-900 dark:text-white"></textarea>
          <div v-if="form.errors.descriptions" class="mt-1 text-sm text-red-600">{{ form.errors.descriptions }}</div>
        </div>

        <div>
          <label for="remarks" class="mb-1 block text-sm font-medium">Remarks</label>
          <textarea id="remarks" v-model="form.remarks" :maxlength="limits.remarks" rows="4" class="w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-black dark:bg-neutral-900 dark:text-white"></textarea>
          <div v-if="form.errors.remarks" class="mt-1 text-sm text-red-600">{{ form.errors.remarks }}</div>
        </div>

        <div class="flex items-center justify-end gap-3">
          <button type="button" @click="cancel" class="rounded-md border px-4 py-2 text-sm">Cancel</button>
          <button type="submit" :disabled="form.processing" class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white disabled:opacity-60 dark:bg-white dark:text-black">Save changes</button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>