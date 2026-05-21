<?php

use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->deploymentEnvKeys = [
        'DB_CONNECTION',
        'DB_URL',
        'DATABASE_URL',
        'DB_HOST',
        'PGHOST',
        'DB_PORT',
        'PGPORT',
        'DB_DATABASE',
        'PGDATABASE',
        'DB_USERNAME',
        'PGUSER',
        'DB_PASSWORD',
        'PGPASSWORD',
        'DB_SCHEMA',
        'PGSCHEMA',
        'DB_SSLMODE',
        'PGSSLMODE',
    ];

    $this->originalDeploymentEnv = [];

    foreach ($this->deploymentEnvKeys as $key) {
        $value = getenv($key);
        $this->originalDeploymentEnv[$key] = $value === false ? null : $value;
    }
});

afterEach(function () {
    foreach ($this->deploymentEnvKeys as $key) {
        setDeploymentEnv($key, $this->originalDeploymentEnv[$key]);
    }
});

it('uses external postgres variables when db variables are missing', function () {
    setDeploymentEnv('DB_CONNECTION', '');
    setDeploymentEnv('DB_URL', null);
    setDeploymentEnv('DATABASE_URL', 'postgresql://supabase:secret@db.supabase.co:6543/lavanderia?sslmode=require');
    setDeploymentEnv('DB_HOST', null);
    setDeploymentEnv('PGHOST', 'db.supabase.co');
    setDeploymentEnv('DB_PORT', null);
    setDeploymentEnv('PGPORT', '6543');
    setDeploymentEnv('DB_DATABASE', null);
    setDeploymentEnv('PGDATABASE', 'lavanderia');
    setDeploymentEnv('DB_USERNAME', null);
    setDeploymentEnv('PGUSER', 'postgres');
    setDeploymentEnv('DB_PASSWORD', null);
    setDeploymentEnv('PGPASSWORD', 'secret');
    setDeploymentEnv('DB_SCHEMA', null);
    setDeploymentEnv('PGSCHEMA', 'public');
    setDeploymentEnv('DB_SSLMODE', null);
    setDeploymentEnv('PGSSLMODE', 'require');

    $config = require __DIR__.'/../../config/database.php';

    expect($config['default'])->toBe('pgsql')
        ->and($config['connections']['pgsql']['url'])->toBe('postgresql://supabase:secret@db.supabase.co:6543/lavanderia?sslmode=require')
        ->and($config['connections']['pgsql']['host'])->toBe('db.supabase.co')
        ->and($config['connections']['pgsql']['port'])->toBe('6543')
        ->and($config['connections']['pgsql']['database'])->toBe('lavanderia')
        ->and($config['connections']['pgsql']['username'])->toBe('postgres')
        ->and($config['connections']['pgsql']['password'])->toBe('secret')
        ->and($config['connections']['pgsql']['search_path'])->toBe('public')
        ->and($config['connections']['pgsql']['sslmode'])->toBe('require');
});

it('does not let an empty db url hide database url', function () {
    setDeploymentEnv('DB_CONNECTION', '');
    setDeploymentEnv('DB_URL', '');
    setDeploymentEnv('DATABASE_URL', 'postgresql://supabase:secret@aws-1-us-west-2.pooler.supabase.com:5432/postgres?sslmode=require');
    setDeploymentEnv('DB_HOST', null);
    setDeploymentEnv('PGHOST', null);

    $config = require __DIR__.'/../../config/database.php';

    expect($config['default'])->toBe('pgsql')
        ->and($config['connections']['pgsql']['url'])->toBe('postgresql://supabase:secret@aws-1-us-west-2.pooler.supabase.com:5432/postgres?sslmode=require');
});

it('keeps explicit db variables above external postgres fallbacks', function () {
    setDeploymentEnv('DB_CONNECTION', 'pgsql');
    setDeploymentEnv('DB_URL', null);
    setDeploymentEnv('DATABASE_URL', 'postgresql://supabase:secret@db.supabase.co:6543/lavanderia');
    setDeploymentEnv('DB_HOST', 'db.internal');
    setDeploymentEnv('PGHOST', 'db.supabase.co');
    setDeploymentEnv('DB_PORT', '5432');
    setDeploymentEnv('PGPORT', '6543');
    setDeploymentEnv('DB_DATABASE', 'custom_db');
    setDeploymentEnv('PGDATABASE', 'lavanderia');
    setDeploymentEnv('DB_USERNAME', 'custom_user');
    setDeploymentEnv('PGUSER', 'postgres');
    setDeploymentEnv('DB_PASSWORD', 'custom_secret');
    setDeploymentEnv('PGPASSWORD', 'secret');
    setDeploymentEnv('DB_SCHEMA', 'tenant');
    setDeploymentEnv('PGSCHEMA', 'public');
    setDeploymentEnv('DB_SSLMODE', 'prefer');
    setDeploymentEnv('PGSSLMODE', 'require');

    $config = require __DIR__.'/../../config/database.php';

    expect($config['default'])->toBe('pgsql')
        ->and($config['connections']['pgsql']['host'])->toBe('db.internal')
        ->and($config['connections']['pgsql']['port'])->toBe('5432')
        ->and($config['connections']['pgsql']['database'])->toBe('custom_db')
        ->and($config['connections']['pgsql']['username'])->toBe('custom_user')
        ->and($config['connections']['pgsql']['password'])->toBe('custom_secret')
        ->and($config['connections']['pgsql']['search_path'])->toBe('tenant')
        ->and($config['connections']['pgsql']['sslmode'])->toBe('prefer');
});

it('keeps the docker image using the container startup script', function () {
    $dockerfile = file_get_contents(__DIR__.'/../../Dockerfile');
    $startupScript = file_get_contents(__DIR__.'/../../docker/start-container.sh');

    expect($dockerfile)->toContain('CMD ["start-container"]')
        ->and($startupScript)->toContain('php artisan migrate --force')
        ->and($startupScript)->toContain('exec apache2-foreground');
});

it('keeps render configured for a free docker web service with external postgres', function () {
    $renderConfig = file_get_contents(__DIR__.'/../../render.yaml');

    expect($renderConfig)->toContain('runtime: docker')
        ->and($renderConfig)->toContain('plan: free')
        ->and($renderConfig)->toContain('dockerfilePath: ./Dockerfile')
        ->and($renderConfig)->toContain('healthCheckPath: /up')
        ->and($renderConfig)->toContain('key: DATABASE_URL')
        ->and($renderConfig)->not->toContain('type: pserv');
});

it('pins local docker postgres to version 16', function () {
    $dockerCompose = file_get_contents(__DIR__.'/../../docker-compose.yml');
    $dockerfile = file_get_contents(__DIR__.'/../../Dockerfile');

    expect($dockerCompose)->toContain('postgres:16-alpine')
        ->and($dockerfile)->toContain('postgresql-client-16')
        ->and($dockerfile)->toContain('docker-php-ext-install pdo pdo_pgsql bcmath intl');
});

it('keeps local env files out of the docker build context', function () {
    $dockerignore = file_get_contents(__DIR__.'/../../.dockerignore');

    expect($dockerignore)->toContain('.env')
        ->and($dockerignore)->toContain('.env.*');
});

function setDeploymentEnv(string $key, ?string $value): void
{
    if ($value === null) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);

        return;
    }

    putenv(sprintf('%s=%s', $key, $value));
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
