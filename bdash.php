<?php
include("assets/head/h.php");
// Initialize variable
$block_name = "";

// Fetch block name if block_id is in session
if (isset($_SESSION['block_id'])) {
    $block_id = intval($_SESSION['block_id']); // sanitize

    $sql = "SELECT block_name FROM block_master WHERE block_id = ?";
    $stmt = mysqli_prepare($con, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $block_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $block_name);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }
} else {
    // Optional: handle missing session ID
    $block_name = "Unknown Block";
}

?>

<div id="dashboard-content">
    <div class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="pagetitle mb-2">
                <h5 class="fw-bold text-primary mb-1">Block Dashboard</h5>
            </div>
            <!-- Leaflet CSS and JS (should be in <head> ideally, but here for simplicity) -->
            <div class="row">
                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <h5>Facility Certification Overview</h5>
                            <!-- Map container -->
                            <div id="map" style="width: 100%; height: 370px; border: 1px solid #ccc;"></div>

                            <!-- Map Legend -->
                            <div class="small text-muted">
                                <img src="https://maps.gstatic.com/mapfiles/ms2/micons/blue.png"
                                    style="width:6px;height:10px;vertical-align:middle;margin-right:4px;">
                                <span class="text-primary">State Certification</span>

                                &nbsp;&nbsp;

                                <img src="https://maps.gstatic.com/mapfiles/ms2/micons/green.png"
                                    style="width:6px;height:10px;vertical-align:middle;margin-right:4px;">
                                <span class="text-success">National Certification</span>

                                &nbsp;&nbsp;

                                <img src="https://maps.gstatic.com/mapfiles/ms2/micons/red.png"
                                    style="width:6px;height:10px;vertical-align:middle;margin-right:4px;">
                                <span class="text-danger">Expired Certificates</span>
                            </div>

                        </div>
                    </div>
                </div>


                <!-- ==================== FACILITY TABLE ==================== -->

                <div class="col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <h5>Facility Certification Details Overview</h5>

                            <div class="alert alert-warning mt-2">
                                <strong>⚠️ Expired Certifications:</strong>
                                <span id="expired-count">0</span> facilities —
                                <a href="#" id="download-expired" class="text-decoration-underline fw-bold">Download List</a>
                            </div>

                            <div class="table-responsive">
                                <table class="table datatable table-bordered table-striped table-hover small" id="facTable">
                                    <thead>
                                        <tr>
                                            <th>Facility Name</th>
                                            <th>Facility Type</th>
                                            <th>Certification Type</th>
                                            <th>Details</th>
                                            <th>Issue Date</th>
                                            <th>Validity</th>
                                            <th>Score</th> <!-- NEW -->
                                            <th>Cert Status</th> <!-- NEW -->
                                            <th>Assessment Mode</th> <!-- NEW -->
                                            <th>Assessment Date</th> <!-- NEW -->
                                            <th>District</th> <!-- NEW -->
                                        </tr>

                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== MAP SCRIPT ==================== -->
            <script>
                $(document).ready(function() {
                     var map = L.map('map').setView([25.2, 85.5], 8);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 18
                    }).addTo(map);

                    // Icon chooser
                    function getMarkerIcon(certType, isExpired) {
                        let iconUrl;
                        if (isExpired) {
                            iconUrl = 'https://maps.gstatic.com/mapfiles/ms2/micons/red.png'; // 🔴 Expired
                        } else {
                            switch ((certType || '').toLowerCase()) {
                                case 'state':
                                    iconUrl = 'https://maps.gstatic.com/mapfiles/ms2/micons/blue.png'; // 🟦 State
                                    break;
                                case 'national':
                                    iconUrl = 'https://maps.gstatic.com/mapfiles/ms2/micons/green.png'; // 🟩 National
                                    break;
                                default:
                                    iconUrl = 'https://maps.gstatic.com/mapfiles/ms2/micons/blue.png';
                            }
                        }
                        return new L.Icon({
                            iconUrl: iconUrl,
                            iconSize: [8, 12], // smaller icon
                            iconAnchor: [4, 12], // adjust anchor for smaller icon
                            popupAnchor: [1, -10], // adjust popup position
                            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                            shadowSize: [4, 4] // smaller shadow
                        });

                    }

                    // Fetch facility data
                    $.getJSON('assets/get/get_cert_data.php', function(facilities) {

                        let tableData = [];
                        let expiredFacilities = [];
                        let stateCount = 0,
                            nationalCount = 0;
                        const today = new Date();

                        facilities.forEach(function(facility) {

                            // --------------------------
                            // Expiry Check
                            // --------------------------
                            let isExpired = false;
                            if (facility.validity) {
                                const validDate = new Date(facility.validity);
                                if (validDate < today) isExpired = true;
                            }
                            if (isExpired) expiredFacilities.push(facility);

                            // --------------------------
                            // Marker on Map
                            // --------------------------
                            if (facility.lat && facility.longi) {

                                const icon = getMarkerIcon(facility.cert_type, isExpired);

                                const marker = L.marker([facility.lat, facility.longi], {
                                    icon
                                }).addTo(map);

                                const tooltip = `
                <b>${facility.fac_name}</b><br>
                Type: ${facility.fac_type}<br>
                Certification: ${facility.cert_type}<br>
                Score: ${facility.score ?? 'N/A'}<br>
                Status: ${facility.Cert_status ?? 'N/A'}<br>
                Mode: ${facility.ass_mod ?? 'N/A'}<br>
                Assessment Date: ${facility.date_of_ass ?? 'N/A'}<br>
                Validity: ${facility.validity || 'N/A'}<br>
                Details: ${facility.cert_detailscol || ''}<br>
                District: ${facility.dist ?? 'N/A'}
            `;

                                marker.bindTooltip(tooltip, {
                                    permanent: false,
                                    direction: 'top',
                                    offset: [0, -10]
                                });
                            }

                            // --------------------------
                            // Table Row Data
                            // --------------------------
                            tableData.push({
                                data: [
                                    facility.fac_name,
                                    facility.fac_type,
                                    facility.cert_type,
                                    facility.cert_detailscol,
                                    facility.cert_issue,
                                    facility.validity,
                                    facility.score, // NEW
                                    facility.Cert_status, // NEW
                                    facility.ass_mod, // NEW
                                    facility.date_of_ass, // NEW
                                    facility.dist // NEW
                                ],
                                expired: isExpired
                            });

                            // Count types
                            if ((facility.cert_type || '').toLowerCase() === 'state') stateCount++;
                            if ((facility.cert_type || '').toLowerCase() === 'national') nationalCount++;
                        });

                        // --------------------------
                        // Update Counts
                        // --------------------------
                        $('#state-cert-count').text(stateCount);
                        $('#national-cert-count').text(nationalCount);
                        $('#expired-count').text(expiredFacilities.length);

                        // --------------------------
                        // DataTable
                        // --------------------------
                        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#facTable')) {
                            $('#facTable').DataTable().clear().destroy();
                        }

                        $('#facTable').DataTable({
                            data: tableData.map(t => t.data),
                            columns: [{
                                    title: "Facility Name"
                                },
                                {
                                    title: "Type"
                                },
                                {
                                    title: "Cert. Type"
                                },
                                {
                                    title: "Details"
                                },
                                {
                                    title: "Issue Date"
                                },
                                {
                                    title: "Validity"
                                },
                                {
                                    title: "Score"
                                }, // NEW
                                {
                                    title: "Cert Status"
                                }, // NEW
                                {
                                    title: "Assessment Mode"
                                }, // NEW
                                {
                                    title: "Assessment Date"
                                }, // NEW
                                {
                                    title: "District"
                                } // NEW
                            ],
                            createdRow: function(row, data, dataIndex) {
                                const rowInfo = tableData[dataIndex];
                                if (rowInfo.expired) $(row).addClass('expired-row');
                            },
                            pageLength: 5,
                            dom: 'Bfrtip',
                            buttons: [{
                                extend: 'excelHtml5',
                                title: 'Facility Certification Overview',
                                text: '📥 Export to Excel'
                            }]
                        });

                        // --------------------------
                        // CSV Download for Expired
                        // --------------------------
                        $('#download-expired').on('click', function(e) {
                            e.preventDefault();

                            if (expiredFacilities.length === 0) {
                                alert("No expired facilities found.");
                                return;
                            }

                            let csv = "Facility Name,Facility Type,Cert Type,Details,Issue Date,Validity,Score,Status,Mode,Assessment Date,District\n";

                            expiredFacilities.forEach(f => {
                                csv += `"${f.fac_name}","${f.fac_type}","${f.cert_type}","${f.cert_detailscol}","${f.cert_issue}","${f.validity}","${f.score}","${f.Cert_status}","${f.ass_mod}","${f.date_of_ass}","${f.dist}"\n`;
                            });

                            const blob = new Blob([csv], {
                                type: "text/csv;charset=utf-8;"
                            });
                            const link = document.createElement('a');
                            link.href = URL.createObjectURL(blob);
                            link.download = 'Expired_Facility_List.csv';
                            link.click();
                        });

                    });

                });
            </script>
            <style>
                .small-card {
                    border-radius: 14px;
                    box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
                    border: 0;
                    min-height: 90px;
                }

                .small-card .icon {
                    font-size: 1.4rem;
                    opacity: .9;
                }

                .small-card .value {
                    font-size: 1.3rem;
                    font-weight: 700;
                    line-height: 1.2;
                }

                .small-card .label {
                    font-size: .75rem;
                    opacity: .9;
                }

                .small-card .ratio {
                    font-size: .7rem;
                    opacity: .85;
                }
            </style>

            <!-- ==================== REST OF YOUR DASHBOARD ==================== -->
            <!-- ================== Assessment Summary Section ================== -->
            <div class="card shadow-sm border rounded-3 mt-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-1">
                        <h6 class="fw-bold text-primary mb-0">
                            Assessment Summary by Performance Category
                        </h6>
                        <div class="text-end">
                            <span class="small d-block fw-semibold text-primary">
                                * Total number of assessments done by facilities — shows total assessments and % distribution
                            </span>
                            <span class="small d-block fw-semibold text-primary">
                                * Categories represent assessments scoring &lt;50%, 50–80%, &gt;80%.
                            </span>
                        </div>
                    </div>

                    <?php
                    /* =========================================================
                            FETCH DATA WITH STATUS
                            ========================================================= */
                    $sql = "
                                        SELECT *,
                                            CASE
                                                WHEN IFNULL(obt,0)=0 AND IFNULL(tot,0)=0 THEN 'Not Started'
                                                WHEN IFNULL(obt,0) < IFNULL(tot,0) THEN 'In Progress'
                                                WHEN IFNULL(obt,0) = IFNULL(tot,0) THEN 'Completed'
                                                ELSE 'Unknown'
                                            END AS status
                                        FROM state_dash_view
                                        WHERE fac_id <> 1 and block_id=$block_id
                                        ";

                    $res = mysqli_query($con, $sql);

                    /* =========================================================
                            INITIALIZE COUNTERS
                            ========================================================= */
                    $gt80 = $btw50_80 = $lt50 = 0;
                    $completed = $in_progress = $not_started = 0;

                    $total_facilities = 0;
                    $total_completed_score = 0;

                    /* =========================================================
                    PROCESS DATA
                    ========================================================= */
                    while ($row = mysqli_fetch_assoc($res)) {

                        $p = floatval($row['p1']);
                        $total_facilities++;

                        // Facility status counters
                        switch ($row['status']) {
                            case 'Completed':
                                $completed++;
                                $total_completed_score += $p;

                                // Score buckets ONLY for completed
                                if ($p > 80) {
                                    $gt80++;
                                } elseif ($p >= 50 && $p < 80) {
                                    $btw50_80++;
                                } else {
                                    $lt50++;
                                }
                                break;

                            case 'In Progress':
                                $in_progress++;
                                break;

                            case 'Not Started':
                                $not_started++;
                                break;
                        }
                    }

                    /* =========================================================
                        AVERAGE SCORE (COMPLETED ONLY)
                        ========================================================= */
                    $avg_p = $completed > 0 ? round($total_completed_score / $completed, 2) : 0;
                    ?>
                    <div class="row g-3">
                        <div class="col-lg-6 col-md-12">

                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light fw-bold py-2">
                                    📊 Assessment Status
                                </div>

                                <div class="card-body py-2">
                                    <div class="row g-2 text-center">

                                        <div class="col-4">
                                            <div class="card small-card bg-success text-white">
                                                <div class="card-body py-2">
                                                    <div class="icon"><i class="bi bi-award-fill"></i></div>
                                                    <div class="value"><?= $gt80 ?></div>
                                                    <div class="ratio">/ <?= $completed ?></div>
                                                    <div class="label">&gt; 80%</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-4">
                                            <div class="card small-card bg-warning text-dark">
                                                <div class="card-body py-2">
                                                    <div class="icon"><i class="bi bi-bar-chart-line-fill"></i></div>
                                                    <div class="value"><?= $btw50_80 ?></div>
                                                    <div class="ratio">/ <?= $completed ?></div>
                                                    <div class="label">50–80%</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-4">
                                            <div class="card small-card bg-danger text-white">
                                                <div class="card-body py-2">
                                                    <div class="icon"><i class="bi bi-exclamation-circle-fill"></i></div>
                                                    <div class="value"><?= $lt50 ?></div>
                                                    <div class="ratio">/ <?= $completed ?></div>
                                                    <div class="label">&lt; 50%</div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-lg-6 col-md-12">

                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light fw-bold py-2">
                                    🏥 Facility Progress
                                </div>

                                <div class="card-body py-2">
                                    <div class="row g-2 text-center">

                                        <div class="col-4">
                                            <div class="card small-card bg-success text-white">
                                                <div class="card-body py-2">
                                                    <div class="icon"><i class="bi bi-check-circle-fill"></i></div>
                                                    <div class="value"><?= $completed ?></div>
                                                    <div class="ratio">/ <?= $total_facilities ?></div>
                                                    <div class="label">Completed</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-4">
                                            <div class="card small-card bg-warning text-dark">
                                                <div class="card-body py-2">
                                                    <div class="icon"><i class="bi bi-arrow-repeat"></i></div>
                                                    <div class="value"><?= $in_progress ?></div>
                                                    <div class="ratio">/ <?= $total_facilities ?></div>
                                                    <div class="label">In Progress</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-4">
                                            <div class="card small-card bg-secondary text-white">
                                                <div class="card-body py-2">
                                                    <div class="icon"><i class="bi bi-hourglass-split"></i></div>
                                                    <div class="value"><?= $not_started ?></div>
                                                    <div class="ratio">/ <?= $total_facilities ?></div>
                                                    <div class="label">Not Started</div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body">

                            <!-- REPORT CONTENT TO EXPORT -->
                            <div id="report-content" style="
                    font-family: 'Arial', sans-serif;
                    font-size: 14px;
                    line-height: 1.6;
                    padding: 10px;
                    background: white;
                    color: #333;
                ">
                                <center>
                                    <h4 class="card-title" style="font-size: 20px; margin-bottom: 5px; color: #0056b3;">
                                        Block Compliance Summary – Report
                                    </h4>
                                    <h4 style="font-size: 16px; margin-bottom: 15px; color: #333;">
                                        Block: <strong><?= htmlspecialchars($block_name) ?></strong>
                                    </h4>
                                </center>
                                <?php
                                $green_zone = [];
                                $yellow_zone = [];
                                $red_zone = [];

                                $call_count = "SELECT * FROM state_dash_view WHERE block_id=$block_id and p <>0 and obt=tot";
                                $count = mysqli_query($con, $call_count);

                                while ($row = mysqli_fetch_assoc($count)) {
                                    $p = (isset($row['marks']) && isset($row['f']) && $row['marks'] && $row['f'])
                                        ? round(($row['marks'] / $row['f']) * 100, 2)
                                        : 0;
                                    $entry = [
                                        'block' => $row['Block_Name'],
                                        'name' => $row['fac_name'],
                                        'type' => $row['facilities_type'],
                                        'score' => $p
                                    ];

                                    if ($p > 80) {
                                        $green_zone[] = $entry;
                                    } elseif ($p >= 50 && $p <= 79.99) {
                                        $yellow_zone[] = $entry;
                                    } else {
                                        $red_zone[] = $entry;
                                    }
                                }

                                mysqli_free_result($count);
                                $con->next_result();
                                ?>

                                <?php
                                $total_facilities = $gt80 + $btw50_80 + $lt50;

                                $gt80_percent = $total_facilities > 0 ? round(($gt80 / $total_facilities) * 100, 1) : 0;
                                $btw50_80_percent = $total_facilities > 0 ? round(($btw50_80 / $total_facilities) * 100, 1) : 0;
                                $lt50_percent = $total_facilities > 0 ? round(($lt50 / $total_facilities) * 100, 1) : 0;

                                ?>

                                <p>
                                    In this block , a total of <strong><?= $total_facilities ?> facilities</strong> were assessed.
                                    Out of these, <strong><?= $gt80 ?> facilities (<?= $gt80_percent ?>%)</strong> achieved a compliance score greater than <strong>80%</strong>, reflecting high performance.
                                    An additional <strong><?= $btw50_80 ?> facilities (<?= $btw50_80_percent ?>%)</strong> scored between <strong>50% and 80%</strong>.
                                    However, <strong><?= $lt50 ?> facilities (<?= $lt50_percent ?>%)</strong> are below the <strong>50%</strong> compliance threshold and need focused improvement.
                                </p>

                                <p>
                                    Certification coverage includes:
                                </p>
                                <ul style="margin-bottom: 15px;">
                                    <li>State Certified Facilities: <strong><span id="state-cert-count">...</span></strong></li>
                                    <li>National Certified Facilities: <strong><span id="national-cert-count">...</span></strong></li>
                                </ul>

                                <h5 style="font-size: 16px; margin-top: 20px; color: green;">Green Zone - Facilities with > 80%</h5>
                                <table style="width:100%; border-collapse: collapse; font-size: 13px;">
                                    <thead>
                                        <tr>
                                            <th style="border: 1px solid #ccc; padding: 5px;">Sl. No.</th>
                                            <th style="border: 1px solid #ccc; padding: 5px;">Block Name</th>
                                            <th style="border: 1px solid #ccc; padding: 5px;">Facility Name</th>
                                            <th style="border: 1px solid #ccc; padding: 5px;">Facility Type</th>
                                            <th style="border: 1px solid #ccc; padding: 5px;">Score %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $slno = 1; ?>
                                        <?php foreach ($green_zone as $row): ?>
                                            <tr>
                                                <td style="border: 1px solid #ccc; padding: 5px;"><?= $slno++ ?></td>
                                                <td style="border: 1px solid #ccc; padding: 5px;"><?= htmlspecialchars($row['block']) ?></td>
                                                <td style="border: 1px solid #ccc; padding: 5px;"><?= htmlspecialchars($row['name']) ?></td>
                                                <td style="border: 1px solid #ccc; padding: 5px;"><?= htmlspecialchars($row['type']) ?></td>
                                                <td style="border: 1px solid #ccc; padding: 5px;"><?= $row['score'] ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <h5 style="font-size: 16px; margin-top: 20px; color: orange;">Yellow Zone - Facilities with 50% to 79%</h5>
                                <table style="width:100%; border-collapse: collapse; font-size: 13px;">
                                    <thead>
                                        <tr>
                                            <th style="border: 1px solid #ccc; padding: 5px;">Sl. No.</th>
                                            <th style="border: 1px solid #ccc; padding: 5px;">Block Name</th>
                                            <th style="border: 1px solid #ccc; padding: 5px;">Facility Name</th>
                                            <th style="border: 1px solid #ccc; padding: 5px;">Facility Type</th>
                                            <th style="border: 1px solid #ccc; padding: 5px;">Score %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $slno = 1; ?>
                                        <?php foreach ($yellow_zone as $row): ?>
                                            <tr>
                                                <td style="border: 1px solid #ccc; padding: 5px;"><?= $slno++ ?></td>
                                                <td style="border: 1px solid #ccc; padding: 5px;"><?= htmlspecialchars($row['block']) ?></td>
                                                <td style="border: 1px solid #ccc; padding: 5px;"><?= htmlspecialchars($row['name']) ?></td>
                                                <td style="border: 1px solid #ccc; padding: 5px;"><?= htmlspecialchars($row['type']) ?></td>
                                                <td style="border: 1px solid #ccc; padding: 5px;"><?= $row['score'] ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <h5 style="font-size: 16px; margin-top: 20px; color: red;">Red Zone - Facilities with < 50%</h5>
                                        <table style="width:100%; border-collapse: collapse; font-size: 13px;">
                                            <thead>
                                                <tr>
                                                    <th style="border: 1px solid #ccc; padding: 5px;">Sl. No.</th>
                                                    <th style="border: 1px solid #ccc; padding: 5px;">Block Name</th>
                                                    <th style="border: 1px solid #ccc; padding: 5px;">Facility Name</th>
                                                    <th style="border: 1px solid #ccc; padding: 5px;">Facility Type</th>
                                                    <th style="border: 1px solid #ccc; padding: 5px;">Score %</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $slno = 1; ?>
                                                <?php foreach ($red_zone as $row): ?>
                                                    <tr>
                                                        <td style="border: 1px solid #ccc; padding: 5px;"><?= $slno++ ?></td>
                                                        <td style="border: 1px solid #ccc; padding: 5px;"><?= htmlspecialchars($row['block']) ?></td>
                                                        <td style="border: 1px solid #ccc; padding: 5px;"><?= htmlspecialchars($row['name']) ?></td>
                                                        <td style="border: 1px solid #ccc; padding: 5px;"><?= htmlspecialchars($row['type']) ?></td>
                                                        <td style="border: 1px solid #ccc; padding: 5px;"><?= $row['score'] ?>%</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>

                                        <p class="text-muted" style="font-size: 12px; margin-top: 20px;">
                                            <em>Report generated on <?= date('d M Y h:i A') ?></em>
                                        </p>

                                        <p class="text-muted" style="font-size: 12px; margin-top: 5px;">
                                            <em>This report is generated by <strong>SaQshi</strong></em>
                                        </p>

                            </div> <!-- END of report-content -->

                            <!-- PDF Button OUTSIDE report-content -->
                            <div class="text-end mb-3 mt-3">
                                <button class="btn btn-danger" onclick="downloadDashboardAsPDF()">Download Dashboard as PDF</button>
                            </div>

                        </div> <!-- end card-body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
            </div> <!-- end row -->


            <!-- Load html2pdf.js -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

            <!-- PDF Export Script -->
            <script>
                async function downloadDashboardAsPDF() {
                    const {
                        jsPDF
                    } = window.jspdf;
                    const dashboard = document.getElementById('report-content');

                    window.scrollTo(0, 0);

                    html2canvas(dashboard, {
                        scale: 2,
                        useCORS: true,
                        ignoreElements: el => el.classList.contains('exclude-from-pdf')
                    }).then(canvas => {
                        const imgData = canvas.toDataURL('image/png');
                        const pdf = new jsPDF('p', 'mm', 'a4');
                        const pdfWidth = pdf.internal.pageSize.getWidth();
                        const pdfHeight = pdf.internal.pageSize.getHeight();

                        const imgWidth = pdfWidth;
                        const imgHeight = (canvas.height * pdfWidth) / canvas.width;

                        let heightLeft = imgHeight;
                        let position = 0;

                        // Add first page
                        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                        heightLeft -= pdfHeight;

                        // Add remaining pages if needed
                        while (heightLeft > 0) {
                            position = heightLeft - imgHeight;
                            pdf.addPage();
                            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                            heightLeft -= pdfHeight;
                        }

                        pdf.save("SaQshi_Dist_Report.pdf");
                    }).catch(err => {
                        console.error("PDF generation error:", err);
                    });
                }
            </script>



            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">
                                Assessment Summary by Facility
                                <a href="assets/export/export_block_score_card.php">
                                    <i class="bi bi-arrow-down-circle-fill"></i>
                                </a>
                            </h6>
                            <!-- Note below title -->
                            <p class="text-primary small mb-3 fst-italic">
                                * This summary also includes facilities that have not yet started the assessment,
                                and facilities that have undergone the assessment process twice or thrice.
                            </p>
                            <div class="table-responsive">
                                <table class="table datatable table-bordered table-striped table-hover small" id="tbl_exporttable_to_xls">
                                    <thead>
                                        <tr class="table-primary">
                                            <th>District</th>
                                            <th>Block</th>
                                            <th>Type</th>
                                            <th>Name</th>
                                            <th>Ass.</th>
                                            <th>Non</th>
                                            <th>Partially</th>
                                            <th>Fully</th>
                                            <th>Comp.</th>
                                            <th>Total</th>
                                            <th>%</th>
                                            <th>PDist.</th>
                                            <th>Obt.</th>
                                            <th>Max.Score</th>
                                            <th>%</th>
                                            <th>status</th>
                                            <th>Std</th>
                                            <th>Expd</th>
                                            <th>Compd</th>
                                            <th><i class="bi bi-arrow-down-circle-fill"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $call_count = "SELECT 
    *,
    CASE
        WHEN obt < tot THEN 'In Progress'
        WHEN obt = tot THEN 'Completed'
        ELSE 'Not started'
    END AS status
