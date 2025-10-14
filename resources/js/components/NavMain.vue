<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavGroup } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    groups: NavGroup[];
}>();

const page = usePage();
</script>

<template>
    <SidebarGroup v-for="(group, idx) in groups" :key="group.label || idx" class="px-2 py-0">
        <SidebarGroupLabel v-if="group.label">{{ group.label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in group.items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="urlIsActive(item.href, page.url)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>

