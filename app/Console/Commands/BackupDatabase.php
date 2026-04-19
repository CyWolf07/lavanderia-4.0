<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * BackupDatabase
 *
 * Comando artisan para generar un respaldo (dump) de la base de datos PostgreSQL.
 *
 * Uso:
 *   php artisan db:backup
 *
 * El archivo se guarda en: storage/app/backups/backup_YYYY-MM-DD_HHmmss.sql
 *
 * Requiere que `pg_dump` esté disponible en el PATH del sistema.
 * En local con Docker: el contenedor de la app ya lo incluye.
 * En Railway/Render: disponible por defecto en el entorno PHP+PostgreSQL.
 */
class BackupDatabase extends Command
{
    /**
     * Nombre y firma del comando artisan.
     * Se ejecuta con: php artisan db:backup
     */
    protected $signature = 'db:backup';

    /**
     * Descripción que aparece en: php artisan list
     */
    protected $description = 'Genera un backup SQL de la base de datos PostgreSQL y lo guarda en storage/app/backups/';

    /**
     * Ejecuta el comando de backup.
     * Lee las credenciales del .env y llama a pg_dump para exportar el esquema.
     */
    public function handle(): int
    {
        // ── 1. Leer configuración de la BD desde el .env ──────────────────────
        $host     = config('database.connections.pgsql.host');
        $port     = config('database.connections.pgsql.port', 5432);
        $database = config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');

        // ── 2. Preparar la carpeta de destino ──────────────────────────────────
        // Los backups se guardan en storage/app/backups/
        $backupDir = storage_path('app/backups');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true); // Crea la carpeta si no existe
        }

        // ── 3. Nombre del archivo con timestamp ────────────────────────────────
        // Ejemplo: backup_2025-04-18_214500.sql
        $filename  = 'backup_' . now()->format('Y-m-d_His') . '.sql';
        $filepath  = $backupDir . DIRECTORY_SEPARATOR . $filename;

        // ── 4. Construir el comando pg_dump ────────────────────────────────────
        // -F p = formato plain text SQL (compatible con cualquier PostgreSQL)
        // -x   = excluye los privilegios (GRANT/REVOKE) para portabilidad
        // -O   = excluye los SET OWNER para facilitar la restauración
        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s -F p -x -O %s > %s 2>&1',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        $this->info("⏳ Iniciando backup de la base de datos '{$database}'...");
        $this->line("   Host: {$host}:{$port}");

        // ── 5. Ejecutar el comando de backup ───────────────────────────────────
        exec($command, $output, $resultCode);

        // ── 6. Verificar resultado ─────────────────────────────────────────────
        if ($resultCode !== 0 || ! file_exists($filepath)) {
            // Si pg_dump falló, muestra el error y retorna código de error
            $this->error('❌ El backup falló. Verifica que pg_dump esté instalado y las credenciales sean correctas.');
            foreach ($output as $line) {
                $this->line("   {$line}");
            }
            return self::FAILURE;
        }

        // ── 7. Informar éxito con ruta y tamaño del archivo ───────────────────
        $sizeKb = round(filesize($filepath) / 1024, 2);
        $this->info("✅ Backup completado exitosamente.");
        $this->line("   📁 Archivo: {$filepath}");
        $this->line("   📦 Tamaño:  {$sizeKb} KB");
        $this->newLine();
        $this->comment("Para restaurar: psql -h HOST -U USER -d DATABASE < {$filename}");

        return self::SUCCESS;
    }
}
