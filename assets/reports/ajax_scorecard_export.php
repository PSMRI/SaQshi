<?php
ini_set('max_execution_time', 300); // allow 5 minutes if needed
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include_once("../conn/db.php");

$Fa = $_SESSION['u_facilityid'];
$p = $_SESSION['assperiod'] ?? $_SESSION['period']; // use what is available
$fat = $_SESSION['f_type_id'];
$t = $_SESSION['userid'] ?? 0;

// Fetch department list
$dept_info = [];
$stmt = $con->prepare("SELECT DISTINCT a.fac_dept_id_fk, b.dept_name
                       FROM concern_subtype_chklist AS a
                       JOIN fac_department AS b ON a.fac_dept_id_fk = b.fac_dept_id
                       WHERE a.fac_type_id_fk = ? 
                       AND a.fac_dept_id_fk IN (
                           SELECT fac_dept_id FROM fac_dept_map 
                           WHERE fac_id = ? AND acc_id = ?
                       )");
$stmt->bind_param("iii", $fat, $Fa, $p);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $dept_info[$row['fac_dept_id_fk']] = $row['dept_name'];
}
$stmt->close();

header('Content-Type: application/json');
$data_scorecard = [];

foreach ($dept_info as $dept_id => $dept_name) {

    // 1️⃣ Fetch Concern Type Report
    $concernRows = [];
    $tablequery1 = "CALL concern_type_report($fat, $Fa, $dept_id, $p)";
    $q2 = mysqli_query($con, $tablequery1);

    if ($q2) {
        // Prepare first row → header row manually
        $headerRow = [
            '',
            'Service Provision',
            'Patients\' Rights',
            'Overall Facility Score',
            'Inputs',
            'Support Services'
        ];
        $concernRows[] = $headerRow;

        // Now read the result row
        if ($row = mysqli_fetch_assoc($q2)) {
            $dataRow = [
                '',
                ($row['totalc1'] ? round($row['d1'] / $row['totalc1'] * 100, 2) . '%' : '0%'),
                ($row['totalc2'] ? round($row['d2'] / $row['totalc2'] * 100, 2) . '%' : '0%'),
                ($row['totalc3'] ? round($row['d3'] / $row['totalc3'] * 100, 2) . '%' : '0%'),
                ($row['totalc4'] ? round($row['d4'] / $row['totalc4'] * 100, 2) . '%' : '0%')
            ];
            $concernRows[] = $dataRow;
        }

        mysqli_free_result($q2);
        $con->next_result();
    }

    // 2️⃣ Fetch Detailed Report
    $detailRows = [];

    // Prepare header row for detailed table
    $headerDetailRow = [
        'Reference No.',
        'Measurable Element',
        'Checkpoint',
        'Compliance',
        'Assessment Method',
        'Means of Verification',
        'Remarks'
    ];
    $detailRows[] = $headerDetailRow;

    // Fetch Areas of Concern
    $aocQuery = "SELECT concern_name, concern_id FROM area_of_concern";
    $aocResult = mysqli_query($con, $aocQuery);

    while ($aoc = mysqli_fetch_assoc($aocResult)) {
        $conid = $aoc['concern_id'];

        // Add AOC header row
        $detailRows[] = [ $aoc['concern_name'] ];

        // Fetch subtypes
        $subtypeQuery = "SELECT Reference_No, c_subtype_id, area_of_con_subtypedeatils
                         FROM sarbsoft_nqa.area_of_concern_subtype
                         WHERE c_subtype_id IN (
                             SELECT DISTINCT c_subtype_id_fk
                             FROM chk_list_assessment
                             WHERE fac_id_fk = $Fa
                               AND ass_period_id = $p
                               AND fac_dept_id_fk = $dept_id
                               AND area_of_con_id_fk = $conid
                               AND fac_type_id = $fat
                         )
                         ORDER BY c_subtype_id ASC";
        $subtypeResult = mysqli_query($con, $subtypeQuery);

        while ($sub = mysqli_fetch_assoc($subtypeResult)) {
            $sub_id = $sub['c_subtype_id'];

            // Add subtype header row
            $detailRows[] = [
                $sub['Reference_No'],
                $sub['area_of_con_subtypedeatils']
            ];

            // Fetch detailed records
            $detailQuery = "CALL fac_tot_reports($Fa, $t, $sub_id, $conid, $dept_id, $p)";
            $detailResult = mysqli_query($con, $detailQuery);

            while ($item = mysqli_fetch_assoc($detailResult)) {
                $detailRow = [
                    $item['csqa_reference_id'],
                    $item['Measurable_Element'],
                    $item['Checkpoint'],
                    $item['ass_compliance'],
                    $item['Assessment_Method'],
                    $item['Means_of_Verification'],
                    $item['Remarks']
                ];
                $detailRows[] = $detailRow;
            }

            mysqli_free_result($detailResult);
            $con->next_result();
        }

        mysqli_free_result($subtypeResult);
        $con->next_result();
    }

    mysqli_free_result($aocResult);
    $con->next_result();

    // Add to output array
    $data_scorecard[] = [
        'dept_id' => $dept_id,
        'dept_name' => $dept_name,
        'concernRows' => $concernRows,
        'detailRows' => $detailRows
    ];
}

echo json_encode($data_scorecard);
?>
