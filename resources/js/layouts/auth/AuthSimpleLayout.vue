<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';
import { useSiteSettings } from '@/composables/useSiteSettings';

defineProps<{
    title?: string;
    description?: string;
}>();

const { siteSettings } = useSiteSettings();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-3 font-medium"
                    >
                        <div
                            class="mb-2 flex h-24 w-24 items-center justify-center rounded-lg dark:bg-neutral-900 shadow-sm border border-neutral-200 dark:border-neutral-800"
                        >
                            <img 
                                v-if="siteSettings.site_logo" 
                                :src="siteSettings.site_logo" 
                                :alt="siteSettings.site_name"
                                class="h-20 w-20 object-contain"
                            />
                            <AppLogoIcon
                                v-else
                                class="size-16 fill-current text-[var(--foreground)] dark:text-white"
                            />
                        </div>
                        <span class="text-lg font-semibold">{{ siteSettings.site_name }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>

