BEGIN;

CREATE OR REPLACE VIEW elca_cache.project_variant_process_config_mass_v AS
SELECT e.project_variant_id,
    c.process_config_id,
    p.name,
    sum(cec.mass) AS mass,
    sum(
        CASE
            WHEN (pc.factor IS NOT NULL) THEN (cec.mass / pc.factor)
            ELSE NULL::numeric
        END) AS volume,
    p.waste_code
   FROM ((((((element_components cec
     JOIN elca.element_components c ON ((c.id = cec.element_component_id)))
     JOIN elca.elements e ON ((e.id = c.element_id)))
     JOIN elca.process_configs p ON ((p.id = c.process_config_id)))
     JOIN elca.project_variants pv ON ((pv.id = e.project_variant_id)))
     JOIN elca.projects proj ON ((proj.id = pv.project_id)))
     LEFT JOIN elca.process_conversions_v pc ON (((p.id = pc.process_config_id) AND (((pc.in_unit)::text = 'm3'::text) AND ((pc.out_unit)::text = 'kg'::text)) AND (pc.process_db_id = proj.process_db_id))))
  GROUP BY e.project_variant_id, c.process_config_id, p.name, p.waste_code
;

COMMIT;
