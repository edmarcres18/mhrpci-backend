<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import LineChart from '@/components/dashboard/LineChart.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { dashboard } from '@/routes';
import { useDashboard } from '@/composables/useDashboard';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    Users,
    Mail,
    TrendingUp,
    RefreshCw,
    UserPlus,
    Clock,
    CheckCircle,
    XCircle,
    AlertCircle,
    Database,
    HardDrive,
    ListPlus,
    PencilLine,
    Trash2,
} from 'lucide-vue-next';
import { computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const {
    stats,
    userGrowth,
    recentUsers,
    recentInvitations,
    recentBackups,
    isLoading,
    error,
    lastUpdated,
    refresh,
    startAutoRefresh,
    inventoriesActivity,
    startInventoriesRealtime,
} = useDashboard();

// Start auto-refresh every 60 seconds
startAutoRefresh(60000);
// Start real-time inventories activity polling every 5 seconds
startInventoriesRealtime(5000);

const isRefreshing = computed(() => isLoading.value);

const getStatusBadgeVariant = (status: string) => {
    switch (status.toLowerCase()) {
        case 'used':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'expired':
            return 'destructive';
        default:
            return 'outline';
    }
};

const getStatusIcon = (status: string) => {
    switch (status.toLowerCase()) {
        case 'used':
            return CheckCircle;
        case 'pending':
            return Clock;
        case 'expired':
            return XCircle;
        default:
            return AlertCircle;
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-3 sm:gap-5 sm:p-4 md:gap-6 md:p-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl truncate">Dashboard</h1>
                    <p class="text-xs text-muted-foreground mt-1 sm:text-sm">
                        <span v-if="lastUpdated">
                            <span class="hidden sm:inline">Last updated </span>
                            <span class="sm:hidden">Updated </span>
                            {{ lastUpdated.toLocaleTimeString() }}
                        </span>
                        <span v-else>Loading...</span>
                    </p>
                </div>
                <Button
                    @click="refresh"
                    :disabled="isRefreshing"
                    variant="outline"
                    size="sm"
                    class="w-full sm:w-auto"
                >
                    <RefreshCw :class="['mr-2 h-4 w-4', { 'animate-spin': isRefreshing }]" />
                    <span>Refresh</span>
                </Button>
            </div>

            <!-- Error Alert -->
            <div
                v-if="error"
                class="rounded-lg border border-destructive/50 bg-destructive/10 p-3 text-xs text-destructive sm:p-4 sm:text-sm"
            >
                <div class="flex items-start gap-2">
                    <AlertCircle class="h-4 w-4 flex-shrink-0 mt-0.5" />
                    <span class="flex-1">{{ error }}</span>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading && !stats" class="space-y-4 sm:space-y-5 md:space-y-6">
                <div class="grid gap-3 grid-cols-1 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
                    <Skeleton class="h-28 w-full sm:h-32" />
                    <Skeleton class="h-28 w-full sm:h-32" />
                    <Skeleton class="h-28 w-full sm:h-32" />
                    <Skeleton class="h-28 w-full sm:h-32" />
                </div>
                <Skeleton class="h-64 w-full sm:h-80 md:h-96" />
            </div>

            <!-- Dashboard Content -->
            <div v-else class="space-y-4 sm:space-y-5 md:space-y-6">
                <!-- Stats Cards -->
                <div class="grid gap-3 grid-cols-1 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
                    <StatCard
                        title="Total Users"
                        :value="stats?.users.total || 0"
                        :description="`+${stats?.users.last_month || 0} this month`"
                        :icon="Users"
                        icon-color="text-blue-600 dark:text-blue-400"
                        trend="up"
                        :trend-value="`${stats?.users.growth_rate || 0}%`"
                    />
                    <StatCard
                        title="New Users (This Week)"
                        :value="stats?.users.last_week || 0"
                        :icon="UserPlus"
                        icon-color="text-green-600 dark:text-green-400"
                    />
                    <StatCard
                        title="Pending Invitations"
                        :value="stats?.invitations.pending || 0"
                        :description="`${stats?.invitations.total || 0} total invitations`"
                        :icon="Mail"
                        icon-color="text-purple-600 dark:text-purple-400"
                    />
                    <StatCard
                        title="Today's Activity"
                        :value="(stats?.activity.new_users_today || 0) + (stats?.activity.invitations_today || 0)"
                        :description="`${stats?.activity.new_users_today || 0} users, ${stats?.activity.invitations_today || 0} invitations`"
                        :icon="TrendingUp"
                        icon-color="text-orange-600 dark:text-orange-400"
                    />
                </div>

                <!-- Charts and Details Row -->
                <div class="grid gap-4 sm:gap-5 md:gap-6 lg:grid-cols-2">
                    <!-- User Growth Chart -->
                    <Card class="lg:col-span-2 overflow-hidden">
                        <CardHeader class="pb-3 sm:pb-6">
                            <CardTitle class="text-lg sm:text-xl">User Growth (Last 30 Days)</CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Daily new user registrations over the past month
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="px-2 sm:px-6">
                            <div class="-mx-2 sm:mx-0">
                                <LineChart :data="userGrowth" :height="250" class="sm:!h-[300px]" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Users by Role -->
                    <Card>
                        <CardHeader class="pb-3 sm:pb-6">
                            <CardTitle class="text-lg sm:text-xl">Users by Role</CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Distribution of users across different roles
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3 sm:space-y-4">
                                <div
                                    v-for="(count, role) in stats?.users.by_role"
                                    :key="role"
                                    class="flex items-center justify-between py-1"
                                >
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <div class="h-2.5 w-2.5 sm:h-3 sm:w-3 rounded-full bg-primary flex-shrink-0" />
                                        <span class="text-xs sm:text-sm font-medium truncate">{{ role }}</span>
                                    </div>
                                    <span class="text-xl sm:text-2xl font-bold ml-2">{{ count }}</span>
                                </div>
                                <div
                                    v-if="!stats?.users.by_role || Object.keys(stats.users.by_role).length === 0"
                                    class="text-center py-6 sm:py-8 text-xs sm:text-sm text-muted-foreground"
                                >
                                    No role data available
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Invitation Statistics -->
                    <Card>
                        <CardHeader class="pb-3 sm:pb-6">
                            <CardTitle class="text-lg sm:text-xl">Invitation Status</CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Overview of invitation states
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3 sm:space-y-4">
                                <div class="flex items-center justify-between py-1">
                                    <div class="flex items-center gap-2">
                                        <Clock class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-yellow-600 flex-shrink-0" />
                                        <span class="text-xs sm:text-sm font-medium">Pending</span>
                                    </div>
                                    <span class="text-xl sm:text-2xl font-bold">{{ stats?.invitations.pending || 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1">
                                    <div class="flex items-center gap-2">
                                        <CheckCircle class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-green-600 flex-shrink-0" />
                                        <span class="text-xs sm:text-sm font-medium">Used</span>
                                    </div>
                                    <span class="text-xl sm:text-2xl font-bold">{{ stats?.invitations.used || 0 }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1">
                                    <div class="flex items-center gap-2">
                                        <XCircle class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-red-600 flex-shrink-0" />
                                        <span class="text-xs sm:text-sm font-medium">Expired</span>
                                    </div>
                                    <span class="text-xl sm:text-2xl font-bold">{{ stats?.invitations.expired || 0 }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Inventories Activity -->
                <Card>
                    <CardHeader class="pb-3 sm:pb-6">
                        <CardTitle class="text-lg sm:text-xl">Inventories Activity</CardTitle>
                        <CardDescription class="text-xs sm:text-sm">
                            Real-time monitor of created, updated, and deleted accountables
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-3 grid-cols-1 sm:grid-cols-3 sm:gap-4">
                            <div class="rounded-lg border p-4">
                                <div class="flex items-center gap-2 text-xs sm:text-sm text-muted-foreground">
                                    <ListPlus class="h-4 w-4 text-green-600" />
                                    <span>Added (24h)</span>
                                </div>
                                <div class="mt-2 text-2xl sm:text-3xl font-bold">
                                    {{ inventoriesActivity?.summary.added_last_24h || 0 }}
                                </div>
                            </div>
                            <div class="rounded-lg border p-4">
                                <div class="flex items-center gap-2 text-xs sm:text-sm text-muted-foreground">
                                    <PencilLine class="h-4 w-4 text-blue-600" />
                                    <span>Updated (24h)</span>
                                </div>
                                <div class="mt-2 text-2xl sm:text-3xl font-bold">
                                    {{ inventoriesActivity?.summary.updated_last_24h || 0 }}
                                </div>
                            </div>
                            <div class="rounded-lg border p-4">
                                <div class="flex items-center gap-2 text-xs sm:text-sm text-muted-foreground">
                                    <Trash2 class="h-4 w-4 text-red-600" />
                                    <span>Deleted (24h)</span>
                                </div>
                                <div class="mt-2 text-2xl sm:text-3xl font-bold">
                                    {{ inventoriesActivity?.summary.deleted_last_24h || 0 }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 sm:gap-5 md:gap-6 lg:grid-cols-2">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-sm font-semibold">Recent Accountable Activity</div>
                                    <div class="text-xs text-muted-foreground">
                                        Last change: {{ inventoriesActivity?.summary.last_change_at || '—' }}
                                    </div>
                                </div>
                                <div class="space-y-3 sm:space-y-4">
                                    <div
                                        v-for="ev in inventoriesActivity?.recent_accountables || []"
                                        :key="`${ev.accountable}-${ev.timestamp}-${ev.event}`"
                                        class="flex items-start gap-3 border-b border-border/50 pb-3 last:border-0 last:pb-0"
                                    >
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium leading-none truncate" :title="ev.accountable">
                                                {{ ev.accountable }}
                                            </p>
                                            <p class="text-xs text-muted-foreground truncate">
                                                {{ ev.at }}
                                            </p>
                                        </div>
                                        <Badge :variant="ev.event === 'deleted' ? 'destructive' : (ev.event === 'created' ? 'secondary' : 'outline')" class="text-xs whitespace-nowrap">
                                            <component :is="ev.event === 'deleted' ? Trash2 : (ev.event === 'created' ? ListPlus : PencilLine)" class="mr-1 h-3 w-3" />
                                            <span v-if="ev.event === 'deleted' && ev.count != null">{{ ev.count }} deleted</span>
                                            <span v-else>{{ ev.event }}</span>
                                        </Badge>
                                    </div>
                                    <div
                                        v-if="!inventoriesActivity || (inventoriesActivity.recent_accountables || []).length === 0"
                                        class="text-center py-6 sm:py-8 text-xs sm:text-sm text-muted-foreground"
                                    >
                                        No recent activity
                                    </div>
                                </div>
                            </div>

                            <div class="hidden lg:block">
                                <div class="text-sm font-semibold mb-2">Summary</div>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2 text-xs sm:text-sm text-muted-foreground">
                                        <ListPlus class="h-4 w-4 text-green-600" />
                                        <span>{{ inventoriesActivity?.summary.added_last_24h || 0 }} added (24h)</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs sm:text-sm text-muted-foreground">
                                        <PencilLine class="h-4 w-4 text-blue-600" />
                                        <span>{{ inventoriesActivity?.summary.updated_last_24h || 0 }} updated (24h)</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs sm:text-sm text-muted-foreground">
                                        <Trash2 class="h-4 w-4 text-red-600" />
                                        <span>{{ inventoriesActivity?.summary.deleted_last_24h || 0 }} deleted (24h)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Activity Section -->
                <div class="grid gap-4 sm:gap-5 md:gap-6 lg:grid-cols-3">
                    <!-- Recent Users -->
                    <Card>
                        <CardHeader class="pb-3 sm:pb-6">
                            <CardTitle class="text-lg sm:text-xl">Recent Users</CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Latest registered users in the system
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3 sm:space-y-4">
                                <div
                                    v-for="user in recentUsers"
                                    :key="user.id"
                                    class="flex items-start sm:items-center gap-3 border-b border-border/50 pb-3 last:border-0 last:pb-0"
                                >
                                    <div class="flex-1 min-w-0 space-y-1">
                                        <p class="text-sm font-medium leading-none truncate" :title="user.name">
                                            {{ user.name }}
                                        </p>
                                        <p class="text-xs text-muted-foreground truncate" :title="user.email">
                                            {{ user.email }}
                                        </p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <Badge variant="secondary" class="mb-1 text-xs whitespace-nowrap">
                                            {{ user.role }}
                                        </Badge>
                                        <p class="text-xs text-muted-foreground whitespace-nowrap">
                                            {{ user.created_at }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    v-if="recentUsers.length === 0"
                                    class="text-center py-6 sm:py-8 text-xs sm:text-sm text-muted-foreground"
                                >
                                    No recent users
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Invitations -->
                    <Card>
                        <CardHeader class="pb-3 sm:pb-6">
                            <CardTitle class="text-lg sm:text-xl">Recent Invitations</CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Latest sent invitations and their status
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3 sm:space-y-4">
                                <div
                                    v-for="invitation in recentInvitations"
                                    :key="invitation.id"
                                    class="flex items-start sm:items-center gap-3 border-b border-border/50 pb-3 last:border-0 last:pb-0"
                                >
                                    <div class="space-y-1 flex-1 min-w-0">
                                        <p class="text-sm font-medium leading-none truncate" :title="invitation.email">
                                            {{ invitation.email }}
                                        </p>
                                        <p class="text-xs text-muted-foreground truncate">
                                            By {{ invitation.invited_by }} • {{ invitation.created_at }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                        <Badge :variant="getStatusBadgeVariant(invitation.status)" class="text-xs whitespace-nowrap">
                                            <component :is="getStatusIcon(invitation.status)" class="mr-1 h-3 w-3" />
                                            {{ invitation.status }}
                                        </Badge>
                                        <span class="text-xs text-muted-foreground whitespace-nowrap">
                                            {{ invitation.role }}
                                        </span>
                                    </div>
                                </div>
                                <div
                                    v-if="recentInvitations.length === 0"
                                    class="text-center py-6 sm:py-8 text-xs sm:text-sm text-muted-foreground"
                                >
                                    No recent invitations
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Backups -->
                    <Card>
                        <CardHeader class="pb-3 sm:pb-6">
                            <CardTitle class="text-lg sm:text-xl">Recent Backups</CardTitle>
                            <CardDescription class="text-xs sm:text-sm">
                                Latest database backup files
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3 sm:space-y-4">
                                <div
                                    v-for="backup in recentBackups"
                                    :key="backup.filename"
                                    class="flex items-center gap-2 sm:gap-3 border-b border-border/50 pb-3 last:border-0 last:pb-0"
                                >
                                    <div class="flex-shrink-0">
                                        <div class="h-9 w-9 sm:h-10 sm:w-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                            <Database class="h-4 w-4 sm:h-5 sm:w-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs sm:text-sm font-medium leading-none truncate" :title="backup.filename">
                                            {{ backup.filename }}
                                        </p>
                                        <div class="flex items-center gap-1.5 sm:gap-2 mt-1">
                                            <HardDrive class="h-3 w-3 text-muted-foreground flex-shrink-0" />
                                            <p class="text-xs text-muted-foreground">
                                                {{ backup.size }}
                                            </p>
                                            <span class="text-xs text-muted-foreground">•</span>
                                            <p class="text-xs text-muted-foreground truncate" :title="backup.created_at_full">
                                                {{ backup.created_at }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-if="recentBackups.length === 0"
                                    class="text-center py-6 sm:py-8 text-xs sm:text-sm text-muted-foreground"
                                >
                                    No recent backups
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
