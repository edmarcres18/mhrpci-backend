<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavGroup } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Info, Settings, Database, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const canAccessUsers = computed(() => (page.props.auth as any)?.canAccessUsers ?? false);
const isSystemAdmin = computed(() => (page.props.auth as any)?.isSystemAdmin ?? false);

const mainNavGroups = computed<NavGroup[]>(() => {
    const groups: NavGroup[] = [
        {
            items: [
                {
                    title: 'Dashboard',
                    href: dashboard(),
                    icon: LayoutGrid,
                },
            ],
        },
    ];

    // Only show Management section if user can access users
    if (canAccessUsers.value) {
        groups.push({
            label: 'Management',
            items: [
                {
                    title: 'Users',
                    href: '/users',
                    icon: Users,
                },
            ],
        });
    }

    // Only show Settings section if user can access users
    if (canAccessUsers.value) {
        groups.push({
            label: 'Settings',
            items: [
                {
                    title: 'Contact Information',
                    href: '/site-information',
                    icon: Info,
                },
            ],
        });
    }

    return groups;
});

const footerNavGroups = computed<NavGroup[]>(() => {
    const groups: NavGroup[] = [];

    // Only show System section if user is system admin
    if (isSystemAdmin.value) {
        groups.push({
            items: [
                {
                    title: 'Site Settings',
                    href: '/site-settings',
                    icon: Settings,
                },
                {
                    title: 'Database Backup',
                    href: '/database-backup',
                    icon: Database,
                },
            ],
        });
    }

    return groups;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :groups="mainNavGroups" />
        </SidebarContent>

        <SidebarFooter>
            <NavMain v-if="footerNavGroups.length > 0" :groups="footerNavGroups" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

