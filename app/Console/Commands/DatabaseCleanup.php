<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DatabaseCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:cleanup {--days=30 : Number of days to keep backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old database backup files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting backup cleanup...');

        $days = (int) $this->option('days');
        $backupPath = $this->getBackupStoragePath();
        
        if (!File::exists($backupPath)) {
            $this->warn('No backup directory found.');
            return Command::SUCCESS;
        }

        $files = File::files($backupPath);
        $cutoffTime = now()->subDays($days)->timestamp;
        $deletedCount = 0;
        $totalSize = 0;

        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.sql') && $file->getMTime() < $cutoffTime) {
                $totalSize += $file->getSize();
                File::delete($file->getPathname());
                $deletedCount++;
                $this->line("  - Deleted: " . $file->getFilename());
            }
        }

        if ($deletedCount > 0) {
            $this->info("✓ Deleted {$deletedCount} backup(s) older than {$days} days");
            $this->info("✓ Freed up " . $this->formatBytes($totalSize) . " of disk space");
        } else {
            $this->info('No old backups to clean up.');
        }

        return Command::SUCCESS;
    }

    /**
     * Get backup storage path.
     */
    private function getBackupStoragePath(): string
    {
        return storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'database');
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
}
