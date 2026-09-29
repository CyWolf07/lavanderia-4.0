-- Laravel owns authentication/authorization; browser/API roles have no direct access.
-- Preserve postgres owner/BYPASSRLS and existing service_role privileges.
-- No FORCE RLS, auth.uid() mapping, data changes, or new privileged functions.
SET LOCAL lock_timeout = '5s';
DO $security$
DECLARE
    target record;
BEGIN
    FOR target IN
        SELECT c.oid, c.relname FROM pg_class c
        JOIN pg_namespace n ON n.oid = c.relnamespace
        WHERE n.nspname = 'public' AND c.relkind IN ('r', 'p')
          AND c.relname = ANY (ARRAY['audit_events', 'bloques_numero_orden', 'cache', 'cache_locks', 'clientes', 'enterprise_access_controls', 'factura_recolector_detalles', 'facturas_recolector', 'failed_jobs', 'gastos', 'historial_producciones', 'incongruencias_produccion', 'incongruencias_recolector', 'job_batches', 'jobs', 'mensajes', 'migrations', 'notifications', 'pagos_recolector', 'password_reset_tokens', 'pqrs', 'prenda_equivalencias', 'prendas', 'producciones', 'puntual_recordatorios', 'puntual_suscripciones', 'recolector_prendas', 'roles', 'sessions', 'system_settings', 'users'])
        ORDER BY c.relname
    LOOP
        EXECUTE format('ALTER TABLE public.%I ENABLE ROW LEVEL SECURITY', target.relname);
        EXECUTE format('REVOKE ALL PRIVILEGES ON TABLE public.%I FROM PUBLIC, anon, authenticated', target.relname);
        IF NOT EXISTS (SELECT 1 FROM pg_policy WHERE polrelid = target.oid AND polname = 'laravel_backend_only') THEN
            EXECUTE format('CREATE POLICY laravel_backend_only ON public.%I AS RESTRICTIVE FOR ALL TO anon, authenticated USING (false) WITH CHECK (false)', target.relname);
        END IF;
    END LOOP;

    FOR target IN
        SELECT DISTINCT seq.relname FROM pg_class seq
        JOIN pg_namespace ns ON ns.oid = seq.relnamespace
        JOIN pg_depend d ON d.objid = seq.oid AND d.classid = 'pg_class'::regclass
        JOIN pg_class tab ON tab.oid = d.refobjid
        JOIN pg_namespace tn ON tn.oid = tab.relnamespace
        WHERE ns.nspname = 'public' AND seq.relkind = 'S'
          AND tn.nspname = 'public' AND d.deptype IN ('a', 'i')
          AND tab.relname = ANY (ARRAY['audit_events', 'bloques_numero_orden', 'cache', 'cache_locks', 'clientes', 'enterprise_access_controls', 'factura_recolector_detalles', 'facturas_recolector', 'failed_jobs', 'gastos', 'historial_producciones', 'incongruencias_produccion', 'incongruencias_recolector', 'job_batches', 'jobs', 'mensajes', 'migrations', 'notifications', 'pagos_recolector', 'password_reset_tokens', 'pqrs', 'prenda_equivalencias', 'prendas', 'producciones', 'puntual_recordatorios', 'puntual_suscripciones', 'recolector_prendas', 'roles', 'sessions', 'system_settings', 'users'])
    LOOP
        EXECUTE format('REVOKE ALL PRIVILEGES ON SEQUENCE public.%I FROM PUBLIC, anon, authenticated', target.relname);
    END LOOP;
END
$security$;

-- Prevent postgres-created Laravel tables/sequences from being auto-exposed.
-- Future tables must also enable RLS in their own migrations.
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public
    REVOKE ALL PRIVILEGES ON TABLES FROM PUBLIC, anon, authenticated;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public
    REVOKE ALL PRIVILEGES ON SEQUENCES FROM PUBLIC, anon, authenticated;
