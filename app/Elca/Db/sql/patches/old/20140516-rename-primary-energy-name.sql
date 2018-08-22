BEGIN;
SELECT public.register_patch('rename-primary-energy-names', 'elca');

UPDATE elca.indicators
SET ident = 'peEm'
  , name = 'PE ern.'
  , description = 'PE ern.'
WHERE ident = 'peiEm';

UPDATE elca.indicators
SET ident = 'peNEm'
  , name = 'PE n. ern.'
  , description = 'PE n. ern.'
WHERE ident = 'peiNEm';

UPDATE elca.settings SET ident = 'min.peEm' WHERE section = 'elca.admin.benchmarks' AND ident = 'min.peiEm';
UPDATE elca.settings SET ident = 'avg.peEm' WHERE section = 'elca.admin.benchmarks' AND ident = 'avg.peiEm';
UPDATE elca.settings SET ident = 'max.peEm' WHERE section = 'elca.admin.benchmarks' AND ident = 'max.peiEm';
UPDATE elca.settings SET ident = 'min.peNEm' WHERE section = 'elca.admin.benchmarks' AND ident = 'min.peiNEm';
UPDATE elca.settings SET ident = 'avg.peNEm' WHERE section = 'elca.admin.benchmarks' AND ident = 'avg.peiNEm';
UPDATE elca.settings SET ident = 'max.peNEm' WHERE section = 'elca.admin.benchmarks' AND ident = 'max.peiNEm';

COMMIT;