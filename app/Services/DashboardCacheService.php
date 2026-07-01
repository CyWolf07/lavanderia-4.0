<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Servicio centralizado de caché para el dashboard administrativo.
 *
 * - Agrupa todas las claves de caché en un namespace único (`lav:dashboard:`).
 * - TTLs configurados por tipo de dato:
 *     · Estadísticas live (tarjetas)   →  2 min  (datos que cambian frecuentemente)
 *     · Gráficas (por día)             →  5 min  (suficiente precisión)
 *     · Historial de períodos cerrados →  10 min (cambia pocas veces al día)
 *     · Datos de usuarios              →  5 min
 * - flush() invalida TODO el namespace del dashboard de una vez.
 */
class DashboardCacheService
{
    /** Prefijo de namespace para todas las claves */
    private const PREFIX = 'lav:dashboard:';

    /** TTLs en segundos */
    public const TTL_LIVE    = 120;   // 2 min
    public const TTL_CHARTS  = 300;   // 5 min
    public const TTL_HISTORY = 600;   // 10 min
    public const TTL_USERS   = 300;   // 5 min

    /**
     * Obtiene (o genera y guarda) un valor cacheado.
     *
     * @param  string    $key      Identificador del dato dentro del namespace
     * @param  int       $ttl      Tiempo de vida en segundos
     * @param  callable  $callback Función que genera el valor si no existe en caché
     * @return mixed
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember(self::PREFIX . $key, $ttl, $callback);
    }

    /**
     * Guarda un valor directamente en caché.
     */
    public function put(string $key, mixed $value, int $ttl): void
    {
        Cache::put(self::PREFIX . $key, $value, $ttl);
    }

    /**
     * Elimina un valor específico del caché.
     */
    public function forget(string $key): void
    {
        Cache::forget(self::PREFIX . $key);
    }

    /**
     * Invalida todo el caché del dashboard.
     * Se debe llamar cuando se realizan escrituras que afectan los datos mostrados:
     * - Cerrar quincena
     * - Editar/eliminar producción o facturas
     * - Cambiar estado de usuario
     */
    public function flush(): void
    {
        $keys = [
            'estadisticas',
            'financiero',
            'facturas_dia',
            'produccion_dia',
            'periodos_cerrados',
            'pago_recolectores',
            'recolectores_facturas',
            'prendas_mes',
        ];

        foreach ($keys as $key) {
            Cache::forget(self::PREFIX . $key);
        }
    }

    /**
     * Invalida solo las claves que afecta una operación de escritura en facturas.
     */
    public function flushFacturas(): void
    {
        Cache::forget(self::PREFIX . 'estadisticas');
        Cache::forget(self::PREFIX . 'financiero');
        Cache::forget(self::PREFIX . 'facturas_dia');
        Cache::forget(self::PREFIX . 'recolectores_facturas');
        Cache::forget(self::PREFIX . 'periodos_cerrados');
    }

    /**
     * Invalida solo las claves que afecta una operación de escritura en producción.
     */
    public function flushProduccion(): void
    {
        Cache::forget(self::PREFIX . 'estadisticas');
        Cache::forget(self::PREFIX . 'financiero');
        Cache::forget(self::PREFIX . 'produccion_dia');
        Cache::forget(self::PREFIX . 'prendas_mes');
    }
}
