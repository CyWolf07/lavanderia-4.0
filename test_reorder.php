<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

DB::transaction(function () {
    // Para evitar violaciones de restricción UNIQUE al reordenar, 
    // primero los movemos a números negativos temporalmente.
    $clientes = \App\Models\Cliente::orderBy('id')->get();
    foreach ($clientes as $cliente) {
        $cliente->update(['numero_cliente' => -$cliente->id]);
    }
    
    // Luego los reordenamos limpiamente de 1 a N
    foreach ($clientes as $index => $cliente) {
        $cliente->update(['numero_cliente' => $index + 1]);
    }
});
echo "Reordenamiento completado.";
