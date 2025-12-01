<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DatabaseBackupController extends Controller
{
    /**
     * Display the database backup management page.
     */
    public function index(): Response
    {
        // Only System Admin can access backup management
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->isSystemAdmin()) {
            abort(403, 'You do not have permission to access database backup management.');
        }

        $backups = $this->getAvailableBackups();

        return Inertia::render('Database/Backup', [
            'backups' => $backups,
            'databaseInfo' => $this->getDatabaseInfo(),
        ]);
    }

    /**
     * Create a new database backup.
     */
    public function backup(Request $request)
    {
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->isSystemAdmin()) {
            abort(403, 'You do not have permission to create database backups.');
        }

        try {
            $backupPath = $this->createBackup();

            return redirect()
                ->route('database-backup.index')
                ->with('success', 'Database backup created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create backup: '.$e->getMessage()]);
        }
    }

    /**
     * Download a specific backup file.
     */
    public function download(string $filename): BinaryFileResponse
    {
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->isSystemAdmin()) {
            abort(403, 'You do not have permission to download database backups.');
        }

        $backupPath = $this->getBackupStoragePath();
        $filePath = $backupPath.DIRECTORY_SEPARATOR.$filename;

        if (! File::exists($filePath)) {
            abort(404, 'Backup file not found.');
        }

        // Validate filename to prevent directory traversal
        if (basename($filename) !== $filename || ! str_ends_with($filename, '.sql')) {
            abort(403, 'Invalid backup file name.');
        }

        return response()->download($filePath);
    }

    /**
     * Delete a specific backup file.
     */
    public function destroy(string $filename)
    {
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->isSystemAdmin()) {
            abort(403, 'You do not have permission to delete database backups.');
        }

        $backupPath = $this->getBackupStoragePath();
        $filePath = $backupPath.DIRECTORY_SEPARATOR.$filename;

        // Validate filename
        if (basename($filename) !== $filename || ! str_ends_with($filename, '.sql')) {
            abort(403, 'Invalid backup file name.');
        }

        if (File::exists($filePath)) {
            File::delete($filePath);

            return redirect()
                ->route('database-backup.index')
                ->with('success', 'Backup deleted successfully.');
        }

        return back()->withErrors(['error' => 'Backup file not found.']);
    }

    /**
     * Restore database from a backup file.
     */
    public function restore(Request $request)
    {
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->isSystemAdmin()) {
            abort(403, 'You do not have permission to restore database backups.');
        }

        $request->validate([
            'filename' => 'required|string',
        ]);

        $filename = $request->input('filename');
        $backupPath = $this->getBackupStoragePath();
        $filePath = $backupPath.DIRECTORY_SEPARATOR.$filename;

        // Validate filename
        if (basename($filename) !== $filename || ! str_ends_with($filename, '.sql')) {
            return back()->withErrors(['error' => 'Invalid backup file name.']);
        }

        if (! File::exists($filePath)) {
            return back()->withErrors(['error' => 'Backup file not found.']);
        }

        try {
            $this->restoreFromFile($filePath);

            return redirect()
                ->route('database-backup.index')
                ->with('success', 'Database restored successfully from backup.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to restore backup: '.$e->getMessage()]);
        }
    }

    /**
     * Upload and restore from SQL file.
     */
    public function uploadAndRestore(Request $request)
    {
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->isSystemAdmin()) {
            abort(403, 'You do not have permission to restore database backups.');
        }

        $request->validate([
            'backup_file' => 'required|file|mimes:sql|max:102400', // Max 100MB
        ]);

        try {
            $file = $request->file('backup_file');
            $tempPath = $file->getRealPath();

            $this->restoreFromFile($tempPath);

            return redirect()
                ->route('database-backup.index')
                ->with('success', 'Database restored successfully from uploaded file.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to restore from uploaded file: '.$e->getMessage()]);
        }
    }

    /**
     * Get available backups from storage.
     */
    private function getAvailableBackups(): array
    {
        $backupPath = $this->getBackupStoragePath();

        if (! File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);

            return [];
        }

        $files = File::files($backupPath);
        $backups = [];

        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.sql')) {
                $backups[] = [
                    'filename' => $file->getFilename(),
                    'size' => $this->formatBytes($file->getSize()),
                    'size_bytes' => $file->getSize(),
                    'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                    'timestamp' => $file->getMTime(),
                ];
            }
        }

        // Sort by timestamp descending (newest first)
        usort($backups, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return $backups;
    }

    /**
     * Create a database backup.
     */
    private function createBackup(): string
    {
        $backupPath = $this->getBackupStoragePath();

        if (! File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $filename = 'mhrpci_backup_'.date('y_m_d_His').'.sql';
        $filePath = $backupPath.DIRECTORY_SEPARATOR.$filename;

        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        if ($dbConfig['driver'] === 'sqlite') {
            $this->backupSqlite($dbConfig, $filePath);
        } elseif ($dbConfig['driver'] === 'mysql') {
            $this->backupMysql($dbConfig, $filePath);
        } elseif ($dbConfig['driver'] === 'pgsql') {
            $this->backupPostgresql($dbConfig, $filePath);
        } else {
            throw new \Exception('Unsupported database driver: '.$dbConfig['driver']);
        }

        return $filePath;
    }

    /**
     * Backup SQLite database.
     */
    private function backupSqlite(array $config, string $backupPath): void
    {
        $databasePath = $config['database'];

        if (! File::exists($databasePath)) {
            throw new \Exception('Database file not found: '.$databasePath);
        }

        // For SQLite, we can use the .dump command via sqlite3 CLI or just copy
        // Let's create a proper SQL dump
        $pdo = DB::connection()->getPdo();
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

        $dump = "-- SQLite Database Backup\n";
        $dump .= '-- Generated: '.date('Y-m-d H:i:s')."\n\n";
        $dump .= "PRAGMA foreign_keys=OFF;\n";
        $dump .= "BEGIN TRANSACTION;\n\n";

        foreach ($tables as $table) {
            $tableName = $table->name;

            // Get table schema
            $createTable = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name=?", [$tableName]);
            if (! empty($createTable)) {
                $dump .= "-- Table: {$tableName}\n";
                $dump .= "DROP TABLE IF EXISTS \"{$tableName}\";\n";
                $dump .= $createTable[0]->sql.";\n\n";
            }

            // Get table data
            $rows = DB::select("SELECT * FROM \"{$tableName}\"");
            foreach ($rows as $row) {
                $values = [];
                foreach ((array) $row as $value) {
                    if (is_null($value)) {
                        $values[] = 'NULL';
                    } elseif (is_numeric($value)) {
                        $values[] = $value;
                    } else {
                        $values[] = "'".str_replace("'", "''", $value)."'";
                    }
                }
                if (! empty($values)) {
                    $dump .= "INSERT INTO \"{$tableName}\" VALUES(".implode(',', $values).");\n";
                }
            }
            $dump .= "\n";
        }

        $dump .= "COMMIT;\n";
        $dump .= "PRAGMA foreign_keys=ON;\n";

        File::put($backupPath, $dump);
    }

    /**
     * Backup MySQL database.
     */
    private function backupMysql(array $config, string $backupPath): void
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'] ?? '';

        $command = sprintf(
            'mysqldump --host=%s --port=%d --user=%s --password=%s --skip-comments --no-tablespaces %s > %s',
            escapeshellarg($host),
            $port,
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($backupPath)
        );

        // For Windows compatibility
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = str_replace('>', '^>', $command);
        }

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(300); // 5 minutes timeout

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            // If mysqldump is not available, fallback to PHP-based backup
            $this->backupMysqlPHP($database, $backupPath);
        }
    }

    /**
     * Backup MySQL database using PHP (fallback method).
     */
    private function backupMysqlPHP(string $database, string $backupPath): void
    {
        $tables = DB::select('SHOW TABLES');
        $dump = "-- MySQL Database Backup\n";
        $dump .= '-- Generated: '.date('Y-m-d H:i:s')."\n\n";
        $dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];

            // Get create table statement
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $dump .= "-- Table: {$tableName}\n";
            $dump .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $dump .= $createTable[0]->{'Create Table'}.";\n\n";

            // Get table data
            $rows = DB::select("SELECT * FROM `{$tableName}`");
            if (count($rows) > 0) {
                $dump .= "INSERT INTO `{$tableName}` VALUES\n";
                $rowValues = [];
                foreach ($rows as $row) {
                    $values = [];
                    foreach ((array) $row as $value) {
                        if (is_null($value)) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'".addslashes($value)."'";
                        }
                    }
                    $rowValues[] = '('.implode(',', $values).')';
                }
                $dump .= implode(",\n", $rowValues).";\n\n";
            }
        }

        $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
        File::put($backupPath, $dump);
    }

    /**
     * Backup PostgreSQL database.
     */
    private function backupPostgresql(array $config, string $backupPath): void
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 5432;
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'] ?? '';

        $command = sprintf(
            'PGPASSWORD=%s pg_dump --host=%s --port=%d --username=%s --format=plain --file=%s %s',
            escapeshellarg($password),
            escapeshellarg($host),
            $port,
            escapeshellarg($username),
            escapeshellarg($backupPath),
            escapeshellarg($database)
        );

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(300);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            throw new \Exception('Failed to create PostgreSQL backup. Make sure pg_dump is installed and accessible.');
        }
    }

    /**
     * Restore database from SQL file.
     */
    private function restoreFromFile(string $filePath): void
    {
        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        if ($dbConfig['driver'] === 'sqlite') {
            $this->restoreSqlite($filePath);
        } elseif ($dbConfig['driver'] === 'mysql') {
            $this->restoreMysql($dbConfig, $filePath);
        } elseif ($dbConfig['driver'] === 'pgsql') {
            $this->restorePostgresql($dbConfig, $filePath);
        } else {
            throw new \Exception('Unsupported database driver: '.$dbConfig['driver']);
        }
    }

    /**
     * Restore SQLite database.
     */
    private function restoreSqlite(string $backupPath): void
    {
        $sql = File::get($backupPath);
        DB::unprepared($sql);
    }

    /**
     * Restore MySQL database.
     */
    private function restoreMysql(array $config, string $backupPath): void
    {
        $sql = File::get($backupPath);
        DB::unprepared($sql);
    }

    /**
     * Restore PostgreSQL database.
     */
    private function restorePostgresql(array $config, string $backupPath): void
    {
        $sql = File::get($backupPath);
        DB::unprepared($sql);
    }

    /**
     * Get database information.
     */
    private function getDatabaseInfo(): array
    {
        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        $info = [
            'driver' => $dbConfig['driver'],
            'connection' => $connection,
        ];

        if ($dbConfig['driver'] === 'sqlite') {
            $info['database'] = basename($dbConfig['database']);
            $info['path'] = $dbConfig['database'];
            if (File::exists($dbConfig['database'])) {
                $info['size'] = $this->formatBytes(File::size($dbConfig['database']));
            }
        } else {
            $info['database'] = $dbConfig['database'] ?? 'N/A';
            $info['host'] = $dbConfig['host'] ?? 'N/A';
        }

        // Get table count
        try {
            if ($dbConfig['driver'] === 'sqlite') {
                $tables = DB::select("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            } elseif ($dbConfig['driver'] === 'mysql') {
                $tables = DB::select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?', [$dbConfig['database']]);
            } elseif ($dbConfig['driver'] === 'pgsql') {
                $tables = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'public'");
            }
            $info['tables'] = $tables[0]->count ?? 0;
        } catch (\Exception $e) {
            $info['tables'] = 'N/A';
        }

        return $info;
    }

    /**
     * Get backup storage path.
     */
    private function getBackupStoragePath(): string
    {
        return storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'database');
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

        return round($bytes, $precision).' '.$units[$i];
    }
}
