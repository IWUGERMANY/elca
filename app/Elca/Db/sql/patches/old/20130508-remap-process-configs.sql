BEGIN;
SELECT public.register_patch('remap-process-configs', 'elca');

UPDATE elca.process_configs
   SET process_category_node_id = (SELECT node_id FROM elca.process_categories_v WHERE ref_num = '8.01')
 WHERE name IN ('Elektrischer Durchlauferhitzer (21 kW)',
                'Gas-Brennwertgerät < 20 kW (Standgerät)',
                'Hackschnitzelkessel 20-120 kW',
                'Hackschnitzelkessel 120-400 kW',
                'Hackschnitzelkessel < 20 kW');

COMMIT;




