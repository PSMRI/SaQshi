<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dept_id = $_SESSION['dept_id'];
$Fa = $_SESSION['u_facilityid'];
$p = $_SESSION['assperiod'];

$allRows = []; // initialize to avoid undefined variable error
$monthColumns = [];
?>
<div class="row">
    <div class="col-sm-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Outcome Report</h5>
                <button class="btn btn-sm btn-success" onclick="exportToExcel('table31')">Export</button>
            </div>
            <div class="card-body p-2" style="max-height: 400px; overflow: auto; font-size: 0.75rem;">
                <?php
                if (in_array($dept_id, [1, 2, 3, 4, 5,6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 23, 24, 25, 26, 27, 32, 33, 40,38,39,41])) {
                    $out_comerpt = "CALL dh_outcomerpt_test($Fa, $dept_id)";
                    $result = $con->query($out_comerpt);

                    if (!$result) {
                        echo "<div class='alert alert-danger'>Query failed: " . $con->error . "</div>";
                    } elseif ($result->num_rows == 0) {
                        echo "<div class='alert alert-warning'>No records found for this department.</div>";
                    } else {
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
                            $allRows[] = $row;
                        }
                ?>
                        <div class="table-responsive">
                            <table class="table w-auto small table-bordered" id="table31">

                                <thead>
                                    <?php

                                    // Group month-wise: "Jun-25 Num", "Jun-25 Den", "Jun-25 Result"
                                    $monthGroups = [];

                                    foreach ($columns as $column) {
                                        $colName = $column->name;
                                        if ($colName === 'out_come_hwcindi') continue;

                                        if (preg_match('/^(.*?)\s+\((Num|Den|Result)\)$/', $colName, $matches)) {
                                            $month = trim($matches[1]);  // e.g., 'Jun-25'
                                            $type = $matches[2];         // Num / Den / Result
                                            $monthGroups[$month][$type] = $colName;  // map type to actual column name
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <th colspan="<?= 3 + count($monthGroups) * 3 ?>" class="text-start fw-bold bg-light">
                                            Outcome Report - Facility: <?= htmlspecialchars($_SESSION['facname'] ?? 'N/A') ?>
                                        </th>
                                    </tr>
                                    <tr class="table-success">
                                        <th rowspan="2">Indicator</th>
                                        <th rowspan="2">Numerator</th>
                                        <th rowspan="2">Denominator</th>
                                        <?php
                                        // First row: Month names with colspan
                                        foreach ($monthGroups as $month => $parts) {
                                            echo "<th colspan='3' class='text-center'>$month</th>";
                                        }
                                        ?>
                                    </tr>
                                    <tr class="table-secondary">
                                        <?php
                                        foreach ($monthGroups as $month => $parts) {
                                            echo "<th class='text-center'>Num</th>";
                                            echo "<th class='text-center'>Den</th>";
                                            echo "<th class='text-center'>Result</th>";
                                        }
                                        ?>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($allRows as $row): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($row['out_come_hwcindi']) ?></td>
                                            <td class="fw-bold"><?= htmlspecialchars($row['num']) ?></td>
                                            <td class="fw-bold"><?= htmlspecialchars($row['deno']) ?></td>
                                            <?php
                                            foreach ($monthGroups as $month => $parts) {
                                                $numCol = $parts['Num'] ?? '';
                                                $denCol = $parts['Den'] ?? '';
                                                $resCol = $parts['Result'] ?? '';

                                                echo '<td class="text-center">' . htmlspecialchars($row[$numCol] ?? '-') . '</td>';
                                                echo '<td class="text-center">' . htmlspecialchars($row[$denCol] ?? '-') . '</td>';
                                                echo '<td class="text-center">' . htmlspecialchars($row[$resCol] ?? '-') . '</td>';
                                            }
                                            ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>
                <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($allRows)): ?>
    <!-- KPI CHARTS -->
    <div class="row">
        <div class="col-sm-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Outcome Progress</h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="downloadAllChartsAsPDF()">Download All Charts as PDF</button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($allRows as $row):
                            $indicator = trim(strip_tags($row['out_come_hwcindi']));
                            $chartId = 'chart_' . md5($indicator);
                            $labels = $values = [];

                            // Step 1: Gather all (month, value) pairs for Result columns
                            $monthValueMap = [];

                            foreach ($columns as $column) {
                                $colName = $column->name;
                                if (preg_match('/^(.*?)\s+\(Result\)$/', $colName, $matches)) {
                                    $month = $matches[1];
                                    $val = str_replace('%', '', trim($row[$colName] ?? ''));
                                    $monthValueMap[$month] = (is_numeric($val) && $val !== '') ? (float)$val : null;
                                }
                            }

                            // Step 2: Sort month keys in ascending order
                            uksort($monthValueMap, function ($a, $b) {
                                return strtotime("01 $a") <=> strtotime("01 $b");
                            });

                            // Step 3: Extract sorted labels and values
                            $labels = array_keys($monthValueMap);
                            $values = array_values($monthValueMap);


                        ?>
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-header bg-primary text-white fw-bold">
                                        <?= htmlspecialchars($indicator) ?>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="<?= $chartId ?>" height="220" data-title="<?= htmlspecialchars(substr($indicator, 0, 100)) ?>"></canvas>
                                        <div class="mt-2 text-end">
                                            <button type="button" class="btn btn-icon btn-secondary" onclick="downloadChartAsImage('<?= $chartId ?>')"><i class="feather icon-camera"></i></button>
                                            <button type="button" class="btn btn-icon btn-secondary" onclick="downloadChartAsPDF('<?= $chartId ?>')"><i class="bi bi-file-earmark-pdf"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
                            <script>
                                const ctx_<?= $chartId ?> = document.getElementById('<?= $chartId ?>').getContext('2d');
                                new Chart(ctx_<?= $chartId ?>, {
                                    type: 'line',
                                    data: {
                                        labels: <?= json_encode($labels) ?>,
                                        datasets: [{
                                            label: <?= json_encode($indicator) ?>,
                                            data: <?= json_encode($values) ?>,
                                            borderColor: 'rgba(75, 192, 192, 1)',
                                            backgroundColor: function(context) {
                                                const chart = context.chart;
                                                const {
                                                    ctx,
                                                    chartArea
                                                } = chart;
                                                if (!chartArea) return;
                                                const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                                                gradient.addColorStop(0, 'rgba(235, 71, 16, 0.4)');
                                                gradient.addColorStop(0.5, 'rgba(255, 255, 0, 0.3)');
                                                gradient.addColorStop(1, 'rgba(0, 200, 0, 0.4)');
                                                return gradient;
                                            },
                                            fill: true,
                                            tension: 0.3,
                                            pointRadius: 3
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        plugins: {
                                            legend: {
                                                display: false
                                            }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true
                                            }
                                        }
                                    }
                                });
                            </script>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    function downloadChart(chartId, type) {
        const canvas = document.getElementById(chartId);
        const title = canvas.dataset.title || chartId;
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = canvas.width;
        tempCanvas.height = canvas.height + 30;
        const ctx = tempCanvas.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
        ctx.drawImage(canvas, 0, 0);
        ctx.font = '16px Arial';
        ctx.fillStyle = '#000';
        ctx.textAlign = 'center';
        ctx.fillText(title, canvas.width / 2, canvas.height + 20);
        const link = document.createElement('a');
        link.download = title.replace(/\s+/g, '_') + '.png';
        link.href = tempCanvas.toDataURL('image/png');
        link.click();
    }

    async function downloadChartAsPDF(chartId, title = 'Chart') {
        const {
            jsPDF
        } = window.jspdf;
        const canvas = document.getElementById(chartId);
        const width = canvas.width,
            height = canvas.height,
            padding = 30;
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = width;
        tempCanvas.height = height + padding;
        const ctx = tempCanvas.getContext("2d");
        ctx.fillStyle = "#fff";
        ctx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
        ctx.drawImage(canvas, 0, 0);
        ctx.font = "16px Arial";
        ctx.fillStyle = "#000";
        ctx.textAlign = "center";
        ctx.fillText(title, width / 2, height + 20);
        const imgData = tempCanvas.toDataURL('image/png');
        const pdf = new jsPDF({
            orientation: "landscape"
        });
        pdf.addImage(imgData, 'PNG', 10, 10, 270, 160);
        pdf.save(title.replace(/\s+/g, '_') + '.pdf');
    }

    async function downloadAllChartsAsPDF() {
        const {
            jsPDF
        } = window.jspdf;
        const pdf = new jsPDF({
            orientation: 'landscape'
        });
        const chartCanvases = document.querySelectorAll('canvas');
        let first = true;
        for (const canvas of chartCanvases) {
            const title = canvas.dataset.title || canvas.id;
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = canvas.width;
            tempCanvas.height = canvas.height + 30;
            const ctx = tempCanvas.getContext("2d");
            ctx.fillStyle = "#fff";
            ctx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
            ctx.drawImage(canvas, 0, 0);
            ctx.font = "16px Arial";
            ctx.fillStyle = "#000";
            ctx.textAlign = "center";
            ctx.fillText(title, canvas.width / 2, canvas.height + 20);
            const imgData = tempCanvas.toDataURL('image/png');
            if (!first) pdf.addPage();
            first = false;
            pdf.addImage(imgData, 'PNG', 10, 10, 270, 160);
        }
        pdf.save('All_Outcome_Charts.pdf');
    }

    function exportToExcel(tableId, filename = 'Outcome_Report.xlsx') {
    const table = document.getElementById(tableId);
    if (!table) {
        alert("Table not found.");
        return;
    }

    const workbook = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
    XLSX.writeFile(workbook, filename);}
</script>