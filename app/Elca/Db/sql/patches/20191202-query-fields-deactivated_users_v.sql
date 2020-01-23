BEGIN;
SELECT public.register_patch('20191202-query-fields-deactivated_users_v.sql', 'ublic');  
  
DROP VIEW IF EXISTS public.users_v;
  
CREATE OR REPLACE VIEW public.users_v AS

SELECT u.id,
    u.auth_name,
    u.auth_key,
    u.auth_method,
    u.group_id,
    u.is_locked,
    u.status,
    u.created,
    u.modified,
	u.login_time,
	u.deactivated,
	u.deactivatedmail,
    p.company,
    p.gender,
    p.firstname,
    p.lastname,
    p.email,
    p.candidate_email,
    p.notice,
    p.birthday,
        CASE
            WHEN (((p.firstname)::text <> ''::text) OR ((p.lastname)::text <> ''::text)) THEN (btrim((((COALESCE(p.firstname, ''::character varying))::text || ' '::text) || (COALESCE(p.lastname, ''::character varying))::text)))::character varying
            ELSE u.auth_name
        END AS fullname
   FROM (users u
     LEFT JOIN user_profiles p ON ((u.id = p.user_id)));
  
;  
COMMIT;