BEGIN;
DO $verify$
DECLARE
    target record;
    api_role text;
BEGIN
    FOR target IN SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename LOOP
        -- All Laravel tables remain readable through the existing trusted database role.
        EXECUTE format('SELECT 1 FROM public.%I LIMIT 0', target.tablename);
        FOREACH api_role IN ARRAY ARRAY['anon', 'authenticated'] LOOP
            EXECUTE format('SET LOCAL ROLE %I', api_role);
            BEGIN
                EXECUTE format('SELECT 1 FROM public.%I LIMIT 0', target.tablename);
                RAISE EXCEPTION 'Unexpected direct API access: %.%', api_role, target.tablename;
            EXCEPTION WHEN insufficient_privilege THEN
                NULL; -- Expected: table access revoked.
            END;
            RESET ROLE;
        END LOOP;
    END LOOP;
    -- Transactional Laravel cache write/read smoke test; never persisted.
    INSERT INTO public.cache (key, value, expiration)
        VALUES ('__rls_verification_20260929__', 'security-smoke-test', 0);
    IF NOT EXISTS (SELECT 1 FROM public.cache WHERE key = '__rls_verification_20260929__') THEN
        RAISE EXCEPTION 'Backend cache write/read failed';
    END IF;
    UPDATE public.cache SET value = 'verified' WHERE key = '__rls_verification_20260929__';
    DELETE FROM public.cache WHERE key = '__rls_verification_20260929__';
END
$verify$;
ROLLBACK;
