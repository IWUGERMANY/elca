BEGIN;
SELECT public.register_patch('20170313-init-project-access-tokens.sql', 'eLCA');

ALTER TABLE elca.projects ADD "owner_id" int;
ALTER TABLE elca.projects ADD FOREIGN KEY ("owner_id") REFERENCES public.users ("id") ON DELETE RESTRICT ;

UPDATE elca.projects p
   SET owner_id = (SELECT id FROM public.users u WHERE u.group_id = p.access_group_id)
;

ALTER TABLE elca.projects ALTER "owner_id" SET NOT NULL ;

CREATE OR REPLACE VIEW elca.projects_view AS
   SELECT
      p.id
      , p.process_db_id
      , p.current_variant_id
      , p.access_group_id
      , p.owner_id
      , p.name
      , p.description
      , p.project_nr
      , p.constr_measure
      , p.life_time
      , p.created
      , p.modified
      , p.constr_class_id
      , p.editor
      , p.is_reference
      , p.benchmark_version_id
      , p.password
      , array_agg(g.user_id)
           FILTER (WHERE g.user_id IS NOT NULL) || p.owner_id AS user_ids
   FROM elca.projects p
      LEFT JOIN public.group_members g ON g.group_id = p.access_group_id
   GROUP BY p.id
      , p.process_db_id
      , p.current_variant_id
      , p.access_group_id
      , p.owner_id
      , p.name
      , p.description
      , p.project_nr
      , p.constr_measure
      , p.life_time
      , p.created
      , p.modified
      , p.constr_class_id
      , p.editor
      , p.is_reference
      , p.benchmark_version_id
      , p.password;


CREATE TABLE elca.project_access_tokens
(
     "token"                  uuid            NOT NULL                -- projectAccessToken
   , "project_id"             int             NOT NULL                -- projectId
   , "user_id"                int                                     -- userId of user which gets privileges
   , "user_email"             varchar(200)    NOT NULL                -- user email address
   , "can_edit"               boolean         NOT NULL DEFAULT false  -- privilege to edit
   , "is_confirmed"           boolean         NOT NULL DEFAULT false  -- confirmed state
   , "created"                timestamptz(0)  NOT NULL DEFAULT now()  -- creation time
   , "modified"               timestamptz(0)           DEFAULT now()  -- modification time
   , PRIMARY KEY ("token")
   , UNIQUE ("project_id", "user_id")
   , FOREIGN KEY ("project_id") REFERENCES elca.projects ("id") ON DELETE CASCADE
   , FOREIGN KEY ("user_id") REFERENCES public.users ("id")    ON DELETE CASCADE
);

COMMIT;