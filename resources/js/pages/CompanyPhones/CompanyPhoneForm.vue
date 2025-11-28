<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import Toast from '@/pages/SiteSettings/Toast.vue';
import { ArrowLeft } from 'lucide-vue-next';

type ToastType = 'success' | 'error';

const props = defineProps<{ phoneRecord?: { id: number; department: string; phone_number: string; person_in_charge: string; position: string; extension?: string | null } | null }>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Company Phones', href: '/company-phones' },
  { title: props.phoneRecord ? 'Edit' : 'Create', href: props.phoneRecord ? `/company-phones/${props.phoneRecord.id}/edit` : '/company-phones/create' },
];

const form = reactive({
  department: props.phoneRecord?.department || '',
  phone_number: props.phoneRecord?.phone_number || '',
  person_in_charge: props.phoneRecord?.person_in_charge || '',
  position: props.phoneRecord?.position || '',
  extension: props.phoneRecord?.extension || '',
});
const errors = ref<Record<string, string>>({});
const processing = ref(false);

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
  if (!form.department?.trim()) map.department = 'Department is required';
  if (!form.phone_number?.trim()) map.phone_number = 'Phone number is required';
  else if (!/^\+639\d{10}$/.test(form.phone_number)) map.phone_number = 'Phone must be +639 followed by 10 digits';
  if (!form.person_in_charge?.trim()) map.person_in_charge = 'Person in charge is required';
  if (!form.position?.trim()) map.position = 'Position is required';
  errors.value = map;
  return Object.keys(map).length === 0;
}

const toast = reactive<{ show: boolean; type: ToastType; message: string }>({ show: false, type: 'success', message: '' });

async function submit() {
  try {
    processing.value = true;
    errors.value = {};
    const isEdit = !!props.phoneRecord?.id;
    if (!validateLocal()) {
      toast.type = 'error'; toast.message = 'Please fix validation errors'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
      return;
    }
    const payload = { ...form } as any;
    const url = isEdit ? `/api/company-phones/${props.phoneRecord!.id}` : '/api/company-phones';
    const res = isEdit ? await axios.put(url, payload) : await axios.post(url, payload);
    if (res.data?.success) {
      toast.type = 'success'; toast.message = isEdit ? 'Phone updated' : 'Phone created'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
      window.location.href = '/company-phones';
    }
  } catch (e: any) {
    const map = normalizeErrors(e);
    errors.value = map;
    toast.type = 'error'; toast.message = map.general || 'Failed'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
  } finally { processing.value = false; }
}

function resetForm() {
  Object.assign(form, {
    department: props.phoneRecord?.department || '',
    phone_number: props.phoneRecord?.phone_number || '',
    person_in_charge: props.phoneRecord?.person_in_charge || '',
    position: props.phoneRecord?.position || '',
    extension: props.phoneRecord?.extension || '',
  });
  errors.value = {};
}

const isEdit = computed(() => !!props.phoneRecord?.id);
</script>

<template>
  <Head :title="isEdit ? `Edit Phone` : `Create Phone`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-5xl p-4 space-y-6">
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" />

      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">{{ isEdit ? 'Edit Phone' : 'Create Phone' }}</h1>
        <Link href="/company-phones"><Button variant="secondary"><ArrowLeft class="size-4" /> Back</Button></Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Details</CardTitle>
          <CardDescription>{{ isEdit ? 'Update phone information' : 'Enter phone information' }}</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label>Department *</Label>
              <Input v-model="form.department" placeholder="e.g. IT Department" maxlength="255" aria-invalid="!!errors.department" />
              <p v-if="errors.department" class="text-xs text-red-600">{{ errors.department }}</p>
            </div>
            <div class="space-y-2">
              <Label>Phone Number *</Label>
              <Input v-model="form.phone_number" placeholder="e.g. +6391234567890" maxlength="14" aria-invalid="!!errors.phone_number" />
              <p v-if="errors.phone_number" class="text-xs text-red-600">{{ errors.phone_number }}</p>
            </div>
            <div class="space-y-2">
              <Label>Person In Charge *</Label>
              <Input v-model="form.person_in_charge" placeholder="e.g. John Doe" maxlength="255" aria-invalid="!!errors.person_in_charge" />
              <p v-if="errors.person_in_charge" class="text-xs text-red-600">{{ errors.person_in_charge }}</p>
            </div>
            <div class="space-y-2">
              <Label>Position *</Label>
              <Input v-model="form.position" placeholder="e.g. IT Manager" maxlength="255" aria-invalid="!!errors.position" />
              <p v-if="errors.position" class="text-xs text-red-600">{{ errors.position }}</p>
            </div>
            <div class="space-y-2">
              <Label>Extension</Label>
              <Input v-model="form.extension" placeholder="e.g. 123" maxlength="20" aria-invalid="!!errors.extension" />
              <p v-if="errors.extension" class="text-xs text-red-600">{{ errors.extension }}</p>
            </div>
          </div>
          <div class="pt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
            <Button class="w-full sm:w-auto" :disabled="processing" @click="submit">{{ processing ? (isEdit ? 'Updating…' : 'Saving…') : (isEdit ? 'Update' : 'Save') }}</Button>
            <Button class="w-full sm:w-auto" variant="outline" :disabled="processing" @click="resetForm">Reset</Button>
          </div>
          <div v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
