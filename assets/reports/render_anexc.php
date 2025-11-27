<?php
ini_set('max_execution_time', 300);
if (session_status() === PHP_SESSION_NONE) session_start();

$facid = $_SESSION['u_facilityid'] ?? null;
?>

<div class="row">
    <div class="col-sm-12">
        <h5 class="fw-bold text-primary">
            <i class="bi bi-table me-2"></i>Annexure C - Month-wise Indicator Report
        </h5>

        <?php
        // Step 1: Fetch indicator definitions
        $indicatorMap = [];
        $indRes = $con->query("SELECT anexcid, anexc_indicators FROM anexc ORDER BY anexcid");
        while ($row = $indRes->fetch_assoc()) {
            $indicatorMap[$row['anexcid']] = $row['anexc_indicators'];
        }

        // Step 2: Fetch submissions
        $query = "SELECT anexcid, anexc_values, anexc_date, remarks 
                  FROM anexc_in 
                  WHERE anexc_fac_id = ? 
                  ORDER BY anexc_date";

        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $facid);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        $months = [];

        while ($row = $result->fetch_assoc()) {
            $indId = $row['anexcid'];
            $rawDate = $row['anexc_date']; // e.g., 2025/07
            $timestamp = strtotime(str_replace('/', '-', $rawDate) . '-01');
            $month = date('M-y', $timestamp); // e.g., Jul-25

            $months[$month] = true;
            $data[$indId][$month] = [
                'value' => $row['anexc_values'],
                'remarks' => !empty($row['remarks']) ? $row['remarks'] : 'NA'
            ];
        }

        if (empty($data)) {
            echo "<div class='alert alert-warning'>No Annexure C data found for your facility.</div>";
            return;
        }

        $months = array_keys($months);
        sort($months);
        ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <button class="btn btn-sm btn-outline-success" onclick="downloadExcel()">
                    <i class="bi bi-download me-1"></i>Download Excel
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm small align-middle text-center" id="anexcReport" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th rowspan="2">Sl no</th>
                                <th rowspan="2" class="text-start">Indicators</th>
                                <?php foreach ($months as $m): ?>
                                    <th colspan="2"><?= $m ?></th>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <?php foreach ($months as $m): ?>
                                    <th>Value</th>
                                    <th>Remarks</th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $serial = 1;
                            foreach ($indicatorMap as $id => $text): ?>
                                <tr>
                                    <td><?= $serial++ ?></td>
                                    <td class="text-start"><?= $text ?></td>
                                    <?php foreach ($months as $m): ?>
                                        <?php
                                        $entry = $data[$id][$m] ?? null;
                                        $val = $entry['value'] ?? '-';
                                        $rem = $entry['remarks'] ?? 'NA';
                                        ?>
                                        <td><?= $val ?></td>
                                        <td><?= $rem ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SheetJS for Excel Export -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
    function downloadExcel() {
        var table = document.getElementById("anexcReport");
        var wb = XLSX.utils.table_to_book(table, { sheet: "Annexure C" });
        XLSX.writeFile(wb, "AnnexureC_Report.xlsx");
    }
</script>
