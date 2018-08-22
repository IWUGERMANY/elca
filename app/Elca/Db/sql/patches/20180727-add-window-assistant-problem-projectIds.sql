BEGIN;
SELECT
    public.register_patch('20180727-add-window-assistant-problem-projectIds.sql', 'eLCA');


SELECT DISTINCT p.id AS project_id
INTO elca.window_assistant_problem_project_ids
FROM elca.elements e
    JOIN elca.element_attributes a ON e.id = a.element_id
    JOIN elca.project_variants v ON v.id = e.project_variant_id
    JOIN elca_cache.project_variants cv ON v.id = cv.project_variant_id
    JOIN elca_cache.indicators cvi ON cvi.item_id = cv.item_id AND cvi.indicator_id = 9
    JOIN elca.projects p ON p.id = v.project_id
    LEFT JOIN elca_cache.elements ce ON e.id = ce.element_id
    LEFT JOIN elca_cache.indicators ci ON ci.item_id = ce.item_id AND ci.indicator_id = 9
WHERE a.ident = 'window-assistant'
      AND a.numeric_value IS NOT NULL
      AND ci.item_id IS NULL;

COMMIT;