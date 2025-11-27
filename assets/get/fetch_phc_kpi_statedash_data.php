<?php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Facility_KPI_Outcome_Summary.csv"');

// Output UTF-8 BOM to ensure Excel opens as UTF-8
echo "\xEF\xBB\xBF";

include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$output = fopen('php://output', 'w');

// Table header
$header = ['District', 'Block', 'Facility', 'Facility Type'];

$months = [];
$startingMonth = strtotime("first day of last month");
for ($i = 0; $i < 8; $i++) {
    $months[] = date("M Y", strtotime("first day of -$i month", $startingMonth));
}

$header = array_merge($header, $months);
fputcsv($output, $header);

// Data query
$query = "CALL generate_outcomekpi_all()";
$result = mysqli_query($con, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $dataRow = [
            $row['Dist'],
            $row['Block'],
            $row['Facility'],
            $row['FacType']
        ];

        foreach ($months as $monthKey) {
            $value = isset($row[$monthKey]) ? $row[$monthKey] : 'No Data';
            $dataRow[] = $value;
        }

        fputcsv($output, $dataRow);
    }

    mysqli_free_result($result);
} else {
    fputcsv($output, ['Error running query']);
}

fclose($output);
mysqli_close($con);
exit;
?>
