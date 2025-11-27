<?php
ini_set('max_execution_time', 300);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("assets/conn/db.php");

$fac_id = $_SESSION['u_facilityid'];
$facility_name_safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $_SESSION['facname'] ?? 'Facility');

?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>

<script>
var chartData = []; // Collect chart info here
</script>

<?php

$allowed_depts = [
    7 => ['procedure' => 'mkpi_rpt_SNCU1', 'display' => 'SNCU'],
    23 => ['procedure' => 'mkpi_rpt_nrc1', 'display' => 'NRC'],
    6 => ['procedure' => 'mkpi_rpt_pward', 'display' => 'Paediatric Ward'],
    5 => ['procedure' => 'mkpi_rpt_OPD', 'display' => 'Paediatric OPD']
];

echo "<button class='btn btn-primary mb-4' onclick='exportAllToExcel()'>Download Overall KPI Report (All Departments)</button>";

foreach ($allowed_depts as $dept_no => $info) {
    $procedure = $info['procedure'];
    $display_name = $info['display'];

    echo "<h4 class='mt-4 mb-2 text-primary'>Department Report: $display_name</h4>";

    $query = "CALL $procedure(?, ?);";

    $stmt2 = $con->prepare($query);
    $stmt2->bind_param("ii", $dept_no, $fac_id);
    $stmt2->execute();
    $result = $stmt2->get_result();

    if ($result && $result->num_rows > 0) {
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $metric_name = $row['metric_name'];
            $month_in = $row['month_in'];
            $metric_value = $row['metric_value'];

            $data[$metric_name][$month_in] = $metric_value;
        }

        $table_id = "table3_dept_" . $dept_no;
        echo "<div class='card mb-4'>
                <div class='card-body'>
                  <input type='button' value='Export $display_name to Excel' class='btn btn-success mb-3' onclick='exportToExcel(\"$table_id\", \"$display_name\")' />
                  <div class='table-responsive'>
                    <table class='table w-auto small table-bordered' id='$table_id' data-dept-name='$display_name' data-facility-name='" . htmlspecialchars($facility_name_safe) . "'>";

        $months = array_keys($data[array_keys($data)[0]]);
        $month_count = count($months);
        $colspan = $month_count + 1;

        echo "<thead>";
        echo "<tr>";
        echo "<th colspan='$colspan' style='text-align:left; font-size:16px; background-color:#f2f2f2;'>MusQan KPI | Department: $display_name</th>";
        echo "</tr>";

        echo "<tr>";
        echo "<th><b>Metric</b></th>";
        foreach ($months as $month) {
            echo "<th><b>$month</b></th>";
        }
        echo "</tr>";
        echo "</thead>";

        echo "<tbody>";
        foreach ($data as $metric => $months_data) {
            echo "<tr><td>$metric</td>";
            foreach ($months as $month) {
                $value = isset($months_data[$month]) ? $months_data[$month] : 0;
                echo "<td>$value</td>";
            }
            echo "</tr>";
        }
        echo "</tbody></table></div></div>";

        // Cards → 3 per row
        echo "<div class='row'>";

        foreach ($data as $metric => $months_data) {
            $metric_id_safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $metric);
            $chart_id = "chart_" . $dept_no . "_" . $metric_id_safe;

            echo "<div class='col-md-4 mb-4'>";
            echo "<div class='card h-100'>";
            echo "<div class='card-header bg-primary text-white'>
                    <b>Metric:</b> $metric
               <span class='float-end' style='cursor:pointer; text-decoration:underline; color:white;' onclick='downloadChartAsImage(\"$chart_id\", \"$metric\")'>
    <i class='bi bi-download'></i>PNG
</span>


                  </div>";
            echo "<div class='card-body'>
                    <div class='table-responsive mb-3'>
                        <table class='table table-sm table-bordered'>
                          <thead>
                            <tr>";
            foreach ($months as $month) {
                echo "<th>$month</th>";
            }
            echo "</tr></thead><tbody><tr>";
            foreach ($months as $month) {
                $value = isset($months_data[$month]) ? $months_data[$month] : 0;
                echo "<td>$value</td>";
            }
            echo "</tr></tbody></table></div>";

            echo "<canvas id='$chart_id' height='200'></canvas>";

            // PART 2 — PUSH chartData here 👇
echo "<script>
chartData.push({
    id: '$chart_id',
    label: '$metric',
    labels: " . json_encode($months) . ",
    data: " . json_encode(array_values($months_data)) . "
});
</script>";
echo "</div></div></div>"; // close col-md-4
        }

        echo "</div>"; // close row

    } else {
        echo "<div class='alert alert-warning'>No data found for department $display_name.</div>";
    }

    mysqli_free_result($result);
    $con->next_result();
}

echo "<button class='btn btn-primary mb-4' onclick='exportAllToExcel()'>Download Overall KPI Report (All Departments)</button>";
?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    chartData.forEach(item => {
        var ctx = document.getElementById(item.id).getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: item.labels,
                datasets: [{
                    label: item.label,
                    data: item.data,
                    fill: false,
                    borderColor: getRandomColor(),
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    });
});

function downloadChartAsImage(chartId, metricName) {
    var canvas = document.getElementById(chartId);
    var link = document.createElement('a');
    link.download = metricName + '_chart.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

function getRandomColor() {
    const letters = '0123456789ABCDEF';
    let color = '#';
    for (let i = 0; i < 6; i++) {
        color += letters[Math.floor(Math.random() * 16)];
    }
    return color;
}
</script>
