<?php

uses(Tests\TestCase::class);

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

it('uses railway postgres variables when db variables are missing', function () {
    setDeploymentEnv('DB_CONNECTION', null);
    setDeploymentEnv('DB_URL', null);
    setDeploymentEnv('DATABASE_URL', 'postgresql://railway:secret@postgres.railway.internal:6543/lavanderia?sslmode=require');
    setDeploymentEnv('DB_HOST', null);
    setDeploymentEnv('PGHOST', 'postgres.railway.internal');
    setDeploymentEnv('DB_PORT', null);
    setDeploymentEnv('PGPORT', '6543');
    setDeploymentEnv('DB_DATABASE', null);
    setDeploymentEnv('PGDATABASE', 'lavanderia');
    setDeploymentEnv('DB_USERNAME', null);
    setDeploymentEnv('PGUSER', 'railway');
    setDeploymentEnv('DB_PASSWORD', null);
    setDeploymentEnv('PGPASSWORD', 'secret');
    setDeploymentEnv('DB_SCHEMA', null);
    setDeploymentEnv('PGSCHEMA', 'public');
    setDeploymentEnv('DB_SSLMODE', null);
    setDeploymentEnv('PGSSLMODE', 'require');

    $config = require __DIR__.'/../../config/database.php';

    expect($config['default'])->toBe('pgsql')
        ->and($config['connections']['pgsql']['url'])->toBe('postgresql://railway:secret@postgres.railway.internal:6543/lavanderia?sslmode=require')
        ->and($config['connections']['pgsql']['host'])->toBe('postgres.railway.internal')
        ->and($config['connections']['pgsql']['port'])->toBe('6543')
        ->and($config['connections']['pgsql']['database'])->toBe('lavanderia')
        ->and($config['connections']['pgsql']['username'])->toBe('railway')
        ->and($config['connections']['pgsql']['password'])->toBe('secret')
        ->and($config['connections']['pgsql']['search_path'])->toBe('public')
        ->and($config['connections']['pgsql']['sslmode'])->toBe('require');
});

it('keeps explicit db variables above railway fallbacks', function () {
    setDeploymentEnv('DB_CONNECTION', 'pgsql');
    setDeploymentEnv('DB_URL', null);
    setDeploymentEnv('DATABASE_URL', 'postgresql://railway:secret@postgres.railway.internal:6543/lavanderia');
    setDeploymentEnv('DB_HOST', 'db.internal');
    setDeploymentEnv('PGHOST', 'postgres.railway.internal');
    setDeploymentEnv('DB_PORT', '5432');
    setDeploymentEnv('PGPORT', '6543');
    setDeploymentEnv('DB_DATABASE', 'custom_db');
    setDeploymentEnv('PGDATABASE', 'lavanderia');
    setDeploymentEnv('DB_USERNAME', 'custom_user');
    setDeploymentEnv('PGUSER', 'railway');
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
