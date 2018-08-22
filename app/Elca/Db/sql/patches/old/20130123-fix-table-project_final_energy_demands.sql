BEGIN;
SELECT public.register_patch('fix-table-project_final_energy_demands', 'elca');

DROP TABLE elca.project_final_energy_demands;
CREATE TABLE elca.project_final_energy_demands
(
   "id"                      serial          NOT NULL                -- projectFinalEnergyDemandId
 , "project_variant_id"      int             NOT NULL                -- projectVariantId
 , "process_config_id"       int             NOT NULL                -- process config id

 , "heating"                 numeric                                 -- heating in kWh/(m2*a)
 , "water"                   numeric                                 -- water in kWh/(m2*a)
 , "lighting"                numeric                                 -- lighting in kWh/(m2*a)
 , "ventilation"             numeric                                 -- ventilation in kWh/(m2*a)
 , "cooling"                 numeric                                 -- cooling in kWh/(m2*a)

 , PRIMARY KEY ("id")
 , FOREIGN KEY ("project_variant_id") REFERENCES elca.project_variants ("id") ON UPDATE CASCADE ON DELETE CASCADE
 , FOREIGN KEY ("process_config_id") REFERENCES elca.process_configs ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

COMMIT;
