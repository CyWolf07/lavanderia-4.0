# Supabase security remediation — 2026-09-29

Project: Lavanderia (`lbxnxuryktwpukwuaiwt`).
Applied remote migration: `20260929203057_protect_laravel_backend_tables`.
The local SQL filename matches the version returned by Supabase migration history.

## Evidence and access assumptions recorded before application

- Laravel uses Eloquent/PDO PostgreSQL and session authentication (`config/database.php`, `config/auth.php`). Application user IDs/roles are not Supabase Auth identities.
- Repository searches found no Supabase JS client, REST API, GraphQL client or service key integration in app/resources/android/config.
- Deployment documentation uses the pooled `postgres.PROJECT_REF` connection. All 31 live public tables are owned by `postgres`, which has BYPASSRLS. No custom application database roles were present.
- Live Supabase Auth has zero users; no Edge Functions or public Realtime publications were present. No public views or functions were present.
- The local .env uses SQLite. The production Laravel secret/connection was not inspected and an authenticated production web session was not exercised. Compatibility assumes the documented postgres deployment role (or an existing trusted BYPASSRLS connection).
- Legitimate application access goes through Laravel middleware/controllers for admin, programador, recolector and usuario. Anonymous and Supabase-authenticated Data API access is unintended for these tables.

## Protected inventory

All names below are in public. Thirty had RLS disabled; users had RLS enabled without policies.

| Domain | Tables |
| --- | --- |
| Identity and access | users, roles, password_reset_tokens, sessions, enterprise_access_controls, system_settings |
| Customers and communications | clientes, pqrs, mensajes (legacy), notifications, puntual_recordatorios, puntual_suscripciones |
| Orders and money | facturas_recolector, factura_recolector_detalles, bloques_numero_orden, gastos, pagos_recolector, incongruencias_recolector |
| Laundry and catalogs | prendas, recolector_prendas, prenda_equivalencias, producciones, historial_producciones, incongruencias_produccion |
| Audit and framework infrastructure | audit_events, migrations, cache, cache_locks, jobs, job_batches, failed_jobs |

Sensitive paths include customer contact/address data, invoice/payment records, password hashes/reset tokens, serialized sessions/jobs, push subscriptions and enterprise settings. Public catalog names do not imply a legitimate public API contract.

## Access design

- Enable RLS on all 31 tables.
- Add restrictive `laravel_backend_only`, FOR ALL TO anon, authenticated, USING (false), WITH CHECK (false).
- Revoke ALL table privileges from PUBLIC, anon and authenticated, including TRUNCATE (not covered by RLS), and revoke associated sequence privileges.
- Preserve existing postgres and service_role grants. Do not FORCE RLS or change table owners.
- Revoke postgres default table/sequence grants to PUBLIC/anon/authenticated in public, so future Laravel migrations do not automatically expose newly created tables.
- No per-user auth.uid() policy: Laravel does not issue Supabase user JWTs or establish a Supabase Auth UUID mapping.
- No data deletion, role creation, new privileged function or changes to auth/storage/realtime schemas.

The service_role key remains privileged; it must remain server-only. This remediation blocks direct API bypass of Laravel authorization; it does not implement row isolation inside Laravel's privileged database connection.

## Verification results

- Before: 30 ERROR rls_disabled_in_public + 1 INFO rls_enabled_no_policy.
- After: security advisor lints = [].
- 31/31 public tables have RLS; 31 restrictive deny policies have both false predicates.
- anon/authenticated: 0 effective table, column or sequence privileges across public.
- postgres/service_role: CRUD privileges preserved on 31/31 tables.
- Live SQL role probes: all 62 table reads under anon/authenticated rejected with insufficient_privilege; backend reads succeed for all 31.
- Live backend cache INSERT/SELECT/UPDATE/DELETE smoke test succeeds inside a transaction followed by ROLLBACK.
- Supabase migration history confirms the version above.

Verification SQL: `supabase/tests/laravel_backend_access.sql`. Run through the trusted postgres connection; the transaction leaves no smoke-test data.

## Deployment and follow-up

The migration is already applied remotely. These SQL files document the applied Supabase security change; Laravel's schema history remains in database/migrations. This directory is NOT a full Supabase schema baseline: do not db reset/db push it as a replacement for Laravel's migration history.

Future tables must enable RLS in their creating migration and follow this backend-only access model. Default privileges changed here apply to postgres-created objects only. Supabase-managed owners (including supabase_admin) retain their existing defaults; any tables created by other roles require a separate privilege review.

A future direct browser/Realtime integration needs an explicit auth mapping and new reviewed grants/policies; it will intentionally fail under today's rules. Migrating Laravel to a dedicated least-privilege runtime role is a separate deployment change requiring a role-specific policy design.

Do not roll back by disabling RLS or restoring blanket API grants. If an unexpected legitimate consumer needs access, add a reviewed narrow policy and required grants, or fix its backend credentials. The policy is deliberately restrictive and must be reviewed before adding a permissive API policy.

References:
- [RLS security model](https://supabase.com/docs/guides/database/postgres/row-level-security)
- [RLS-disabled advisor remediation](https://supabase.com/docs/guides/database/database-linter?lint=0013_rls_disabled_in_public)
