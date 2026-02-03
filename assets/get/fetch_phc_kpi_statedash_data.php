<?php
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Facility_KPI_Outcome_Summary.csv"');
echo "\xEF\xBB\xBF";

include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$output = fopen('php://output', 'w');

/* =========================================================
   EXECUTE STORED PROCEDURE
========================================================= */
$result = mysqli_query($con, "CALL generate_outcomekpi_all()");
if (!$result) {
    fputcsv($output, ['Error executing stored procedure']);
    exit;
}

/* =========================================================
   READ COLUMN NAMES DYNAMICALLY
========================================================= */
$fields = mysqli_fetch_fields($result);

$header = [];
$monthCols = [];

foreach ($fields as $f) {
    if (in_array($f->name, ['fac_id'])) {
        continue; // ignore internal id
    }
    $header[] = $f->name;

    // detect month columns like Dec-25, Jan-26
    if (preg_match('/^\d{2}-[A-Za-z]{3}$/', $f->name)) {
        $monthCols[] = $f->name;
    }
}

fputcsv($output, $header);

/* =========================================================
   WRITE DATA
========================================================= */
while ($row = mysqli_fetch_assoc($result)) {

    $dataRow = [];

    foreach ($header as $col) {

        if (!isset($row[$col])) {
            $dataRow[] = 'No Data';
            continue;
        }

        // Map 1/0 to ✔ / No Data
        if ($row[$col] === '1' || $row[$col] === 1) {
            $dataRow[] = '✔';
        } elseif ($row[$col] === '0' || $row[$col] === 0) {
            $dataRow[] = 'x';
        } else {
            $dataRow[] = $row[$col];
        }
    }

    fputcsv($output, $dataRow);
}

/* =========================================================
   CLEANUP (VERY IMPORTANT)
========================================================= */
mysqli_free_result($result);
while (mysqli_more_results($con)) {
    mysqli_next_result($con);
}

fclose($output);
mysqli_close($con);
exit;
?>
