<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { AppPageProps, BreadcrumbItemType } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItemType[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mobile Scanner', href: '/mobile-scanner' },
];

const page = usePage<AppPageProps<{ apk?: any }>>();
const apkMeta = computed(() => (page.props.apk as any) ?? {});
const apkUrl = computed(() => apkMeta.value.download_url || '/mobile_app/ITScanner.apk');
const apkQr = '/mobile-scanner/qr';
const apkVersion = computed(() => apkMeta.value.version || 'Not set');
const apkSize = computed(() => apkMeta.value.size_human || 'n/a');

const screenshotFiles = [
    'photo_2026-01-07_16-57-05.jpg',
    'photo_2026-01-07_16-57-06 (2).jpg',
    'photo_2026-01-07_16-57-06.jpg',
    'photo_2026-01-07_16-57-07 (2).jpg',
    'photo_2026-01-07_16-57-07.jpg',
    'photo_2026-01-07_16-57-08 (2).jpg',
    'photo_2026-01-07_16-57-08.jpg',
    'photo_2026-01-07_16-57-09 (2).jpg',
    'photo_2026-01-07_16-57-09.jpg',
    'photo_2026-01-07_16-57-10.jpg',
];

const screenshotUrls = screenshotFiles.map((file) => `/screenshots/${encodeURIComponent(file)}`);
const currentIndex = ref(0);
const currentImage = computed(() =>
    screenshotUrls.length ? screenshotUrls[currentIndex.value % screenshotUrls.length] : null
);
let rotationTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    if (screenshotUrls.length <= 1) return;
    rotationTimer = setInterval(() => {
        currentIndex.value = (currentIndex.value + 1) % screenshotUrls.length;
    }, 3000);
});

onBeforeUnmount(() => {
    if (rotationTimer) {
        clearInterval(rotationTimer);
        rotationTimer = null;
    }
});

</script>

<template>
    <Head title="Mobile App IT Scanner" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-10 p-4 sm:p-6 lg:p-10">
            <section
                class="relative overflow-hidden rounded-3xl border bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-6 py-8 shadow-xl text-white sm:px-8 sm:py-10"
            >
                <div class="absolute left-10 top-10 h-32 w-32 rounded-full bg-cyan-500/25 blur-3xl" />
                <div class="absolute -right-10 bottom-0 h-40 w-40 rounded-full bg-indigo-500/25 blur-3xl" />

                <div class="relative flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-4 lg:max-w-2xl">
                        <div class="flex flex-wrap items-center gap-2 text-xs uppercase tracking-[0.1em]">
                            <Badge variant="outline" class="border-white/30 bg-white/10 text-white">
                                Android only
                            </Badge>
                            <Badge variant="secondary" class="bg-white/20 text-white">
                                Version: {{ apkVersion }}
                            </Badge>
                            <Badge variant="outline" class="border-white/30 bg-white/10 text-white">
                                Size: {{ apkSize }}
                            </Badge>
                            <Badge variant="outline" class="border-white/30 bg-white/10 text-white">
                                Online required
                            </Badge>
                        </div>
                        <h1 class="text-3xl font-semibold leading-tight sm:text-4xl lg:text-5xl">
                            Mobile App IT Scanner (Android)
                        </h1>
                        <div class="space-y-2 text-white/80">
                            <p class="text-sm sm:text-base leading-relaxed">
                                Seamlessly audit IT assets across warehouses, clinics, and offices. Scan, verify, and sync
                                inventory data with a streamlined experience built for teams on the move.
                            </p>
                            <p class="text-sm sm:text-base">
                                The APK is <strong>Android-only</strong> (iOS remains invite-only via TestFlight). You’ll need an active internet
                                connection to download, sign in, and keep data in sync.
                            </p>
                            <p class="text-sm sm:text-base">
                                Purpose-built for IT inventories: every QR or barcode scanned is validated against the IT inventory
                                database before it’s recorded, so only authorized IT assets are saved.
                            </p>
                        </div>
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-6">
                            <div class="flex flex-col items-center gap-2 rounded-2xl border border-white/10 bg-white/5 p-4 text-center">
                                <p class="text-xs uppercase tracking-[0.08em] text-white/60">Scan to download</p>
                                <img
                                    :src="apkQr"
                                    alt="QR code to download IT Scanner APK"
                                    class="h-44 w-44 rounded-xl border border-white/20 bg-white/10 p-2 shadow-inner"
                                    loading="lazy"
                                />
                            </div>
                            <div class="space-y-3 text-white/80">
                                <div class="grid gap-2 text-sm sm:text-base">
                                    <div><strong>Latest version:</strong> {{ apkVersion }}</div>
                                    <div><strong>Download size:</strong> {{ apkSize }}</div>
                                    <div><strong>Network:</strong> Active internet required for download and sync.</div>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <Button as-child size="lg" class="bg-emerald-400 text-slate-900 hover:bg-emerald-300 shadow-md">
                                        <a :href="apkUrl" download>
                                            <Download class="mr-2 h-4 w-4" />
                                            Download APK
                                        </a>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="currentImage"
                        class="w-full lg:max-w-md overflow-hidden rounded-3xl border border-white/10 bg-white/10 shadow-2xl backdrop-blur-md"
                    >
                        <div class="relative aspect-[3/5]">
                            <transition name="fade" mode="out-in">
                                <img
                                    v-if="currentImage"
                                    :key="currentImage"
                                    :src="currentImage"
                                    alt="Mobile app screenshot"
                                    class="absolute inset-0 h-full w-full object-cover"
                                    loading="lazy"
                                />
                            </transition>
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent" />
                            <div class="absolute bottom-4 right-4 flex items-center gap-2 rounded-full bg-black/50 px-3 py-1 text-xs font-medium text-white backdrop-blur">
                                <span class="inline-block h-2 w-2 rounded-full bg-emerald-300" />
                                Auto-rotates every 3s
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
