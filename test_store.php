<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$request = \Illuminate\Http\Request::create('/admin/clientes', 'POST', [
    'nombre' => 'Test Client 500',
    'celular' => '3001234567',
    'direccion' => 'Calle 123',
    'barrio' => 'Centro',
    'codigo_postal' => '520001',
]);

try {
    $c = app('App\Http\Controllers\ClienteController');
    $response = $c->store($request);
    echo "OK!";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
