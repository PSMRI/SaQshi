CREATE DEFINER=`sarbsoft_sarbsoft_nqaadmin`@`%` PROCEDURE `update_phckpi`(
IN p_kpiid INT,
IN p_value DECIMAL(10,2),
IN p_num VARCHAR(100),
IN p_deno VARCHAR(100),
IN p_date VARCHAR(20),
IN p_facid INT
)
BEGIN

UPDATE phc_kpi_in
SET phc_kpi_value=p_value,
phc_kpi_n = p_num,
phc_kpi_d = p_deno
WHERE phc_kpi_id=p_kpiid
AND phc_kpi_date=p_date
AND phc_kpi_fac_id=p_facid;

END



CREATE DEFINER=`sarbsoft_sarbsoft_nqaadmin`@`%` PROCEDURE `insert_PHCKPI`(
id_kpi int(10),
IN in_values DECIMAL(10,2),
IN phc_kpi_num VARCHAR(100),
IN phc_kpi_deno VARCHAR(100),
in date_val varchar (20),
IN fid int(10)
)
Begin
insert into phc_kpi_in(phc_kpi_id,phc_kpi_value,phc_kpi_n,phc_kpi_d,phc_kpi_date,phc_kpi_fac_id)
values(id_kpi,in_values,phc_kpi_num,phc_kpi_deno,date_val,fid);

END

SELECT * FROM sarbsoft_nqa.phc_kpi_in where phc_kpi_fac_id=15985CREATE TABLE `phc_kpi_in` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `phc_kpi_id` int NOT NULL,
  `phc_kpi_value` decimal(10,2) NOT NULL,
  `phc_kpi_date` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `phc_kpi_fac_id` int NOT NULL,
  `phc_kpi_n` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `phc_kpi_d` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=321 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;


CREATE DEFINER=`sarbsoft_sarbsoft_nqaadmin`@`%` PROCEDURE `insert_chcKPI`(
id_kpi int(10),
IN in_values DECIMAL(10,2),
IN chc_kpi_num VARCHAR(100),
IN chc_kpi_deno VARCHAR(100),
in date_val varchar (20),
IN fid int(10)
)
Begin
insert into chc_kpi_in(chc_kpi_id,chc_kpi_value,chc_kpi_n,chc_kpi_d,chc_kpi_date,chc_kpi_fac_id)
values(id_kpi,in_values,chc_kpi_num,chc_kpi_deno,date_val,fid);

END

CREATE DEFINER=`sarbsoft_sarbsoft_nqaadmin`@`%` PROCEDURE `update_chckpi`(
IN p_kpiid INT,
IN p_value DECIMAL(10,2),
IN p_num VARCHAR(100),
IN p_deno VARCHAR(100),
IN p_date VARCHAR(20),
IN p_facid INT
)
BEGIN

UPDATE chc_kpi_in
SET chc_kpi_value=p_value,
chc_kpi_n = p_num,
chc_kpi_d = p_deno
WHERE chc_kpi_id=p_kpiid
AND chc_kpi_date=p_date
AND chc_kpi_fac_id=p_facid;

END

ALTER TABLE files
ADD file_type VARCHAR(50) NULL


CREATE
    
VIEW `gap_analysis_updated` AS
    SELECT 
        COUNT(DISTINCT `c`.`ass_id`) AS `compliance_count`,
        `c`.`ass_compliance` AS `compliance`,
        `c`.`csqa_id_fk` AS `csqa_id_fk`,
        `d`.`Checkpoint` AS `Checkpoint`,
        `d`.`c_subtype_Reference_No_fk` AS `c_subtype_Reference_No_fk`,
        `d`.`csqa_reference_id` AS `csqa_reference_id`,
        `d`.`Measurable_Element` AS `Measurable_Element`,
        `a`.`concern_name` AS `concern_name`,
        `e`.`area_of_con_subtypedeatils` AS `area_of_con_subtypedeatils`,
        `f`.`facilities_type` AS `facilities_type`,
        GROUP_CONCAT(DISTINCT TRIM(`fm`.`fac_name`)
            ORDER BY `fm`.`fac_name` ASC
            SEPARATOR ', ') AS `facility_names`
    FROM
        (((((`chk_list_assessment` `c`
        JOIN `concern_subtype_chklist` `d` ON ((`c`.`csqa_id_fk` = `d`.`csqa_id`)))
        JOIN `area_of_concern` `a` ON ((`d`.`area_of_con_id_fk` = `a`.`concern_id`)))
        JOIN `area_of_concern_subtype` `e` ON ((`d`.`c_subtype_id_fk` = `e`.`c_subtype_id`)))
        JOIN `facilities_type` `f` ON ((`f`.`fac_type_id` = `d`.`fac_type_id_fk`)))
        LEFT JOIN `facilities` `fm` ON ((`fm`.`fac_id` = `c`.`fac_id_fk`)))
    WHERE
        (`c`.`ass_compliance` IN (0 , 1))
    GROUP BY `c`.`ass_compliance` , `c`.`csqa_id_fk` , `d`.`c_subtype_Reference_No_fk` , `d`.`csqa_reference_id` , `d`.`Measurable_Element` , `a`.`concern_name` , `e`.`area_of_con_subtypedeatils` , `f`.`facilities_type`