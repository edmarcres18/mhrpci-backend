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
import { urlIsActive } from '@/lib/utils';
import { type NavGroup } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';

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
                <template v-if="item.children && item.children.length">
                    <Collapsible
                        v-slot="{ open }"
                        :default-open="item.children.some((child) => urlIsActive(child.href, page.url))"
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
