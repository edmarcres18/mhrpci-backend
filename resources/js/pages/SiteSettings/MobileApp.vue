<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Upload, Info, ShieldCheck, Smartphone, Download, ExternalLink } from 'lucide-vue-next';

type ApkMeta = {
    version: string | null;
    download_url: string;
    size_human: string | null;
    uploaded_at: string | null;
    uploaded_by: { id: number; name: string; email: string } | null;
    notes: string | null;
};

const page = usePage();

const appUrl = (import.meta as any).env?.APP_URL || (import.meta as any).env?.VITE_APP_URL || '';
const cleanAppUrl = appUrl ? appUrl.replace(/\/+$/, '') : '';
const fallbackApkUrl = computed(() =>
    cleanAppUrl ? `${cleanAppUrl}/public/mobile_app/ITScanner.apk` : '/mobile_app/ITScanner.apk'
);

const apk = computed<ApkMeta>(() => {
    const meta = (page.props.apk as ApkMeta) || {};
    return {
        version: meta.version ?? null,
        download_url: meta.download_url || fallbackApkUrl.value,
        size_human: meta.size_human ?? null,
        uploaded_at: meta.uploaded_at ?? null,
        uploaded_by: meta.uploaded_by ?? null,
        notes: meta.notes ?? null,
    };
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Site Settings', href: '/site-settings' },
    { title: 'Mobile App (Android)', href: '/mobile-app/manage' },
];

const form = useForm({
    version: '',
    notes: '',
    apk_file: null as File | null,
});

const fileInput = ref<HTMLInputElement | null>(null);
const uploadModalOpen = ref(false);
const uploadProgress = ref<number | null>(null);
const uploading = computed(() => form.processing);

const submit = () => {
    if (!form.version.trim()) {
        form.setError('version', 'Version is required');
        return;
    }
    if (!form.apk_file) {
        form.setError('apk_file', 'APK file is required');
        return;
    }

    uploadProgress.value = null;
    form.post('/mobile-app/upload', {
        forceFormData: true,
        onProgress: (event) => {
            if (event?.progress) {
                const pct = Math.round(event.progress * 100);
                uploadProgress.value = pct;
            }
        },
        onSuccess: () => {
            form.reset('apk_file');
            if (fileInput.value) fileInput.value.value = '';
            uploadProgress.value = null;
            uploadModalOpen.value = false;
        },
        onFinish: () => {
            uploadProgress.value = null;
        },
    });
};

const handleFile = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;
    if (!file.name.toLowerCase().endsWith('.apk')) {
        form.setError('apk_file', 'Only .apk files are allowed');
        return;
    }
    form.clearErrors('apk_file');
    form.apk_file = file;
};
</script>