FROM state_dash_view
WHERE fac_id NOT IN (1) and block_id=$block_id;";
                                        $count = mysqli_query($con, $call_count);
                                        function renderRow($row, $percentageClass, $marksClass, $p1Class)
                                        {
                                            echo "<tr>
                                                <td class='table-primary'>{$row['Dist_Name']}</td>
                                                <td class='table-primary'>{$row['Block_Name']}</td>
                                                <td class='table-primary'>{$row['facilities_type']}</td>
                                                <td class='table-primary'>{$row['fac_name']}</td>
                                                <td class='table-primary'>{$row['ass_name']}</td>
                                                <td class='{$percentageClass}'>{$row['zero']}</td>
                                                <td class='{$percentageClass}'>{$row['one']}</td>
                                                <td class='{$percentageClass}'>{$row['two']}</td>
                                                <td class='{$percentageClass}'>{$row['obt']}</td>
                                                <td class='{$percentageClass}'>{$row['tot']}</td>
                                                <td class='{$percentageClass}'>{$row['p']}%</td>
                                                <td class='table-info'><a href='assets/export/pdist_details.php?id={$row['fac_id']}'>{$row['non']}</a></td>
                                                <td class='{$marksClass}'>{$row['marks']}</td>
                                                <td class='{$marksClass}'>{$row['f']}</td>
                                                <td class='{$p1Class}'>{$row['p1']}%</td>
                                                <td class='table-primary'>{$row['status']}</td>
                                                 <td class='table-primary'>{$row['Start_date']}</td>
                                                  <td class='table-primary'>{$row['Expected_date']}</td>
                                                   <td class='table-primary'>{$row['ass_completed']}</td>
                                                <td class='table-success'><a href='assets/export/export_dist_dash_comp.php?id={$row['fac_id']}'><i class='bi bi-arrow-down-circle-fill'></i></a></td>
                                            </tr>";
                                        }

                                        while ($row = mysqli_fetch_array($count)) {
                                            $obtained = $row['p'];
                                            $row['p1'] = (isset($row['marks']) && isset($row['f']) && $row['marks'] && $row['f'])
                                                ? round(($row['marks'] / $row['f']) * 100, 2)
                                                : 0;

                                            $percentageClass = ($row['p'] > 70) ? "table-success" : (($row['p'] > 65) ? "table-warning" : "table-danger");
                                            $marksClass = ($row['p1'] > 70) ? "table-success" : (($row['p1'] > 65) ? "table-warning" : "table-danger");
                                            $p1Class = $marksClass;
                                            renderRow($row, $percentageClass, $marksClass, $p1Class);
                                        }

                                        mysqli_free_result($count);
                                        $con->next_result();
                                        ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
                <button class="btn btn-success mb-3" onclick="window.location.href='assets/get/fetch_phc_kpi_blockdash_data.php'">
                    Download KPI & Outcome Summary (Excel)
                </button>
                <button class="btn btn-success mb-3" onclick="window.location.href='assets/get/fetch_data_actionplan_block.php'">
                    DownloadAction Plan Completed/pending summary (Excel)
                </button>

                <script>
                    $(document).ready(function() {
                        // Initialize DataTable with your settings
                        $('#tbl_exporttable_to_xls').DataTable({
                            "pageLength": 5,
                            "lengthMenu": [5, 10, 25, 50, 100, "All"],
                            "paging": true,
                            "searching": true,
                            "ordering": true,
                            "info": true,
                            "language": {
                                "search": "Search table:"
                            }
                        });
                    }); // <-- only ONE closing });
                </script><?php include("assets/head/f.php"); ?>