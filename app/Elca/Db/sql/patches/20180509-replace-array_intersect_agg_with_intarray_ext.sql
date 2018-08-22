BEGIN;
SELECT
    public.register_patch('20180509-replace-array_intersect_agg_with_intarray_ext.sql', 'eLCA');

DROP VIEW IF EXISTS elca.composite_element_extended_search_v;
DROP VIEW IF EXISTS elca.element_search_v;
DROP VIEW IF EXISTS elca.element_extended_search_v;

DROP AGGREGATE array_intersect_agg(int[]);
DROP FUNCTION array_intersect(int[], int[]);

CREATE EXTENSION intarray;

CREATE AGGREGATE public.array_intersect_agg(int[]) (
    sfunc = _int_inter,
    stype = int[]
);

CREATE OR REPLACE VIEW elca.element_extended_search_v AS
    SELECT
        e.id
        , e.element_type_node_id
        , e.name
        , e.description
        , e.is_reference
        , e.is_public
        , e.access_group_id
        , e.project_variant_id
        , e.quantity
        , e.ref_unit
        , e.copy_of_element_id
        , e.owner_id
        , e.is_composite
        , e.uuid
        , e.created
        , e.modified
        , to_tsvector('german', e.id || ' ' || e.name || ' ' || coalesce(e.description || ' ', '') ||
                                array_to_string(array_agg(coalesce(pc.name, '')), ' ')) AS search_vector
        , array_intersect_agg(pc.process_db_ids) AS process_db_ids
    FROM elca.elements e
        LEFT JOIN elca.element_components c ON e.id = c.element_id
        LEFT JOIN elca.process_config_process_dbs_view pc ON pc.id = c.process_config_id
    WHERE is_composite = false
    GROUP BY e.id
        , e.element_type_node_id
        , e.name
        , e.description
        , e.is_reference
        , e.is_public
        , e.access_group_id
        , e.project_variant_id
        , e.quantity
        , e.ref_unit
        , e.copy_of_element_id
        , e.owner_id
        , e.is_composite
        , e.uuid
        , e.created
        , e.modified;


CREATE OR REPLACE VIEW elca.element_search_v AS
    SELECT
        e.id
        , e.name
        , e.element_type_node_id
        , e.project_variant_id
        , e.access_group_id
        , e.is_reference
        , e.is_public
        , t.din_code || ' ' || t.name AS element_type_node_name
        , e.process_db_ids
    FROM elca.element_extended_search_v e
        JOIN elca.element_types t ON t.node_id = e.element_type_node_id;

CREATE OR REPLACE VIEW elca.composite_element_extended_search_v AS
    SELECT
        e.id
        , e.element_type_node_id
        , e.name
        , e.description
        , e.is_reference
        , e.is_public
        , e.access_group_id
        , e.project_variant_id
        , e.quantity
        , e.ref_unit
        , e.copy_of_element_id
        , e.owner_id
        , e.is_composite
        , e.uuid
        , e.created
        , e.modified
        , to_tsvector('german', e.id || ' ' || e.name || ' ' || coalesce(e.description || ' ', '') ||
                                array_to_string(array_agg(coalesce(pc.name, '')), ' ')) AS search_vector
        , array_intersect_agg(pc.process_db_ids) AS process_db_ids
    FROM elca.elements e
        LEFT JOIN elca.composite_elements a ON e.id = a.composite_element_id
        LEFT JOIN elca.element_components c ON a.element_id = c.element_id
        LEFT JOIN elca.process_config_process_dbs_view pc ON pc.id = c.process_config_id
    WHERE is_composite = true
    GROUP BY e.id
        , e.element_type_node_id
        , e.name
        , e.description
        , e.is_reference
        , e.is_public
        , e.access_group_id
        , e.project_variant_id
        , e.quantity
        , e.ref_unit
        , e.copy_of_element_id
        , e.owner_id
        , e.is_composite
        , e.uuid
        , e.created
        , e.modified;
COMMIT;