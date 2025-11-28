<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import Toast from '@/pages/SiteSettings/Toast.vue';
import { ArrowLeft, Upload } from 'lucide-vue-next';

type ToastType = 'success' | 'error';

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Company Phones', href: '/company-phones' },
  { title: 'Import', href: '/company-phones/import' },
];

const file = ref<File | null>(null);
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const toast = reactive<{ show: boolean; type: ToastType; message: string }>({ show: false, type: 'success', message: '' });

function validateLocal(): boolean {
  const map: Record<string, string> = {};
  if (!file.value) map.file = 'File is required';
  errors.value = map;
  return Object.keys(map).length === 0;
}

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  const f = target.files?.[0] || null;
  file.value = f;
}

async function submit() {
  try {
    processing.value = true;
    errors.value = {};
    if (!validateLocal()) {
      toast.type = 'error'; toast.message = 'Please select a file'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
      return;
    }
    const fd = new FormData();
    fd.append('file', file.value!);
    const res = await axios.post('/api/company-phones/import', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    if (res.data?.success) {
      toast.type = 'success'; toast.message = 'Import complete'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
      window.location.href = '/company-phones';
    }
  } catch (e: any) {
    const msg = e?.response?.data?.errors?.[0] || e?.message || 'Import failed';
    errors.value.general = msg;
    toast.type = 'error'; toast.message = msg; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
  } finally { processing.value = false; }
}
</script>

<template>
  <Head title="Import Company Phones" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-5xl p-4 space-y-6">
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" />

      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Import Company Phones</h1>
        <Link href="/company-phones"><Button variant="secondary"><ArrowLeft class="size-4" /> Back</Button></Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Upload Excel File</CardTitle>
          <CardDescription>Supported formats: .xlsx, .xls, .csv • Columns: Department, Phone Number (+639XXXXXXXXXX), Person In Charge, Position, Extension</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <Label>File *</Label>
            <Input type="file" accept=".xlsx,.xls,.csv" @change="onFileChange" />
            <p v-if="errors.file" class="text-xs text-red-600">{{ errors.file }}</p>
          </div>
          <div class="pt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
            <Button class="w-full sm:w-auto" :disabled="processing" @click="submit"><Upload class="size-4" /> {{ processing ? 'Importing…' : 'Import' }}</Button>
          </div>
          <div v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
  </template>
