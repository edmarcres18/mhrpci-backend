<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import Toast from '@/pages/SiteSettings/Toast.vue';
import { ArrowLeft } from 'lucide-vue-next';

type ToastType = 'success' | 'error';

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Consumables', href: '/consumables' },
  { title: 'Create', href: '/consumables/create' },
];

const form = reactive({
  consumable_name: '',
  consumable_description: '',
  consumable_brand: '',
  current_quantity: 0,
  threshold_limit: 0,
  unit: 'pcs',
});
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const page = usePage();
const canManage = computed(() => (page.props.auth as any)?.canDeleteProducts ?? false);

function normalizeErrors(e: any): Record<string, string> {
  const out: Record<string, string> = {};
  const errs = e?.response?.data?.errors;
  if (errs && typeof errs === 'object' && !Array.isArray(errs)) {
    for (const k in errs) {
      const v = (errs as any)[k];
      out[k] = Array.isArray(v) ? (v[0] ?? 'Invalid') : String(v ?? 'Invalid');
    }
  } else if (Array.isArray(errs)) {
    out.general = errs[0] || 'Validation failed';
  } else if (typeof e?.message === 'string') {
    out.general = e.message;
  }
  return out;
}

function validateLocal(): boolean {
  const map: Record<string, string> = {};
  if (!form.consumable_name?.trim()) map.consumable_name = 'Name is required';
  if (form.current_quantity < 0) map.current_quantity = 'Quantity must be 0 or more';
  if (form.threshold_limit < 0) map.threshold_limit = 'Threshold must be 0 or more';
  if (!form.unit?.trim()) map.unit = 'Unit is required';
  errors.value = map;
  return Object.keys(map).length === 0;
}

const toast = reactive<{ show: boolean; type: ToastType; message: string }>({ show: false, type: 'success', message: '' });

async function submit() {
  try {
    processing.value = true;
    errors.value = {};
    if (!validateLocal()) {
      toast.type = 'error'; toast.message = 'Please fix validation errors'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
      return;
    }
    const res = await axios.post('/api/consumables', form);
    if (res.data?.success) {
      toast.type = 'success'; toast.message = 'Consumable created'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
      window.location.href = '/consumables';
    }
  } catch (e: any) {
    const map = normalizeErrors(e);
    errors.value = map;
    toast.type = 'error'; toast.message = map.general || 'Failed'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
  } finally { processing.value = false; }
}

function resetForm() {
  Object.assign(form, {
    consumable_name: '',
    consumable_description: '',
    consumable_brand: '',
    current_quantity: 0,
    threshold_limit: 0,
    unit: 'pcs',
  });
  errors.value = {};
}
</script>

<template>
  <Head title="Create Consumable" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-5xl p-4 space-y-6">
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" />

      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Create Consumable</h1>
        <Link href="/consumables"><Button variant="secondary"><ArrowLeft class="size-4" /> Back</Button></Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Details</CardTitle>
          <CardDescription>Enter consumable information</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label>Name *</Label>
              <Input v-model="form.consumable_name" placeholder="e.g. Ink Cartridge 21XL" maxlength="255" aria-invalid="!!errors.consumable_name" />
              <p v-if="errors.consumable_name" class="text-xs text-red-600">{{ errors.consumable_name }}</p>
            </div>
            <div class="space-y-2">
              <Label>Brand</Label>
              <Input v-model="form.consumable_brand" placeholder="e.g. HP" maxlength="255" />
              <p v-if="errors.consumable_brand" class="text-xs text-red-600">{{ errors.consumable_brand }}</p>
            </div>
            <div class="sm:col-span-2 space-y-2">
              <Label>Description</Label>
              <Input v-model="form.consumable_description" placeholder="Short description" />
              <p v-if="errors.consumable_description" class="text-xs text-red-600">{{ errors.consumable_description }}</p>
            </div>
            <div class="space-y-2">
              <Label>Quantity</Label>
              <Input type="number" v-model.number="form.current_quantity" min="0" step="1" />
              <p v-if="errors.current_quantity" class="text-xs text-red-600">{{ errors.current_quantity }}</p>
            </div>
            <div class="space-y-2">
              <Label>Threshold</Label>
              <Input type="number" v-model.number="form.threshold_limit" min="0" step="1" />
              <p v-if="errors.threshold_limit" class="text-xs text-red-600">{{ errors.threshold_limit }}</p>
            </div>
            <div class="space-y-2">
              <Label>Unit *</Label>
              <select v-model="form.unit" class="rounded-md border px-2 py-2 text-sm dark:bg-neutral-900 dark:text-white">
                <option value="pcs">pcs</option>
                <option value="box">box</option>
                <option value="pack">pack</option>
                <option value="roll">roll</option>
                <option value="bottle">bottle</option>
                <option value="bag">bag</option>
                <option value="set">set</option>
                <option value="meter">meter</option>
                <option value="liter">liter</option>
              </select>
              <p v-if="errors.unit" class="text-xs text-red-600">{{ errors.unit }}</p>
            </div>
          </div>
          <div class="pt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
            <Button class="w-full sm:w-auto" :disabled="processing || !canManage" @click="submit">{{ processing ? 'Saving…' : 'Save' }}</Button>
            <Button class="w-full sm:w-auto" variant="outline" :disabled="processing" @click="resetForm">Reset</Button>
            <span v-if="!canManage" class="text-xs text-muted-foreground">You don’t have permission to create items.</span>
          </div>
          <div v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
