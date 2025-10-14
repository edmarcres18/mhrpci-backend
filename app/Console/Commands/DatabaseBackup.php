<?php

namespace App\Console\Commands;

use App\Http\Controllers\DatabaseBackupController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database {--keep-days=30 : Number of days to keep backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup and optionally clean old backups';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting database backup...');

        try {
            // Create backup using the controller's logic
            $backupPath = $this->createBackup();
            
            $this->info("✓ Backup created successfully: " . basename($backupPath));

            // Clean old backups if specified
            $keepDays = (int) $this->option('keep-days');
            if ($keepDays > 0) {
                $this->cleanOldBackups($keepDays);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Create a database backup.
     */
    private function createBackup(): string
    {
        $backupPath = $this->getBackupStoragePath();
        
        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $filename = 'mhrpci_backup_' . date('y_m_d_His') . '.sql';
        $filePath = $backupPath . DIRECTORY_SEPARATOR . $filename;

        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        // Use reflection to access the controller's backup methods
        $controller = new DatabaseBackupController();
        $reflection = new \ReflectionClass($controller);

        if ($dbConfig['driver'] === 'sqlite') {
            $method = $reflection->getMethod('backupSqlite');
            $method->setAccessible(true);
            $method->invoke($controller, $dbConfig, $filePath);
        } elseif ($dbConfig['driver'] === 'mysql') {
            $method = $reflection->getMethod('backupMysql');
            $method->setAccessible(true);
            $method->invoke($controller, $dbConfig, $filePath);
        } elseif ($dbConfig['driver'] === 'pgsql') {
            $method = $reflection->getMethod('backupPostgresql');
            $method->setAccessible(true);
            $method->invoke($controller, $dbConfig, $filePath);
        } else {
            throw new \Exception('Unsupported database driver: ' . $dbConfig['driver']);
        }

        return $filePath;
    }

    /**
     * Clean old backup files.
     */
    private function cleanOldBackups(int $keepDays): void
    {
        $backupPath = $this->getBackupStoragePath();
        
        if (!File::exists($backupPath)) {
            return;
        }

        $files = File::files($backupPath);
        $cutoffTime = now()->subDays($keepDays)->timestamp;
        $deletedCount = 0;

        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.sql') && $file->getMTime() < $cutoffTime) {
                File::delete($file->getPathname());
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("✓ Cleaned {$deletedCount} old backup(s) older than {$keepDays} days");
        }
    }

    /**
     * Get backup storage path.
     */
    private function getBackupStoragePath(): string
    {
        return storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'database');
    }
}
