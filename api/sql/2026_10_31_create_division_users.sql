-- Create one active Division User for each division represented in facilities.
-- Username: div@<normalized-division-name>, for example div@northdivision.
-- Default password: Saqshi@20266 (bcrypt hash below).
-- Password change is deliberately NOT required after first login.
--
-- Run this script only once per database. Re-running it safely skips an
-- existing Division User for the same division or an already-used username.

START TRANSACTION;

INSERT INTO u_role (role_id, role_name, role_status, role_description)
VALUES (5, 'Division User', 1, 'Division/regional monitoring user')
ON DUPLICATE KEY UPDATE
    role_name = VALUES(role_name),
    role_status = 1,
    role_description = VALUES(role_description);

INSERT INTO s_user (
    u_name,
    u_password,
    fac_id_fk,
    role_id_fk,
    is_active,
    f_name,
    l_name,
    user_type,
    state_id,
    division_id,
    dist_id,
    block_id,
    password_must_change,
    password_changed_on,
    created_by,
    updated_by
)
SELECT
    division.username,
    '$2y$12$z923n4mvUxm412OP0eTHF.DwUIQdESfIAH9j8BpMsK7zmJfuzSiaW',
    NULL,
    5,
    1,
    'Division',
    division.division_name,
    'Division User',
    division.state_id,
    division.division_id,
    NULL,
    NULL,
    0,
    CURRENT_TIMESTAMP,
    NULL,
    NULL
FROM (
    SELECT
        division_id,
        MAX(state_id) AS state_id,
        TRIM(MAX(division)) AS division_name,
        CONCAT(
            'div@',
            LOWER(
                REPLACE(REPLACE(REPLACE(REPLACE(TRIM(MAX(division)), ' ', ''), '-', ''), '_', ''), '.', '')
            )
        ) AS username
    FROM facilities
    WHERE division_id IS NOT NULL
      AND division_id > 0
      AND TRIM(COALESCE(division, '')) <> ''
    GROUP BY division_id
) AS division
WHERE division.username <> 'div@'
  AND NOT EXISTS (
      SELECT 1
      FROM s_user AS scoped_user
      WHERE scoped_user.role_id_fk = 5
        AND scoped_user.division_id = division.division_id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM s_user AS named_user
      WHERE named_user.u_name = division.username
  );

COMMIT;

-- Verify Division User accounts.
SELECT
    u.u_id,
    u.u_name AS username,
    u.division_id,
    MAX(f.division) AS division_name,
    u.is_active,
    u.password_must_change
FROM s_user AS u
LEFT JOIN facilities AS f
    ON f.division_id = u.division_id
WHERE u.role_id_fk = 5
GROUP BY
    u.u_id,
    u.u_name,
    u.division_id,
    u.is_active,
    u.password_must_change
ORDER BY division_name, username;
