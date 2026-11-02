-- Backfill certification history for migrated National and National-conditional facilities.
--
-- Purpose:
--   A National certification is treated as an upgrade from a historical State
--   certification when migration data did not retain the State row. This adds
--   history only; it does not create or change a current cert_details row.
--
-- Result per eligible facility:
--   1. State / Certified historical event, with no score or dates.
--   2. National / Certified historical event copied from the current import.
--
-- The State Dashboard can then show STATE -> NATIONAL UPGRADES while the
-- current certification remains National.

START TRANSACTION;

-- Review this count before committing. It should be 71 for the supplied
-- migration set when all eligible National facilities lack history.
SELECT COUNT(*) AS facilities_to_backfill
FROM cert_details cd
WHERE UPPER(COALESCE(cd.Cert_status, '')) IN (
      'CERTIFIED',
      'CONDITIONAL',
      'CONDITIONAL CERTIFIED',
      'CONDITIONALLY CERTIFIED'
  )
  AND UPPER(COALESCE(cd.cert_type, '')) IN (
      'NATIONAL',
      'NATIONAL CONDITIONALITY',
      'NATIONAL_CONDITIONALITY'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM certification_history ch
      WHERE (cd.fac_id IS NOT NULL AND cd.fac_id > 0 AND ch.fac_id_fk = cd.fac_id)
         OR ((cd.fac_id IS NULL OR cd.fac_id = 0)
             AND cd.fac_nin IS NOT NULL AND cd.fac_nin > 0 AND ch.fac_nin = cd.fac_nin)
  );

-- Insert the inferred State certification first. No date and no score are set.
INSERT INTO certification_history
    (certification_id, fac_id_fk, fac_nin, old_data_json, new_data_json, action_type, action_by)
SELECT
    cd.id,
    NULLIF(cd.fac_id, 0),
    NULLIF(cd.fac_nin, 0),
    NULL,
    JSON_OBJECT(
        'certification_id', cd.id,
        'fac_id', NULLIF(cd.fac_id, 0),
        'fac_nin', NULLIF(cd.fac_nin, 0),
        'Cert_status', 'CERTIFIED',
        'cert_type', 'STATE',
        'migration_note', 'State certification history inferred while migrating National certification.'
    ),
    'MIGRATED_STATE_CERT',
    NULL
FROM cert_details cd
WHERE UPPER(COALESCE(cd.Cert_status, '')) IN (
      'CERTIFIED',
      'CONDITIONAL',
      'CONDITIONAL CERTIFIED',
      'CONDITIONALLY CERTIFIED'
  )
  AND UPPER(COALESCE(cd.cert_type, '')) IN (
      'NATIONAL',
      'NATIONAL CONDITIONALITY',
      'NATIONAL_CONDITIONALITY'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM certification_history ch
      WHERE (cd.fac_id IS NOT NULL AND cd.fac_id > 0 AND ch.fac_id_fk = cd.fac_id)
         OR ((cd.fac_id IS NULL OR cd.fac_id = 0)
             AND cd.fac_nin IS NOT NULL AND cd.fac_nin > 0 AND ch.fac_nin = cd.fac_nin)
  );

-- Insert the current National event second, retaining migrated dates and score.
-- Its newer history id keeps National as the current displayed certification.
INSERT INTO certification_history
    (certification_id, fac_id_fk, fac_nin, old_data_json, new_data_json, action_type, action_by)
SELECT
    cd.id,
    NULLIF(cd.fac_id, 0),
    NULLIF(cd.fac_nin, 0),
    NULL,
    JSON_OBJECT(
        'certification_id', cd.id,
        'fac_id', NULLIF(cd.fac_id, 0),
        'fac_nin', NULLIF(cd.fac_nin, 0),
        'Cert_status', cd.Cert_status,
        'cert_type', 'NATIONAL',
        'ass_mod', cd.ass_mod,
        'date_of_ass', cd.date_of_ass,
        'applied_date', cd.applied_date,
        'cert_issue', cd.cert_issue,
        'validity', cd.validity,
        'score', cd.score,
        'cert_detailscol', cd.cert_detailscol
    ),
    'MIGRATED_NATIONAL_CERT',
    NULL
FROM cert_details cd
WHERE UPPER(COALESCE(cd.Cert_status, '')) IN (
      'CERTIFIED',
      'CONDITIONAL',
      'CONDITIONAL CERTIFIED',
      'CONDITIONALLY CERTIFIED'
  )
  AND UPPER(COALESCE(cd.cert_type, '')) IN (
      'NATIONAL',
      'NATIONAL CONDITIONALITY',
      'NATIONAL_CONDITIONALITY'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM certification_history ch
      WHERE ch.certification_id = cd.id
        AND ch.action_type = 'MIGRATED_NATIONAL_CERT'
  );

-- Expected result: the current National count remains unchanged and this
-- count becomes the State -> National upgrade count.
SELECT COUNT(DISTINCT ch.fac_id_fk) AS state_to_national_upgrades
FROM certification_history ch
WHERE ch.action_type = 'MIGRATED_NATIONAL_CERT';

COMMIT;
