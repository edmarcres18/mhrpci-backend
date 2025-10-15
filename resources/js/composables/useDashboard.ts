import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import type {
    DashboardStats,
    DashboardApiResponse,
    ChartData,
    RecentUser,
    RecentInvitation,
    RecentBackup,
} from '@/types';

export function useDashboard() {
    const stats = ref<DashboardStats | null>(null);
    const userGrowth = ref<ChartData | null>(null);
    const recentUsers = ref<RecentUser[]>([]);
    const recentInvitations = ref<RecentInvitation[]>([]);
    const recentBackups = ref<RecentBackup[]>([]);
    
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    const lastUpdated = ref<Date | null>(null);
    
    let refreshInterval: number | null = null;

    /**
     * Fetch dashboard statistics
     */
    const fetchStats = async () => {
        try {
            const response = await axios.get<DashboardApiResponse<DashboardStats>>(
                '/api/dashboard/stats'
            );
            
            if (response.data.success) {
                stats.value = response.data.data;
            }
        } catch (err) {
            console.error('Failed to fetch dashboard stats:', err);
            throw err;
        }
    };

    /**
     * Fetch user growth chart data
     */
    const fetchUserGrowth = async () => {
        try {
            const response = await axios.get<DashboardApiResponse<ChartData>>(
                '/api/dashboard/user-growth'
            );
            
            if (response.data.success) {
                userGrowth.value = response.data.data;
            }
        } catch (err) {
            console.error('Failed to fetch user growth:', err);
            throw err;
        }
    };

    /**
     * Fetch recent users
     */
    const fetchRecentUsers = async () => {
        try {
            const response = await axios.get<DashboardApiResponse<RecentUser[]>>(
                '/api/dashboard/recent-users'
            );
            
            if (response.data.success) {
                recentUsers.value = response.data.data;
            }
        } catch (err) {
            console.error('Failed to fetch recent users:', err);
            throw err;
        }
    };

    /**
     * Fetch recent invitations
     */
    const fetchRecentInvitations = async () => {
        try {
            const response = await axios.get<DashboardApiResponse<RecentInvitation[]>>(
                '/api/dashboard/recent-invitations'
            );
            
            if (response.data.success) {
                recentInvitations.value = response.data.data;
            }
        } catch (err) {
            console.error('Failed to fetch recent invitations:', err);
            throw err;
        }
    };

    /**
     * Fetch recent backups
     */
    const fetchRecentBackups = async () => {
        try {
            const response = await axios.get<DashboardApiResponse<RecentBackup[]>>(
                '/api/dashboard/recent-backups'
            );
            
            if (response.data.success) {
                recentBackups.value = response.data.data;
            }
        } catch (err) {
            console.error('Failed to fetch recent backups:', err);
            throw err;
        }
    };

    /**
     * Fetch all dashboard data
     */
    const fetchAllData = async () => {
        isLoading.value = true;
        error.value = null;
        
        try {
            await Promise.all([
                fetchStats(),
                fetchUserGrowth(),
                fetchRecentUsers(),
                fetchRecentInvitations(),
                fetchRecentBackups(),
            ]);
            
            lastUpdated.value = new Date();
        } catch (err: any) {
            const errorMessage = err?.response?.data?.message || err?.message || 'Unknown error';
            error.value = `Failed to load dashboard data: ${errorMessage}`;
            console.error('Dashboard fetch error:', {
                message: err?.message,
                response: err?.response?.data,
                status: err?.response?.status,
                error: err
            });
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Start auto-refresh with specified interval (in milliseconds)
     */
    const startAutoRefresh = (interval: number = 60000) => {
        stopAutoRefresh();
        refreshInterval = window.setInterval(() => {
            fetchAllData();
        }, interval);
    };

    /**
     * Stop auto-refresh
     */
    const stopAutoRefresh = () => {
        if (refreshInterval !== null) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
    };

    /**
     * Manually refresh data
     */
    const refresh = async () => {
        await fetchAllData();
    };

    // Auto-fetch on mount
    onMounted(() => {
        fetchAllData();
    });

    // Cleanup on unmount
    onUnmounted(() => {
        stopAutoRefresh();
    });

    return {
        // Data
        stats,
        userGrowth,
        recentUsers,
        recentInvitations,
        recentBackups,
        
        // State
        isLoading,
        error,
        lastUpdated,
        
        // Methods
        refresh,
        startAutoRefresh,
        stopAutoRefresh,
        fetchAllData,
    };
}
