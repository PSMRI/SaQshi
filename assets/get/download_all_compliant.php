<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");
include(__DIR__ . "/../../assets/security/security.php");
/* =====================================================
   EXPORT GAP ANALYSIS REPORT
===================================================== */

header("Content-Type: application/vnd.ms-excel");
header(
    "Content-Disposition: attachment; filename=Gap_Analysis_Report_" .
    date('Ymd_His') .
    ".xls"
);

header("Pragma: no-cache");
header("Expires: 0");

/* =====================================================
   QUERY
===================================================== */

$sql = "

SELECT

    compliance_count AS facility_count,

    compliance,

    concern_name,

    area_of_con_subtypedeatils,

    c_subtype_Reference_No_fk AS standard,

    csqa_reference_id,

    Measurable_Element,

    facilities_type,

    facility_names

FROM gap_analysis_updated

ORDER BY

    facilities_type,
    concern_name,
    csqa_reference_id

";

$result = mysqli_query($con, $sql);

?>

<html>

<head>

    <meta charset=\"utf-8\">

    <style>

        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial;
            font-size: 12px;
        }

        th {
            background: #0d6efd;
            color: white;
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }

        td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
        }       

    </style>

</head>

<body>

<h3>
    SaQshi All Indicators Performance  
</h3>

<p>
    Generated On :
    <?= date('d-m-Y h:i A'); ?>
</p>

<table>

    <thead>

    <tr>

        <th>Sl No</th>       

        <th>Facility Count</th>

        <th>Compliance</th>

        <th>Facility Type</th>

        <th>Area of Concern</th>

        <th>Standard</th>

        <th>Measurable Element Ref</th>

        <th>Measurable Element</th>

        <th>Sub Area</th>

        <th>Facilities</th>

    </tr>

    </thead>

    <tbody>

<?php

$sl = 0;

while($row = mysqli_fetch_assoc($result))
{

    $sl++;

    $count = (int)$row['facility_count'];

    
?>

<tr class="<?= $class; ?>">

    <td>
        <?= $sl; ?>
    </td>

  

    <td>
        <?= e($row['facility_count']); ?>
    </td>

    <td>
        <?= ($row['compliance']==0)
            ? 'Non-Compliant'
            : 'Compliant'; ?>
    </td>

    <td>
        <?= e($row['facilities_type']); ?>
    </td>

    <td>
        <?= e($row['concern_name']); ?>
    </td>

    <td>
        <?= e($row['standard']); ?>
    </td>

    <td>
        <?= e($row['csqa_reference_id']); ?>
    </td>

    <td>
        <?= e($row['Measurable_Element']); ?>
    </td>

    <td>
        <?= e($row['area_of_con_subtypedeatils']); ?>
    </td>

    <td>
        <?= e($row['facility_names']); ?>
    </td>

</tr>

<?php
}
?>

    </tbody>

</table>

<br>

<h4>
    Zone Classification
</h4>

<ul>

    <li>
        <strong>Red Zone :</strong>
        More than 150 facilities are non-compliant
    </li>

    <li>
        <strong>Yellow Zone :</strong>
        100 to 149 facilities are non-compliant
    </li>

    <li>
        <strong>Orange Zone :</strong>
        50 to 99 facilities are non-compliant
    </li>

    <li>
        <strong>Green Zone :</strong>
        Less than 50 facilities are non-compliant
    </li>

</ul>

</body>
</html>