<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Servicio centralizado de caché para el dashboard administrativo.
 *
 * Totalmente fail-safe: si el driver de caché falla por cualquier motivo
 * (tabla no existe, conexión rota, etc.), el método remember() ejecuta
 * el callback directamente sin propagar la excepción.
 *
 * TTLs por tipo de dato:
 *   - live    →  2 min  (estadísticas que cambian frecuentemente)
 *   - charts  →  5 min  (gráficas por día)
 *   - history →  10 min (períodos cerrados)
 *   - users   →  5 min  (listas de usuarios)
 */
class DashboardCacheService
{
    private const PREFIX = 'lav:dashboard:';

    public const TTL_LIVE    = 120;
    public const TTL_CHARTS  = 300;
    public const TTL_HISTORY = 600;
    public const TTL_USERS   = 300;

    /**
     * Obtiene (o genera y guarda) un valor cacheado.
     * Si el caché no está disponible, ejecuta el callback directamente.
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        try {
            return Cache::remember(self::PREFIX . $key, $ttl, $callback);
        } catch (Throwable) {
            // Caché no disponible → ejecutar el callback directamente
            return $callback();
        }
    }

    /**
     * Invalida todo el caché del dashboard de forma segura.
     */
    public function flush(): void
    {
        $keys = [
            'estadisticas', 'financiero', 'facturas_dia', 'produccion_dia',
            'periodos_cerrados', 'pago_recolectores', 'recolectores_facturas',
            'prendas_mes', 'total_usuarios', 'usuarios_lista',
            'recolectores_activos', 'clientes_recolector', 'historial_pagos_rec',
        ];

        foreach ($keys as $key) {
            $this->forget($key);
        }
    }

    public function flushFacturas(): void
    {
        foreach (['estadisticas', 'financiero', 'facturas_dia', 'recolectores_facturas', 'periodos_cerrados', 'pago_recolectores'] as $key) {
            $this->forget($key);
        }
    }

    public function flushProduccion(): void
    {
        foreach (['estadisticas', 'financiero', 'produccion_dia', 'prendas_mes', 'periodos_cerrados'] as $key) {
            $this->forget($key);
        }
    }

    public function forget(string $key): void
    {
        try {
            Cache::forget(self::PREFIX . $key);
        } catch (Throwable) {
            // silencioso si el caché no está disponible
        }
    }
}
