<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // Only admins can access backups
            if (auth()->check() && auth()->user()->role === 'admin') {
                return $next($request);
            }
            abort(403, 'Unauthorized');
        });
    }

    /**
     * Show backup management page
     */
    public function index()
    {
        $backups = Backup::orderBy('created_at', 'desc')->get();
        return view('settings.backups', compact('backups'));
    }

    /**
     * Create a full backup (database + media)
     */
    public function create(Request $request)
    {
        try {
            $timestamp = now()->format('Y-m-d_His');
            $backupName = "backup_{$timestamp}.zip";
            $backupPath = storage_path('backups/' . $backupName);

            // Ensure backups directory exists
            if (!File::isDirectory(storage_path('backups'))) {
                File::makeDirectory(storage_path('backups'), 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($backupPath, ZipArchive::CREATE) !== true) {
                throw new \Exception('Could not create backup zip file');
            }

            // 1. Export database
            $dbDump = $this->exportDatabase();
            $zip->addFromString('database.sql', $dbDump);

            // 2. Add settings
            $settings = DB::table('settings')->get();
            $zip->addFromString('settings.json', json_encode($settings, JSON_PRETTY_PRINT));

            // 3. Add media files from storage/app/public
            $mediaPath = storage_path('app/public');
            if (File::isDirectory($mediaPath)) {
                $this->addDirectoryToZip($zip, $mediaPath, 'media');
            }

            // 4. Add version info
            $versionInfo = [
                'app_version' => file_get_contents(base_path('version.json')),
                'backup_date' => now()->toDateTimeString(),
                'php_version' => phpversion(),
                'laravel_version' => app()->version(),
            ];
            $zip->addFromString('backup_info.json', json_encode($versionInfo, JSON_PRETTY_PRINT));

            $zip->close();

            // Record backup in database
            $fileSize = File::size($backupPath);
            $notes = $request->input('notes', 'Automatic backup');

            Backup::create([
                'filename' => $backupName,
                'size' => $fileSize,
                'notes' => $notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Backup created successfully ({$this->formatBytes($fileSize)})",
                'backup' => [
                    'filename' => $backupName,
                    'size' => $this->formatBytes($fileSize),
                    'created_at' => now()->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a backup file
     */
    public function download($id)
    {
        $backup = Backup::findOrFail($id);
        $path = $backup->download_path;

        if (!File::exists($path)) {
            abort(404, 'Backup file not found');
        }

        return response()->download($path, $backup->filename);
    }

    /**
     * Restore a backup
     */
    public function restore(Request $request, $id)
    {
        try {
            $backup = Backup::findOrFail($id);
            $backupPath = $backup->download_path;

            if (!File::exists($backupPath)) {
                throw new \Exception('Backup file not found');
            }

            // Extract and restore
            $zip = new ZipArchive();
            if ($zip->open($backupPath) !== true) {
                throw new \Exception('Could not open backup file');
            }

            $extractPath = storage_path('backups/extract_' . now()->timestamp);
            $zip->extractTo($extractPath);
            $zip->close();

            // 1. Restore database
            if (File::exists($extractPath . '/database.sql')) {
                $this->restoreDatabase(File::get($extractPath . '/database.sql'));
            }

            // 2. Restore settings
            if (File::exists($extractPath . '/settings.json')) {
                $settings = json_decode(File::get($extractPath . '/settings.json'), true);
                DB::table('settings')->truncate();
                foreach ($settings as $setting) {
                    DB::table('settings')->insert($setting);
                }
            }

            // 3. Restore media files
            if (File::isDirectory($extractPath . '/media')) {
                $mediaDestPath = storage_path('app/public');
                File::copyDirectory($extractPath . '/media', $mediaDestPath);
            }

            // Clean up
            File::deleteDirectory($extractPath);

            // Clear caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');

            return response()->json([
                'success' => true,
                'message' => 'Backup restored successfully. Please log in again.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a backup
     */
    public function delete($id)
    {
        $backup = Backup::findOrFail($id);
        $path = $backup->download_path;

        if (File::exists($path)) {
            File::delete($path);
        }

        $backup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Backup deleted',
        ]);
    }

    /**
     * Clear application cache
     */
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cache clear failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export database to SQL
     */
    private function exportDatabase(): string
    {
        $tables = DB::select('SHOW TABLES');
        $output = "";

        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            
            // Get CREATE TABLE statement
            $createTable = DB::select('SHOW CREATE TABLE ' . $tableName);
            $output .= $createTable[0]->{'Create Table'} . ";\n\n";

            // Get table data
            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $values = array_map(function($val) {
                    return is_null($val) ? 'NULL' : "'" . str_replace("'", "''", $val) . "'";
                }, (array)$row);
                $output .= "INSERT INTO `{$tableName}` VALUES (" . implode(',', $values) . ");\n";
            }
            $output .= "\n";
        }

        return $output;
    }

    /**
     * Restore database from SQL
     */
    private function restoreDatabase(string $sql)
    {
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                DB::statement($statement);
            }
        }
    }

    /**
     * Add directory to zip recursively
     */
    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $zipPath)
    {
        $files = File::allFiles($dir);
        foreach ($files as $file) {
            $relativePath = $zipPath . '/' . $file->getRelativePathname();
            $zip->addFile($file->getRealPath(), $relativePath);
        }
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
