BEGIN;
SELECT public.register_patch('reorder-indicators', 'elca');

UPDATE elca.indicators SET p_order = 10 WHERE ident = 'gwp';
UPDATE elca.indicators SET p_order = 20 WHERE ident = 'odp';
UPDATE elca.indicators SET p_order = 30 WHERE ident = 'pocp';
UPDATE elca.indicators SET p_order = 40 WHERE ident = 'ap';
UPDATE elca.indicators SET p_order = 50 WHERE ident = 'ep';
UPDATE elca.indicators SET p_order = 60 WHERE ident = 'pet';
UPDATE elca.indicators SET p_order = 70 WHERE ident = 'penrt';
UPDATE elca.indicators SET p_order = 75 WHERE ident = 'peNEm';
UPDATE elca.indicators SET p_order = 80 WHERE ident = 'penrm';
UPDATE elca.indicators SET p_order = 90 WHERE ident = 'penre';
UPDATE elca.indicators SET p_order = 100 WHERE ident = 'pert';
UPDATE elca.indicators SET p_order = 105 WHERE ident = 'peEm';
UPDATE elca.indicators SET p_order = 110 WHERE ident = 'perm';
UPDATE elca.indicators SET p_order = 120 WHERE ident = 'pere';

UPDATE elca.indicators SET p_order = 130 WHERE ident = 'adp';
UPDATE elca.indicators SET p_order = 134 WHERE ident = 'adpe';
UPDATE elca.indicators SET p_order = 148 WHERE ident = 'adpf';

UPDATE elca.indicators SET p_order = 150 WHERE ident = 'secFuels';
UPDATE elca.indicators SET p_order = 160 WHERE ident = 'waterUse';
UPDATE elca.indicators SET p_order = 170 WHERE ident = 'spoil';
UPDATE elca.indicators SET p_order = 180 WHERE ident = 'householdWaste';
UPDATE elca.indicators SET p_order = 185 WHERE ident = 'hazardousWaste';
UPDATE elca.indicators SET p_order = 190 WHERE ident = 'sm';
UPDATE elca.indicators SET p_order = 200 WHERE ident = 'rsf';
UPDATE elca.indicators SET p_order = 210 WHERE ident = 'nrsf';
UPDATE elca.indicators SET p_order = 220 WHERE ident = 'fw';
UPDATE elca.indicators SET p_order = 230 WHERE ident = 'hwd';
UPDATE elca.indicators SET p_order = 240 WHERE ident = 'nhwd';
UPDATE elca.indicators SET p_order = 250 WHERE ident = 'rwd';
UPDATE elca.indicators SET p_order = 260 WHERE ident = 'cru';
UPDATE elca.indicators SET p_order = 270 WHERE ident = 'mfr';
UPDATE elca.indicators SET p_order = 280 WHERE ident = 'mer';
UPDATE elca.indicators SET p_order = 290 WHERE ident = 'eee';
UPDATE elca.indicators SET p_order = 300 WHERE ident = 'eet';

COMMIT;