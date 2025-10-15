<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use App\UserRole;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard page.
     */
    public function index(): Response
    {
        return Inertia::render('Dashboard');
    }

    /**
     * Get dashboard statistics.
     * Cached for 5 minutes for performance.
     */
    public function getStats(): JsonResponse
    {
        try {
            $now = Carbon::now();
            $lastMonth = $now->copy()->subMonth();
            $lastWeek = $now->copy()->subWeek();

            // Total users
            $totalUsers = User::count();
            $usersLastMonth = User::where('created_at', '>=', $lastMonth)->count();
            $usersLastWeek = User::where('created_at', '>=', $lastWeek)->count();

            // Users by role with safe enum handling
            $usersByRole = [];
            try {
                $roleData = User::select('role', DB::raw('count(*) as total'))
                    ->whereNotNull('role')
                    ->groupBy('role')
                    ->get();
                
                foreach ($roleData as $item) {
                    try {
                        $role = UserRole::from($item->role);
                        $usersByRole[$role->displayName()] = (int) $item->total;
                    } catch (\Throwable $e) {
                        // Skip invalid role values
                        continue;
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('Error fetching users by role: ' . $e->getMessage());
            }

            // Total invitations
            $totalInvitations = Invitation::count();
            $pendingInvitations = Invitation::where('used', false)
                ->where('expires_at', '>', $now)
                ->count();
            $expiredInvitations = Invitation::where('used', false)
                ->where('expires_at', '<=', $now)
                ->count();
            $usedInvitations = Invitation::where('used', true)->count();

            // Recent activity
            $invitationsLastMonth = Invitation::where('created_at', '>=', $lastMonth)->count();
            $invitationsLastWeek = Invitation::where('created_at', '>=', $lastWeek)->count();

            $stats = [
                'users' => [
                    'total' => $totalUsers,
                    'last_month' => $usersLastMonth,
                    'last_week' => $usersLastWeek,
                    'by_role' => $usersByRole,
                    'growth_rate' => $totalUsers > 0 
                        ? round(($usersLastMonth / max($totalUsers - $usersLastMonth, 1)) * 100, 1)
                        : 0,
                ],
                'invitations' => [
                    'total' => $totalInvitations,
                    'pending' => $pendingInvitations,
                    'expired' => $expiredInvitations,
                    'used' => $usedInvitations,
                    'last_month' => $invitationsLastMonth,
                    'last_week' => $invitationsLastWeek,
                ],
                'activity' => [
                    'new_users_today' => User::whereDate('created_at', $now->toDateString())->count(),
                    'invitations_today' => Invitation::whereDate('created_at', $now->toDateString())->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'cached_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard Stats Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get user growth chart data.
     * Shows user registrations over the last 30 days.
     */
    public function getUserGrowth(): JsonResponse
    {
        try {
            $chartData = Cache::remember('dashboard_user_growth', 300, function () {
                $days = 30;
                $labels = [];
                $data = [];

                for ($i = $days - 1; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $labels[] = $date->format('M d');
                    $data[] = User::whereDate('created_at', $date->toDateString())->count();
                }

                return [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => 'New Users',
                            'data' => $data,
                        ],
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $chartData,
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard User Growth Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user growth data',
            ], 500);
        }
    }

    /**
     * Get recent users.
     * Returns the 10 most recently registered users.
     */
    public function getRecentUsers(): JsonResponse
    {
        try {
            $users = User::latest()
                ->take(10)
                ->get()
                ->map(function ($user) {
                    $roleName = 'Unknown';
                    try {
                        $roleName = $user->role ? $user->role->displayName() : 'Unknown';
                    } catch (\Throwable $e) {
                        \Log::warning('Invalid role for user ' . $user->id);
                    }
                    
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $roleName,
                        'created_at' => $user->created_at->diffForHumans(),
                        'created_at_full' => $user->created_at->format('M d, Y h:i A'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard Recent Users Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent users',
            ], 500);
        }
    }

    /**
     * Get recent invitations.
     * Returns the 10 most recent invitations.
     */
    public function getRecentInvitations(): JsonResponse
    {
        try {
            $invitations = Invitation::with('invitedBy')
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($invitation) {
                    $roleName = 'Unknown';
                    try {
                        if ($invitation->role) {
                            $roleName = UserRole::from($invitation->role)->displayName();
                        }
                    } catch (\Throwable $e) {
                        \Log::warning('Invalid role in invitation ' . $invitation->id);
                    }
                    
                    return [
                        'id' => $invitation->id,
                        'email' => $invitation->email,
                        'role' => $roleName,
                        'invited_by' => $invitation->invitedBy->name ?? 'System',
                        'status' => $invitation->used 
                            ? 'Used' 
                            : ($invitation->isValid() ? 'Pending' : 'Expired'),
                        'expires_at' => $invitation->expires_at->diffForHumans(),
                        'created_at' => $invitation->created_at->diffForHumans(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $invitations,
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard Recent Invitations Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent invitations',
            ], 500);
        }
    }

    /**
     * Get recent database backups.
     * Returns the 5 most recent backup files.
     */
    public function getRecentBackups(): JsonResponse
    {
        try {
            $backupPath = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'database');
            
            if (!\File::exists($backupPath)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $files = \File::files($backupPath);
            $backups = [];

            foreach ($files as $file) {
                if (str_ends_with($file->getFilename(), '.sql')) {
                    $backups[] = [
                        'filename' => $file->getFilename(),
                        'size' => $this->formatBytes($file->getSize()),
                        'size_bytes' => $file->getSize(),
                        'created_at' => \Carbon\Carbon::createFromTimestamp($file->getMTime())->diffForHumans(),
                        'created_at_full' => date('M d, Y h:i A', $file->getMTime()),
                        'timestamp' => $file->getMTime(),
                    ];
                }
            }

            // Sort by timestamp descending (newest first) and take 5
            usort($backups, function ($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });

            $backups = array_slice($backups, 0, 5);

            return response()->json([
                'success' => true,
                'data' => $backups,
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard Recent Backups Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent backups',
            ], 500);
        }
    }

    /**
     * Format bytes to human-readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Clear all dashboard caches.
     * Useful when data needs to be refreshed immediately.
     */
    public function clearCache(): JsonResponse
    {
        try {
            Cache::forget('dashboard_stats');
            Cache::forget('dashboard_user_growth');

            return response()->json([
                'success' => true,
                'message' => 'Dashboard cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
            ], 500);
        }
    }
}
