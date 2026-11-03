-- Reconcile every ACTIVE assessment whose active departments are all complete.
-- Existing completed timestamps are preserved; otherwise the latest completed
-- department timestamp is used. Review the pre-check result before COMMIT.

-- Expected number of rows that will be completed.
SELECT COUNT(*) AS eligible_assessments
FROM assessment_master AS am
WHERE am.status = 'ACTIVE'
  AND EXISTS (
      SELECT 1
      FROM assessment_department AS ad
      WHERE ad.assessment_id = am.assessment_id
        AND ad.is_active = 1
  )
  AND NOT EXISTS (
      SELECT 1
      FROM assessment_department AS ad
      WHERE ad.assessment_id = am.assessment_id
        AND ad.is_active = 1
        AND UPPER(COALESCE(ad.status, '')) <> 'COMPLETED'
  );

START TRANSACTION;

UPDATE assessment_master AS am
SET am.status = 'COMPLETED',
    am.completed_on = COALESCE(
        am.completed_on,
        (
            SELECT MAX(ad.completed_on)
            FROM assessment_department AS ad
            WHERE ad.assessment_id = am.assessment_id
              AND ad.is_active = 1
        ),
        CURRENT_TIMESTAMP
    )
WHERE am.status = 'ACTIVE'
  AND EXISTS (
      SELECT 1
      FROM assessment_department AS ad
      WHERE ad.assessment_id = am.assessment_id
        AND ad.is_active = 1
  )
  AND NOT EXISTS (
      SELECT 1
      FROM assessment_department AS ad
      WHERE ad.assessment_id = am.assessment_id
        AND ad.is_active = 1
        AND UPPER(COALESCE(ad.status, '')) <> 'COMPLETED'
  );

-- Inspect the affected-row count in the SQL client, then COMMIT. Use ROLLBACK
-- instead if the count does not match the pre-check.
COMMIT;

-- Confirm no eligible ACTIVE assessment remains after the update.
SELECT COUNT(*) AS remaining_eligible_assessments
FROM assessment_master AS am
WHERE am.status = 'ACTIVE'
  AND EXISTS (
      SELECT 1
      FROM assessment_department AS ad
      WHERE ad.assessment_id = am.assessment_id
        AND ad.is_active = 1
  )
  AND NOT EXISTS (
      SELECT 1
      FROM assessment_department AS ad
      WHERE ad.assessment_id = am.assessment_id
        AND ad.is_active = 1
        AND UPPER(COALESCE(ad.status, '')) <> 'COMPLETED'
  );
