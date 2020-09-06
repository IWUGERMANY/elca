BEGIN;
SELECT public.register_patch('20200906-assistant-elements-add-indexes.sql', 'elca');

CREATE INDEX IX_elca_assistant_elements_project_variant_id ON elca.assistant_elements ("project_variant_id");
CREATE INDEX IX_elca_assistant_elements_main_element_id ON elca.assistant_elements ("main_element_id");

COMMIT;