<template>
    <Head title="Mobile App (Android)" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-8 p-4 sm:p-6 lg:p-10">
            <div class="grid gap-4 md:grid-cols-1 lg:grid-cols-[1.2fr_0.8fr] lg:items-start">
                <div class="space-y-3">
                    <div class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                        <ShieldCheck class="h-4 w-4" />
                        System Admin — Android builds
                    </div>
                    <div class="space-y-2">
                        <h1 class="text-3xl font-semibold leading-tight sm:text-4xl">Mobile App (Android)</h1>
                        <p class="text-sm text-muted-foreground sm:text-base leading-relaxed">
                            Upload signed Android APKs with version labels. The latest upload is always available at
                            <code class="rounded bg-muted px-1 py-0.5 text-xs">/mobile_app/ITScanner.apk</code> and powers the Mobile Scanner page and QR.
                        </p>
                    </div>
                    <Card class="border border-muted-foreground/20 shadow-sm">
                        <CardHeader class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Smartphone class="h-5 w-5" />
                                    Latest Android build
                                </CardTitle>
                                <CardDescription>Snapshot of the currently published APK.</CardDescription>
                            </div>
                            <Dialog v-model:open="uploadModalOpen">
                                <DialogTrigger as-child>
                                    <Button size="sm" class="gap-2">
                                        <Upload class="h-4 w-4" />
                                        Upload new APK
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="max-w-xl w-[94vw] sm:w-[520px]">
                                    <DialogHeader>
                                        <DialogTitle>Upload new Android APK</DialogTitle>
                                        <DialogDescription>
                                            Provide version (e.g., 1.0.3) and upload the signed APK (up to 1 GB).
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div class="space-y-4">
                                        <Alert variant="default" class="bg-muted">
                                            <Info class="h-4 w-4" />
                                            <AlertTitle>Android only</AlertTitle>
                                            <AlertDescription>iOS/TestFlight is invite-only and not distributed here.</AlertDescription>
                                        </Alert>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <Label for="version">Version *</Label>
                                                <Input
                                                    id="version"
                                                    v-model="form.version"
                                                    placeholder="e.g., 1.0.3"
                                                    :disabled="form.processing"
                                                />
                                                <p v-if="form.errors.version" class="text-sm text-destructive">{{ form.errors.version }}</p>
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="notes">Notes (optional)</Label>
                                                <textarea
                                                    id="notes"
                                                    v-model="form.notes"
                                                    rows="3"
                                                    placeholder="Release highlights, fixes, device notes"
                                                    :disabled="form.processing"
                                                    class="flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                                />
                                                <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="apk_file">APK file *</Label>
                                            <Input
                                                id="apk_file"
                                                type="file"
                                                accept=".apk"
                                                @change="handleFile"
                                                ref="fileInput"
                                                :disabled="uploading"
                                            />
                                            <p class="text-xs text-muted-foreground">Accepted: .apk • Max 1 GB • Android only.</p>
                                            <p v-if="form.errors.apk_file" class="text-sm text-destructive">{{ form.errors.apk_file }}</p>
                                        </div>

                                        <div v-if="uploading" class="space-y-2 rounded-md border border-muted/40 bg-muted/40 p-3">
                                            <div class="flex items-center justify-between text-xs font-medium text-muted-foreground">
                                                <span>Uploading APK...</span>
                                                <span v-if="uploadProgress !== null">{{ uploadProgress }}%</span>
                                            </div>
                                            <div class="relative h-2 overflow-hidden rounded bg-muted">
                                                <div
                                                    class="h-full bg-primary transition-all duration-300"
                                                    :style="{ width: `${uploadProgress ?? 0}%` }"
                                                    role="progressbar"
                                                    :aria-valuenow="uploadProgress ?? 0"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100"
                                                />
                                            </div>
                                            <p class="text-xs text-muted-foreground">Keep this window open until the upload finishes.</p>
                                        </div>
                                    </div>
                                    <DialogFooter class="flex justify-between gap-2">
                                        <Button variant="outline" :disabled="uploading" @click="uploadModalOpen = false">Cancel</Button>
                                        <div class="flex gap-2">
                                            <Button variant="outline" :disabled="uploading" @click="form.reset(); if (fileInput) fileInput.value = '';">
                                                Reset
                                            </Button>
                                            <Button :disabled="uploading" @click="submit">
                                                <Upload class="mr-2 h-4 w-4" />
                                                Upload &amp; publish
                                            </Button>
                                        </div>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <Badge variant="secondary">Version: {{ apk.version || 'Not set' }}</Badge>
                                <Badge variant="outline">Android only</Badge>
                                <Badge variant="outline">Size: {{ apk.size_human || 'n/a' }}</Badge>
                            </div>
                            <div class="grid gap-1 text-sm text-muted-foreground">
                                <p>
                                    Download:
                                    <Link class="text-primary underline" :href="apk.download_url" target="_blank">{{ apk.download_url }}</Link>
                                </p>
                                <p v-if="apk.uploaded_at">Uploaded at: {{ apk.uploaded_at }}</p>
                                <p v-if="apk.uploaded_by">
                                    Uploaded by: {{ apk.uploaded_by.name }} ({{ apk.uploaded_by.email }})
                                </p>
                                <p v-if="apk.notes">Notes: {{ apk.notes }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button as-child size="sm">
                                    <a :href="apk.download_url" download>
                                        <Download class="mr-2 h-4 w-4" />
                                        Download APK
                                    </a>
                                </Button>
                                <Button as-child size="sm" variant="outline">
                                    <a :href="apk.download_url" target="_blank" rel="noreferrer">
                                        <ExternalLink class="mr-2 h-4 w-4" />
                                        Open link
                                    </a>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
