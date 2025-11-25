import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface NavGroup {
    label?: string;
    items: NavItem[];
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    siteSettings: {
        site_name: string;
        site_logo: string | null;
    };
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

// Dashboard Types
export interface DashboardStats {
    users: UserStats;
    invitations: InvitationStats;
    activity: ActivityStats;
}

export interface UserStats {
    total: number;
    last_month: number;
    last_week: number;
    by_role: Record<string, number>;
    growth_rate: number;
}

export interface InvitationStats {
    total: number;
    pending: number;
    expired: number;
    used: number;
    last_month: number;
    last_week: number;
}

export interface ActivityStats {
    new_users_today: number;
    invitations_today: number;
}

export interface ChartData {
    labels: string[];
    datasets: ChartDataset[];
}

export interface ChartDataset {
    label: string;
    data: number[];
}

export interface RecentUser {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
    created_at_full: string;
}

export interface RecentInvitation {
    id: number;
    email: string;
    role: string;
    invited_by: string;
    status: string;
    expires_at: string;
    created_at: string;
}

export interface RecentBackup {
    filename: string;
    size: string;
    size_bytes: number;
    created_at: string;
    created_at_full: string;
    timestamp: number;
}

// Inventories Activity Types
export interface RecentInventoryChange {
    id: number;
    inventory_accountable: string;
    inventory_name: string;
    inventory_status: string;
    event: 'created' | 'updated';
    updated_at: string;
    updated_at_full: string;
    timestamp: number;
}

export interface RecentInventoryDeletion {
    accountable: string;
    count: number;
    item?: { id: number; inventory_name: string } | null;
    deleted_at: string;
    deleted_at_full: string;
    timestamp: number;
}

export interface InventoriesActivitySummary {
    added_last_24h: number;
    updated_last_24h: number;
    deleted_last_24h: number;
    last_change_at: string | null;
}

export interface InventoriesActivity {
    summary: InventoriesActivitySummary;
    recent_changes: RecentInventoryChange[];
    recent_deletions: RecentInventoryDeletion[];
}

export interface DashboardApiResponse<T> {
    success: boolean;
    data: T;
    cached_at?: string;
    message?: string;
    error?: string;
}
