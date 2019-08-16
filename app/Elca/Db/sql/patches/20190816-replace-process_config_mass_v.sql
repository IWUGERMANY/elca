BEGIN;
SELECT public.register_patch('20190816-add-unique-index-elca_process_conversions_in_out.sql', 'eLCA');

DROP VIEW IF EXISTS elca_cache.project_variant_process_config_mass_v;
CREATE OR REPLACE VIEW elca_cache.project_variant_process_config_mass_v AS
SELECT e.project_variant_id
     , c.process_config_id
     , p.name
     , sum(cec.mass) AS mass
     , sum(CASE WHEN pc.factor IS NOT NULL THEN cec.mass / pc.factor ELSE null END) AS volume
FROM elca_cache.element_components cec
         JOIN elca.element_components c ON c.id = cec.element_component_id
         JOIN elca.elements e ON e.id = c.element_id
         JOIN elca.process_configs p ON p.id = c.process_config_id
         LEFT JOIN elca.process_conversions pc ON p.id = pc.process_config_id AND (pc.in_unit, pc.out_unit) = ('m3', 'kg')
GROUP BY e.project_variant_id
       , c.process_config_id
       , p.name;

CREATE UNIQUE INDEX IX_elca_process_conversions_in_out ON elca.process_conversions ("process_config_id", "in_unit", "out_unit");

COMMIT;