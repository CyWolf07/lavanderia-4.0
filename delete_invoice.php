$f = App\Models\FacturaRecolector::where('total', 69000)->first();
if ($f) {
    $f->detalles()->delete();
    $f->delete();
    echo "Deleted factura 69000\n";
} else {
    echo "Factura 69000 not found\n";
}
