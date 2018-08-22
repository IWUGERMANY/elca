BEGIN;
SELECT public.register_patch('fix-conversion-doublings', 'elca');

DROP FUNCTION IF EXISTS elca.fix_conversions();
CREATE OR REPLACE FUNCTION elca.fix_conversions()
    RETURNS void
AS
$BODY$
DECLARE
    r record;
    conversion elca.process_conversions;
    use_conversion elca.process_conversions;
    element_component_id int;
    element_component elca.element_components;

BEGIN

    FOR r IN WITH all_conversions AS (
        SELECT
            process_config_id
            , in_unit
            , out_unit
            , factor
            , ident
        FROM elca.process_conversions
        ORDER BY process_config_id, in_unit, out_unit, factor, ident
    )

    SELECT DISTINCT
        process_config_id
        , pc.name
        , in_unit
        , out_unit
        , count(*)
        , array_agg(ident)  AS idents
        , array_agg(factor) AS factors
        , min(factor)
        , max(factor)
    FROM all_conversions c
        JOIN elca.process_configs pc ON c.process_config_id = pc.id
    GROUP BY process_config_id, pc.name, in_unit, out_unit
    HAVING count(*) > 1
    ORDER BY count(*) DESC
    LOOP

        SELECT * INTO use_conversion FROM elca.process_conversions
        WHERE process_config_id = r.process_config_id
              AND in_unit = 'm2' AND out_unit = 'kg'
              AND ident = 'AVG_MPUA';

        FOR conversion IN SELECT * FROM elca.process_conversions
        WHERE process_config_id = r.process_config_id
              AND in_unit = 'm2' AND out_unit = 'kg'
              AND (ident IS NULL OR ident = 'INIT')
        LOOP

            PERFORM id FROM elca.element_components
            WHERE process_conversion_id = conversion.id;

            IF FOUND THEN
                RAISE NOTICE 'element component uses conversion % -> % : % (%, %)', conversion.in_unit, conversion.out_unit, conversion.factor, conversion.ident, conversion.id;
                RAISE NOTICE 'will reasign to % -> % : % (%, %)', use_conversion.in_unit, use_conversion.out_unit, use_conversion.factor, use_conversion.ident, use_conversion.id;

                IF use_conversion.factor <> conversion.factor THEN
                    RAISE WARNING 'Reasignment to % -> % : % (%, %) differs by %', use_conversion.in_unit, use_conversion.out_unit, use_conversion.factor, use_conversion.ident, use_conversion.id, (use_conversion.factor - conversion.factor);
                END IF;

                FOR element_component IN SELECT * FROM elca.element_components
                WHERE process_conversion_id = conversion.id
                LOOP
                    UPDATE elca.element_components SET process_conversion_id = use_conversion.id WHERE id = element_component.id;
                END LOOP;
            END IF;

            RAISE NOTICE 'Deleting conversion % -> % : % (%, %)', conversion.in_unit, conversion.out_unit, conversion.factor, conversion.ident, conversion.id;
            DELETE FROM elca.process_conversions WHERE id = conversion.id;
        END LOOP;

    END LOOP;

END;
$BODY$
LANGUAGE plpgsql VOLATILE;

SELECT * FROM elca.fix_conversions();

-- use old values for these two conversions
UPDATE elca.process_conversions SET factor = 3.02 WHERE process_config_id = 15127 AND ident = 'AVG_MPUA';
UPDATE elca.process_conversions SET factor = 3.45 WHERE process_config_id = 14979 AND ident = 'AVG_MPUA';

DROP FUNCTION elca.fix_conversions();

COMMIT;