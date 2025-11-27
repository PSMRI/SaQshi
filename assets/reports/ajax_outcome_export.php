<?php
session_start();
include_once("../conn/db.php");


$Fa = $_SESSION['u_facilityid'];
$p = $_SESSION['assperiod'];
$fat = $_SESSION['f_type_id'];
$acc_id = $p;

$dept_info = [];
$stmt = $con->prepare("SELECT DISTINCT a.fac_dept_id_fk, b.dept_name
                       FROM concern_subtype_chklist AS a
                       JOIN fac_department AS b ON a.fac_dept_id_fk = b.fac_dept_id
                       WHERE a.fac_type_id_fk = ? 
                       AND a.fac_dept_id_fk IN (
                           SELECT fac_dept_id FROM fac_dept_map 
                           WHERE fac_id = ? AND acc_id = ?
                       )");
$stmt->bind_param("iii", $fat, $Fa, $acc_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $dept_info[$row['fac_dept_id_fk']] = $row['dept_name'];
}
$stmt->close();

header('Content-Type: application/json');
$data_outcome = [];

foreach ($dept_info as $dept_id => $dept_name) {
    $monthColumns = [];
    $allRows = [];

    $out_comerpt = "CALL dh_outcomerpt($Fa, $dept_id)";
    $result = $con->query($out_comerpt);

    if ($result) {
        $columns = $result->fetch_fields();
        foreach ($columns as $column) {
            if ($column->name !== 'out_come_hwcindi') {
                $monthColumns[] = $column->name;
            }
        }

        usort($monthColumns, function ($a, $b) {
            return strtotime("01 " . $a) <=> strtotime("01 " . $b);
        });

        while ($row = $result->fetch_assoc()) {
            $entry = [
                'indicator' => $row['out_come_hwcindi']
            ];
            foreach ($monthColumns as $month) {
                $entry[$month] = $row[$month] ?? '-';
            }
            $allRows[] = $entry;
        }

        mysqli_free_result($result);
        $con->next_result();
    }

    $data_outcome[] = [
        'dept_id' => $dept_id,
        'dept_name' => $dept_name,
        'monthColumns' => $monthColumns,
        'rows' => $allRows
    ];
}

echo json_encode($data_outcome);
?>
