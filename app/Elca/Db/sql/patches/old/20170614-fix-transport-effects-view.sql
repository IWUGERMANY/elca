BEGIN;
SELECT public.register_patch('20170614-fix-transport-effects-view.sql', 'eLCA');

CREATE OR REPLACE VIEW elca_cache.report_transport_effects_v AS
    SELECT m.id AS transport_mean_id
        , t.id AS transport_id
        , t.name AS element_name
        , t.project_variant_id
        , cm.quantity AS element_quantity
        , cm.ref_unit AS element_ref_unit
        , pc.name AS process_config_name
        , ci.indicator_id AS indicator_id
        , ci.value AS indicator_value
        , i.ident AS indicator_ident
        , i.name AS indicator_name
        , i.unit AS indicator_unit
        , i.is_hidden
        , i.p_order AS indicator_p_order
    FROM elca.project_transports            t
        JOIN elca.project_transport_means       m ON t.id = m.project_transport_id
        JOIN elca.process_configs              pc ON pc.id = m.process_config_id
        JOIN elca_cache.transport_means_v      cm ON m.id = cm.transport_mean_id
        JOIN elca_cache.indicators             ci ON cm.item_id = ci.item_id
        JOIN elca.indicators                    i ON i.id = ci.indicator_id;

COMMIT;