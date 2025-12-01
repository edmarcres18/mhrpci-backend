<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubItem,
    SidebarMenuSubButton,
    SidebarMenuAction,
} from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { toUrl, urlIsActive } from '@/lib/utils';
import { type NavGroup } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

defineProps<{
    groups: NavGroup[];
}>();

const page = usePage();

const LS_KEY = 'navTreeOpenMap';
const openMap = ref<Record<string, boolean>>({});

onMounted(() => {
    try {
        const saved = localStorage.getItem(LS_KEY);
        if (saved) {
            openMap.value = JSON.parse(saved) || {};
        }
    } catch {}
});

const setOpen = (key: string, open: boolean) => {
    openMap.value = { ...openMap.value, [key]: open };
    try {
        localStorage.setItem(LS_KEY, JSON.stringify(openMap.value));
    } catch {}
};

const childrenActive = (item: NavGroup['items'][number]) =>
    item.children?.some((child) => urlIsActive(child.href, page.url)) ?? false;

const treeKey = (group: NavGroup, idx: number, item: NavGroup['items'][number]) =>
    `${group.label ?? idx}:${toUrl(item.href)}`;

const isOpen = (key: string, item: NavGroup['items'][number]) =>
    openMap.value[key] ?? (childrenActive(item) ? true : false);
</script>

<template>
    <SidebarGroup v-for="(group, idx) in groups" :key="group.label || idx" class="px-2 py-0">
        <SidebarGroupLabel v-if="group.label">{{ group.label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in group.items" :key="item.title">
                <template v-if="item.children && item.children.length">
                    <Collapsible
                        v-slot="{ open }"
                        :open="isOpen(treeKey(group, idx, item), item)"
                        @update:open="(val) => setOpen(treeKey(group, idx, item), val)"
                    >
                        <SidebarMenuButton
                            as-child
                            :is-active="item.children.some((child) => urlIsActive(child.href, page.url))"
                            :tooltip="item.title"
                            :data-state="open ? 'open' : 'closed'"
                        >
                            <Link :href="item.href">
                                <component v-if="item.icon" :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>

                        <SidebarMenuAction showOnHover>
                            <CollapsibleTrigger as-child>
                                <button type="button">
                                    <ChevronDown :class="['transition-transform', open ? 'rotate-180' : 'rotate-0']" />
                                </button>
                            </CollapsibleTrigger>
                        </SidebarMenuAction>

                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-for="child in item.children" :key="child.title">
                                    <SidebarMenuSubButton
                                        as-child
                                        :is-active="urlIsActive(child.href, page.url)"
                                    >
                                        <Link :href="child.href">
                                            <component v-if="child.icon" :is="child.icon" />
                                            <span>{{ child.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </Collapsible>
                </template>
                <template v-else>
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
                </template>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
