BEGIN;
SELECT public.register_patch('20181024-add-energy-source-costs', 'lcc');

CREATE TABLE lcc.energy_source_costs
(
      "id"            serial              NOT NULL
    , "version_id"    int                 NOT NULL
    , "name"          varchar(200)        NOT NULL
    , "costs"         numeric             NOT NULL
    , PRIMARY KEY ("id")
    , FOREIGN KEY ("version_id") REFERENCES lcc.versions ON UPDATE CASCADE ON DELETE CASCADE
);

COMMIT;