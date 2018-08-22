BEGIN;
TRUNCATE elca_cache.items RESTART IDENTITY CASCADE;
TRUNCATE elca.benchmark_systems RESTART IDENTITY CASCADE;
TRUNCATE elca.projects RESTART IDENTITY CASCADE;
TRUNCATE public.users RESTART IDENTITY CASCADE;
TRUNCATE public.groups RESTART IDENTITY CASCADE;
TRUNCATE public.role_members RESTART IDENTITY CASCADE;
TRUNCATE elca.process_dbs RESTART IDENTITY CASCADE;
TRUNCATE elca.process_configs RESTART IDENTITY CASCADE;
DELETE FROM lcc.versions;
DELETE FROM elca.svg_patterns;
DELETE FROM public.media;

REFRESH MATERIALIZED VIEW elca.indicators_v;

INSERT INTO public.groups (id, name, is_usergroup) VALUES (1, 'admin_bbsr', true);
INSERT INTO public.users (id, auth_name, auth_key, auth_method, group_id, is_locked, status)
VALUES (1, 'admin_bbsr', '$1$a8d25dbb$TXSauv1sig.1zRrCiO09h0', 3, 1, false, 1);
INSERT INTO public.role_members (role_id, group_id) VALUES ((SELECT node_id FROM public.roles WHERE role_name = 'Administrator'), 1);
COMMIT;
