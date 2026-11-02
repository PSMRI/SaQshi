-- Create one Facility User for every active facility that does not already
-- have one. Existing user accounts are never changed or overwritten.
--
-- Username: facility NIN number
-- Password: Saqshi@20266
-- Password hash: bcrypt (compatible with PHP password_verify)
-- Users will be required to change this temporary password on first login.

START TRANSACTION;

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
    created_by,
    updated_by
)
SELECT
    CAST(f.NIN_no AS CHAR) AS u_name,
    '$2y$12$aLuSmxrKJmr4ogkMYK6P2.CBnGPYrmBkLa.rMsN6AViktwz.h/MXq' AS u_password,
    f.fac_id,
    1 AS role_id_fk,
    1 AS is_active,
    COALESCE(NULLIF(TRIM(f.fac_name), ''), 'Facility') AS f_name,
    'User' AS l_name,
    'Facility User' AS user_type,
    f.state_id,
    f.division_id,
    f.dist_id,
    f.block_id,
    1 AS password_must_change,
    NULL AS created_by,
    NULL AS updated_by
FROM facilities AS f
LEFT JOIN s_user AS existing_facility_user
    ON existing_facility_user.fac_id_fk = f.fac_id
   AND existing_facility_user.role_id_fk = 1
LEFT JOIN s_user AS existing_username
    ON existing_username.u_name COLLATE utf8mb4_unicode_ci =
       CONVERT(CAST(f.NIN_no AS CHAR) USING utf8mb4) COLLATE utf8mb4_unicode_ci
WHERE COALESCE(f.is_active, 1) = 1
  AND f.NIN_no IS NOT NULL
  AND CAST(f.NIN_no AS CHAR) <> ''
  AND existing_facility_user.u_id IS NULL
  AND existing_username.u_id IS NULL
  AND EXISTS (
      SELECT 1
      FROM u_role AS role
      WHERE role.role_id = 1
        AND role.role_status = 1
  );

COMMIT;

-- Review all facility-user accounts after running the insert.
SELECT
    f.fac_id,
    f.NIN_no AS username,
    f.fac_name AS facility_name,
    u.u_id AS user_id,
    u.is_active,
    u.password_must_change
FROM facilities AS f
LEFT JOIN s_user AS u
    ON u.fac_id_fk = f.fac_id
   AND u.role_id_fk = 1
WHERE COALESCE(f.is_active, 1) = 1
ORDER BY f.fac_name, f.fac_id;
