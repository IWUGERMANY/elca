BEGIN;
SELECT public.register_patch('20200214-create-view-report-waste-code-mass-v.sql', 'elca');

DROP VIEW IF EXISTS elca_cache.report_waste_code_mass_v;
CREATE OR REPLACE VIEW elca_cache.report_waste_code_mass_v AS
	SELECT e.project_variant_id
			, et.din_code
			, et.name AS element_type_name
			, c.process_config_id
			, p.name
			, p.waste_code
			, p.waste_code_suffix
			, sum(cec.mass) AS mass
			, sum(CASE WHEN pc.factor IS NOT NULL THEN cec.mass / pc.factor ELSE null END) AS volume
	FROM elca_cache.element_components cec
			 JOIN elca.element_components c ON c.id = cec.element_component_id
			 JOIN elca.elements e ON e.id = c.element_id
			 JOIN elca.element_types et ON et.node_id = e.element_type_node_id
			 JOIN elca.process_configs p ON p.id = c.process_config_id
			 JOIN elca.project_variants pv ON pv.id = e.project_variant_id
			 JOIN elca.projects proj ON proj.id = pv.project_id
			 LEFT JOIN elca.process_conversions_v pc ON p.id = pc.process_config_id
		AND (pc.in_unit, pc.out_unit) = ('m3', 'kg')
		AND (pc.process_db_id = proj.process_db_id)
	GROUP BY e.project_variant_id
			, et.din_code
			, et.name
			, p.waste_code
			, p.waste_code_suffix
			, c.process_config_id
			, p.name
	order by p.waste_code,p.waste_code_suffix,din_code;
	
COMMIT;