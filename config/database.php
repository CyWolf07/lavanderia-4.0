<?php

use Illuminate\Support\Str;

$databaseUrl = env('DB_URL') ?: env('DATABASE_URL');
$postgresHost = env('DB_HOST') ?: env('PGHOST');

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION') ?: ($databaseUrl || $postgresHost ? 'pgsql' : 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => $databaseUrl,
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
            'transaction_mode' => 'DEFERRED',
        ],

        'pgsql' => [
            'driver'         => 'pgsql',
            'url'            => $databaseUrl,
            'host'           => env('DB_HOST', env('PGHOST', '127.0.0.1')),
            'port'           => env('DB_PORT', env('PGPORT', '5432')),
            'database'       => env('DB_DATABASE', env('PGDATABASE', 'laravel')),
            'username'       => env('DB_USERNAME', env('PGUSER', 'root')),
            'password'       => env('DB_PASSWORD', env('PGPASSWORD', '')),
            'charset'        => env('DB_CHARSET', 'utf8'),
            'prefix'         => '',
            'prefix_indexes' => true,
            'search_path'    => env('DB_SCHEMA', env('PGSCHEMA', 'public')),
            'sslmode'        => env('DB_SSLMODE', env('PGSSLMODE', 'prefer')),

            // ── Connection Pool & Timeouts ────────────────────────────────────
            // Estos valores se usan cuando la app corre detrás de PgBouncer
            // o cuando se escala con más workers (Octane, FPM, queue workers).
            'options'        => [
                // Abortar consultas que superen este tiempo (ms). Previene consultas
                // largas que bloqueen conexiones bajo carga alta.
                PDO::ATTR_TIMEOUT              => env('DB_STATEMENT_TIMEOUT', 30),

                // Mantener la conexión viva entre requests (reutilización de socket)
                PDO::ATTR_PERSISTENT           => env('DB_PERSISTENT', false),

                // Errores como excepciones PHP (no modo silencioso)
                PDO::ATTR_ERRMODE              => PDO::ERRMODE_EXCEPTION,

                // No emular prepared statements; usa los nativos de PostgreSQL
                PDO::ATTR_EMULATE_PREPARES     => false,
            ],

            // statement_timeout (ms): limite de tiempo por consulta SQL
            // application_name: visible en pg_stat_activity para monitoreo
            // idle_in_transaction_session_timeout: aborta transacciones que se
            // quedan abiertas demasiado tiempo (peón de concurrencia alta).
            'init_commands'  => [
                'SET statement_timeout = ' . env('DB_STATEMENT_TIMEOUT_MS', 30000),
                "SET application_name = '" . env('APP_NAME', 'lavanderia') . "'",
                'SET idle_in_transaction_session_timeout = ' . env('DB_IDLE_TXN_TIMEOUT_MS', 60000),
                'SET lock_timeout = ' . env('DB_LOCK_TIMEOUT_MS', 5000),
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug((string) env('APP_NAME', 'laravel')).'-database-'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'max_retries' => env('REDIS_MAX_RETRIES', 3),
            'backoff_algorithm' => env('REDIS_BACKOFF_ALGORITHM', 'decorrelated_jitter'),
            'backoff_base' => env('REDIS_BACKOFF_BASE', 100),
            'backoff_cap' => env('REDIS_BACKOFF_CAP', 1000),
        ],

    ],

];
