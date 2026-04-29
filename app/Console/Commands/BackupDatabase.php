<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    /**
     * Se ejecuta con: php artisan db:backup
     */
    protected $signature = 'db:backup';

    /**
     * Descripcion mostrada en artisan list.
     */
    protected $description = 'Genera un backup SQL de la base de datos PostgreSQL y lo guarda en storage/app/backups/';

    public function handle(): int
    {
        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port', 5432);
        $database = config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');

        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'backup_'.now()->format('Y-m-d_His').'.sql';
        $filepath = $backupDir.DIRECTORY_SEPARATOR.$filename;

        $pgDumpBinary = trim((string) shell_exec('command -v pg_dump 2>/dev/null'));

        if ($pgDumpBinary === '') {
            $this->error('No se encontro pg_dump en el contenedor o sistema actual.');
            $this->line('Asegura que la imagen instale PostgreSQL client 16 antes de ejecutar el backup.');

            return self::FAILURE;
        }

        $command = sprintf(
            'PGPASSWORD=%s %s -h %s -p %s -U %s -F p -x -O %s > %s 2>&1',
            escapeshellarg($password),
            escapeshellarg($pgDumpBinary),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        $this->info("Iniciando backup de la base de datos '{$database}'...");
        $this->line("Host: {$host}:{$port}");

        exec($command, $output, $resultCode);

        if ($resultCode !== 0 || ! file_exists($filepath)) {
            $this->error('El backup fallo. Verifica pg_dump y las credenciales configuradas.');

            foreach ($output as $line) {
                $this->line($line);
            }

            return self::FAILURE;
        }

        $sizeKb = round(filesize($filepath) / 1024, 2);

        $this->info('Backup completado exitosamente.');
        $this->line("Archivo: {$filepath}");
        $this->line("Tamano: {$sizeKb} KB");
        $this->newLine();
        $this->comment("Para restaurar: psql -h HOST -U USER -d DATABASE < {$filename}");

        return self::SUCCESS;
    }
}
