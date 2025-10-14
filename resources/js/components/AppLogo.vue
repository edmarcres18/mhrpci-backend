<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useSiteSettings } from '@/composables/useSiteSettings';

const { siteSettings } = useSiteSettings();
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md dark:bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <img 
            v-if="siteSettings.site_logo" 
            :src="siteSettings.site_logo" 
            :alt="siteSettings.site_name"
            class="size-7 object-contain"
        />
        <AppLogoIcon v-else class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{ siteSettings.site_name }}</span>
    </div>
</template>

