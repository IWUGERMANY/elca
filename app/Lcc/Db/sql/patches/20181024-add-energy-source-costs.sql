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

ALTER TABLE lcc.project_costs ADD "energy_source_cost_id"      int;
ALTER TABLE lcc.project_costs ADD FOREIGN KEY ("energy_source_cost_id") REFERENCES lcc.energy_source_costs ("id") ON UPDATE CASCADE ON DELETE SET NULL;

UPDATE lcc.costs SET ident = 'EEG'
WHERE id IN (
            SELECT id FROM lcc.regular_costs_v WHERE grouping = 'ENERGY' AND ref_value IS NULL
            );

UPDATE lcc.regular_costs
SET ref_value = null
WHERE cost_id IN (SELECT id FROM lcc.regular_costs_v WHERE grouping = 'ENERGY');


COMMIT;