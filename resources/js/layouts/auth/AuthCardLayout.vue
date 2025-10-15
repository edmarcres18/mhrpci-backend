<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10"
    >
        <div class="flex w-full max-w-md flex-col gap-6">
            <Link
                :href="home()"
                class="flex flex-col items-center gap-2 self-center font-medium"
            >
                <div class="flex h-16 w-16 items-center justify-center rounded-lg shadow-sm border border-neutral-200 dark:border-neutral-800">
                    <img 
                        v-if="siteSettings.site_logo" 
                        :src="siteSettings.site_logo" 
                        :alt="siteSettings.site_name"
                        class="h-14 w-14 object-contain"
                    />
                    <AppLogoIcon
                        v-else
                        class="size-9 fill-current text-black dark:text-white"
                    />
                </div>
                <span v-if="siteSettings.site_name" class="text-base font-semibold">{{ siteSettings.site_name }}</span>
            </Link>

            <div class="flex flex-col gap-6">
                <Card class="rounded-xl">
                    <CardHeader class="px-10 pt-8 pb-0 text-center">
                        <CardTitle class="text-xl">{{ title }}</CardTitle>
                        <CardDescription>
                            {{ description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-10 py-8">
                        <slot />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
