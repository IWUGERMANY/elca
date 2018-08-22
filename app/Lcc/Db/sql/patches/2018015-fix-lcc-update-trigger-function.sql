BEGIN;
SELECT public.register_patch('2018015-fix-lcc-update-trigger-function.sql', 'LCC');


CREATE OR REPLACE FUNCTION lcc.on_version_update_also_update_project_costs() RETURNS trigger
AS $$

BEGIN
    IF OLD.version_id <> NEW.version_id THEN

        UPDATE lcc.project_costs p
        SET cost_id = (SELECT id
                       FROM lcc.costs c
                       WHERE (c.version_id, c.grouping, c.din276_code, c.label) = (NEW.version_id, o.grouping, o.din276_code, o.label)
        )
        FROM lcc.costs o
        WHERE o.id = p.cost_id
              AND p.project_variant_id = OLD.project_variant_id
              AND o.version_id = OLD.version_id;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

COMMIT;