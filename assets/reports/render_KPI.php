<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dept_id = $_SESSION['dept_id'] ?? null;
$Fa = $_SESSION['u_facilityid'] ?? null;
$p = $_SESSION['assperiod'] ?? null;
$fat = $_SESSION['fat'] ?? null;
$F = $_SESSION['f_type_id'] ?? null;

$indicators = [];
$month_wise_data = [];
$months = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rt']) && $_POST['rt'] == 5) {

    // OUTCOME REPORT for F=8 or F=4
    if (in_array($F, [8, 4])) {
        $out_comerpt = "CALL hwc_kpi($Fa)";
        $out_comerptquery = $con->query($out_comerpt);

        $indicators = [
            'opd_cases_per_month' => 'No. of OPD Cases per month',
            'followup_cases_per_month' => 'No. of follow up cases (repeat visit) per month',
            'dropout_cases' => 'No. of drop out cases following start of the treatment',
            'dropout_rate_ncds' => 'Drop out rate for NCDs',
            'stockout_days_essential_meds' => 'No. of stock out days of essential medicines (as per service Package)',
            'vhnd_conducted' => 'No of VHNDs conducted (for vulnerable population)',
            'high_risk_pregnancy' => 'No. of high risk pregnancy identified during ANC',
            'diarrhea_cases_ors_zn' => 'No. of Children with diarrhoea treated with ORS & Zn',
            'anemia_cases_treated' => 'No. of Anaemia cases treated successfully',
            'blood_pressor' => 'Percentage of cases on treatment achieved blood pressure control',
            'blood_sugar' => 'Percentage of cases on treatment achieved blood sugar control',
            'satisfaction_score' => 'Client Satisfaction Score (Patients)',
            'chronic_cases_3_months' => 'Percentage of chronic cases who started treatment at PHC/above are still under treatment for last 3 months'
        ];

        if ($out_comerptquery && $out_comerptquery->num_rows > 0) {
            while ($row = $out_comerptquery->fetch_assoc()) {
                $month = $row['month_name'];
                if (!in_array($month, $months)) {
                    $months[] = $month;
                }
                foreach ($indicators as $key => $label) {
                    $month_wise_data[$key][$month] = $row[$key];
                }
            }
        } else {
            echo "<div class='alert alert-danger'>No records found.</div>";
        }
    }

    // PHC KPI for F=3
    elseif ($F == 3) {
        $query = "CALL phckpi_rpt(?)";
        $stmt2 = $con->prepare($query);
        $stmt2->bind_param("i", $Fa);
        $stmt2->execute();
        $result = $stmt2->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $metric_name = $row['metric_name'];
                $month_in = $row['month_in'];
                $metric_value = $row['metric_value'];
                if (!in_array($month_in, $months)) {
                    $months[] = $month_in;
                }
                $month_wise_data[$metric_name][$month_in] = $metric_value;
                $indicators[$metric_name] = $metric_name;
            }
        } else {
            echo "<div class='alert alert-danger'>No records found.</div>";
        }
    }

    // DH KPI for F=2
    elseif ($F == 2) {
        $query = "CALL dhkpi_rpt(?)";
        $stmt2 = $con->prepare($query);
        $stmt2->bind_param("i", $Fa);
        $stmt2->execute();
        $result = $stmt2->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $metric_name = $row['metric_name'];
                $month_in = $row['month_in'];
                $metric_value = $row['metric_value'];
                if (!in_array($month_in, $months)) {
                    $months[] = $month_in;
                }
                $month_wise_data[$metric_name][$month_in] = $metric_value;
                $indicators[$metric_name] = $metric_name;
            }
        } else {
            echo "<div class='alert alert-danger'>No records found.</div>";
        }
    }  elseif ($F == 1) {
        $query = "CALL chckpi_rpt(?)";
        $stmt2 = $con->prepare($query);
        $stmt2->bind_param("i", $Fa);
        $stmt2->execute();
        $result = $stmt2->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $metric_name = $row['metric_name'];
                $month_in = $row['month_in'];
                $metric_value = $row['metric_value'];
                if (!in_array($month_in, $months)) {
                    $months[] = $month_in;
                }
                $month_wise_data[$metric_name][$month_in] = $metric_value;
                $indicators[$metric_name] = $metric_name;
            }
        } else {
            echo "<div class='alert alert-danger'>No records found.</div>";
        }
    }else {
        echo "<div class='alert alert-danger'>No records found.</div>";
    }

    usort($months, function ($a, $b) {
        return strtotime("1 " . $a) - strtotime("1 " . $b);
    });
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<div class="row">
    <div class="col-sm-12">
        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">KPI Report</h5>
                <button class="btn btn-sm btn-success" onclick="exportToExcel('kpiTable', 'KPI_Report_<?= date("Y_m_d_H_i") ?>.xls')">Export</button>
            </div>
            <div class="card-body p-2" style="max-height: 400px; overflow: auto; font-size: 0.75rem;">
                <?php if (!empty($month_wise_data)): ?>
                    <table class="table table-bordered w-auto small" id="kpiTable">
                        <thead>
                            <tr>
                                <th>Indicator Name</th>
                                <?php foreach ($months as $month): ?>
                                    <th><?= htmlspecialchars($month) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($month_wise_data as $key => $monthData): ?>
                                <tr>
                                    <td><?= htmlspecialchars($indicators[$key] ?? $key) ?></td>
                                    <?php foreach ($months as $month): ?>
                                        <td><?= htmlspecialchars($monthData[$month] ?? 'NA') ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-warning">No KPI data available.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($month_wise_data)): ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">KPI Progress</h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="downloadAllChartsAsPDF()">Download All Charts as PDF</button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($month_wise_data as $key => $monthData):
                            $indicator = htmlspecialchars($indicators[$key] ?? $key);
                            $chartId = 'chart_' . md5($indicator);
                            $labels = $values = [];

                            foreach ($months as $month) {
                                $labels[] = $month;
                                $val = str_replace('%', '', trim($monthData[$month] ?? ''));
                                $values[] = (is_numeric($val) && $val !== '') ? (float)$val : null;
                            }
                        ?>
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-header bg-primary text-white fw-bold">
                                        <?= $indicator ?>
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
                                                const { ctx, chartArea } = chart;
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
        const { jsPDF } = window.jspdf;
        const canvas = document.getElementById(chartId);
        const width = canvas.width, height = canvas.height, padding = 30;
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
        const pdf = new jsPDF({ orientation: "landscape" });
        pdf.addImage(imgData, 'PNG', 10, 10, 270, 160);
        pdf.save(title.replace(/\s+/g, '_') + '.pdf');
    }

    async function downloadAllChartsAsPDF() {
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({ orientation: 'landscape' });
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
        pdf.save('All_KPI_Charts.pdf');
    }

    function exportToExcel(tableId, filename = 'KPI_Report.xls') {
        const table = document.getElementById(tableId);
        if (!table) {
            alert("Table not found.");
            return;
        }
        const html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" 
              xmlns:x="urn:schemas-microsoft-com:office:excel" 
              xmlns="http://www.w3.org/TR/REC-html40">
        <head><meta charset="utf-8"></head>
        <body>${table.outerHTML}</body></html>`;
        const blob = new Blob([html], { type: "application/vnd.ms-excel" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    }
</script>
