-- Departments that a facility actually operates for Performance Monitoring.
CREATE TABLE IF NOT EXISTS facility_performance_department (
    facility_performance_department_id BIGINT NOT NULL AUTO_INCREMENT,
    fac_id INT NOT NULL,
    dept_id INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    updated_by INT NULL,
    created_on TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_on TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (facility_performance_department_id),
    UNIQUE KEY uq_performance_facility_department (fac_id, dept_id),
    KEY idx_performance_facility_department_active (fac_id, is_active)
);
