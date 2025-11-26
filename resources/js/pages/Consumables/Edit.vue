<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import Toast from '@/pages/SiteSettings/Toast.vue';
import { ArrowLeft } from 'lucide-vue-next';

type ToastType = 'success' | 'error';

const props = defineProps<{ id: number }>();

const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'IT Consumables', href: '/consumables' },
  { title: 'Edit', href: `/consumables/${props.id}/edit` },
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

const toast = reactive<{ show: boolean; type: ToastType; message: string }>({ show: false, type: 'success', message: '' });

async function fetch() {
  const res = await axios.get(`/api/consumables/${props.id}`);
  if (res.data?.success) Object.assign(form, res.data.data);
}

onMounted(fetch);

async function submit() {
  try {
    processing.value = true;
    errors.value = {};
    const res = await axios.put(`/api/consumables/${props.id}`, form);
    if (res.data?.success) { toast.type = 'success'; toast.message = 'Updated'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000); }
  } catch (e: any) {
    const errs = e?.response?.data?.errors || [];
    const map: Record<string, string> = {};
    if (Array.isArray(errs)) { map.general = errs[0] || 'Validation failed'; }
    errors.value = map;
    toast.type = 'error'; toast.message = map.general || 'Failed'; toast.show = true; window.setTimeout(() => (toast.show = false), 3000);
  } finally { processing.value = false; }
}
</script>

<template>
  <Head title="Edit Consumable" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full max-w-3xl p-4 space-y-6">
      <Toast v-model="toast.show" :type="toast.type" :message="toast.message" />

      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Edit Consumable</h1>
        <Link href="/consumables"><Button variant="secondary"><ArrowLeft class="size-4" /> Back</Button></Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Details</CardTitle>
          <CardDescription>Update consumable information</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label>Name</Label>
              <Input v-model="form.consumable_name" placeholder="Name" />
            </div>
            <div class="space-y-2">
              <Label>Brand</Label>
              <Input v-model="form.consumable_brand" placeholder="Brand" />
            </div>
            <div class="sm:col-span-2 space-y-2">
              <Label>Description</Label>
              <Input v-model="form.consumable_description" placeholder="Description" />
            </div>
            <div class="space-y-2">
              <Label>Quantity</Label>
              <Input type="number" v-model.number="form.current_quantity" min="0" />
            </div>
            <div class="space-y-2">
              <Label>Threshold</Label>
              <Input type="number" v-model.number="form.threshold_limit" min="0" />
            </div>
            <div class="space-y-2">
              <Label>Unit</Label>
              <Input v-model="form.unit" placeholder="pcs" />
            </div>
          </div>
          <div class="pt-4">
            <Button class="w-full sm:w-auto" :disabled="processing" @click="submit">Save</Button>
          </div>
          <div v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

