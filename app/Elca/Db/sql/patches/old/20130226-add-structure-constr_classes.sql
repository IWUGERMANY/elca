BEGIN;
SELECT public.register_patch('add-structure-constr_classes', 'elca');

CREATE TABLE elca.constr_classes
(
    "id"                      serial          NOT NULL                -- constrClassId
  , "name"                    text            NOT NULL                -- name
  , "ref_num"                 int             NOT NULL                -- reference number
  , PRIMARY KEY ("id")
  , UNIQUE ("ref_num")
);

ALTER TABLE elca.projects ADD "constr_class_id" int;
ALTER TABLE elca.projects ADD FOREIGN KEY ("constr_class_id") REFERENCES elca.constr_classes ("id") ON UPDATE CASCADE ON DELETE SET NULL;

COMMIT;