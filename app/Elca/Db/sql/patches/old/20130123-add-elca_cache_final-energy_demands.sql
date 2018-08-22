BEGIN;
SELECT public.register_patch('add-elca_cache-final_energy_demands', 'elca');

CREATE TABLE elca_cache.final_energy_demands
(
    "item_id"                integer         NOT NULL              -- itemId
  , "final_energy_demand_id" integer         NOT NULL              -- finalEnergyDemandId

  , "quantity"               numeric                               -- quantity in refUnit / m2[NGF]a
  , "ref_unit"               varchar(10)                           -- refUnit

  , PRIMARY KEY ("item_id")
  , UNIQUE ("final_energy_demand_id")
  , FOREIGN KEY ("item_id") REFERENCES elca_cache.items("id") ON UPDATE CASCADE ON DELETE CASCADE
  , FOREIGN KEY ("final_energy_demand_id") REFERENCES elca.project_final_energy_demands("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TRIGGER trigger_elca_cache_on_delete_cascade AFTER DELETE ON elca_cache.final_energy_demands
   FOR EACH ROW EXECUTE PROCEDURE elca_cache.on_delete_cascade();

COMMIT;