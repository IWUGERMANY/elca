BEGIN;
SELECT public.register_patch('improve-svg_patterns', 'elca');

-- patch from blibs

ALTER TABLE elca.svg_patterns ADD COLUMN "description" TEXT;
ALTER TABLE elca.svg_patterns ADD COLUMN "created" timestamptz(0)  NOT NULL DEFAULT now();
ALTER TABLE elca.svg_patterns ADD COLUMN "modified" timestamptz(0)          DEFAULT now();


COMMIT;

