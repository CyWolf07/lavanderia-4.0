<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\FacturaRecolector;
use App\Models\Produccion;

class FixData extends Command
{
    protected $signature = 'app:fix-data';
    protected $description = 'Fix data';

    public function handle()
    {
        $this->info("Facturas count: " . FacturaRecolector::count());
        $this->info("Producciones count: " . Produccion::count());
        
        $facturas = FacturaRecolector::where('total', 69000)->get();
        foreach ($facturas as $f) {
            $f->detalles()->delete();
            $f->delete();
            $this->info("Deleted factura 69000 (ID $f->id)");
        }
        $this->info("Done");
    }
}
