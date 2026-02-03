<?php
include("assets/head/h.php");
ini_set('max_execution_time', 300); // 300 seconds = 5 minutes
?>
<div id="dashboard-content">
    <div class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="pagetitle mb-2">
                <h5 class="fw-bold text-primary mb-1">State Dashboard</h5>
            </div>

            <!-- ==================== FACILITY MAP ==================== -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <h5>Facility Certification Overview</h5>
                            <!-- Map container -->
                            <div id="map" style="width: 100%; height: 400px; border: 1px solid #ccc;"></div>

                            <!-- Map Legend -->
                            <h6 class="mt-2">
                                <img src="https://maps.gstatic.com/mapfiles/ms2/micons/blue.png" style="width:18px;height:28px;vertical-align:middle;margin-right:6px;">
                                <span class="fw-bold text-primary">State Certification</span>
                                &nbsp;&nbsp;&nbsp;
                                <img src="https://maps.gstatic.com/mapfiles/ms2/micons/green.png" style="width:18px;height:28px;vertical-align:middle;margin-right:6px;">
                                <span class="fw-bold text-success">National Certification</span>
                                &nbsp;&nbsp;&nbsp;
                                <img src="https://maps.gstatic.com/mapfiles/ms2/micons/red.png" style="width:18px;height:28px;vertical-align:middle;margin-right:6px;">
                                <span class="fw-bold text-danger">Expired Certificates</span>
                            </h6>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== FACILITY TABLE ==================== -->
            <div class="row">
                <div class="col-sm-12">
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
                                            <th>Certification Issue Date</th>
                                            <th>Validity</th>
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
                            iconSize: [10, 16],
                            iconAnchor: [8, 26],
                            popupAnchor: [1, -20],
                            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                            shadowSize: [10, 10]
                        });
                    }

                    // Fetch facility data
                    $.getJSON('assets/get/get_cert_data_state.php', function(facilities) {
                        let tableData = [];
                        let expiredFacilities = [];
                        let stateCount = 0,
                            nationalCount = 0;
                        const today = new Date();

                        facilities.forEach(function(facility) {
                            // Check expiry
                            let isExpired = false;
                            if (facility.validity) {
                                const validDate = new Date(facility.validity);
                                if (validDate < today) isExpired = true;
                            }
                            if (isExpired) expiredFacilities.push(facility);

                            // Marker
                            if (facility.lat && facility.longi) {
                                const icon = getMarkerIcon(facility.cert_type, isExpired);
                                const marker = L.marker([facility.lat, facility.longi], {
                                    icon
                                }).addTo(map);
                                const tooltip = `
                  <b>${facility.fac_name}</b><br>
                  Type: ${facility.fac_type}<br>
                  Certification: ${facility.cert_type}<br>
                  Validity: ${facility.validity || 'N/A'}<br>
                  Details: ${facility.cert_detailscol || ''}
                `;
                                marker.bindTooltip(tooltip, {
                                    permanent: false,
                                    direction: 'top',
                                    offset: [0, -10]
                                });
                            }

                            // Row
                            tableData.push({
                                data: [
                                    facility.fac_name,
                                    facility.fac_type,
                                    facility.cert_type,
                                    facility.cert_detailscol,
                                    facility.cert_issue,
                                    facility.validity,
                                ],
                                expired: isExpired
                            });

                            if ((facility.cert_type || '').toLowerCase() === 'state') stateCount++;
                            if ((facility.cert_type || '').toLowerCase() === 'national') nationalCount++;
                        });

                        // Update counts
                        $('#state-cert-count').text(stateCount);
                        $('#national-cert-count').text(nationalCount);
                        $('#expired-count').text(expiredFacilities.length);

                        // DataTable
                        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#facTable')) {
                            $('#facTable').DataTable().clear().destroy();
                        }

                        $('#facTable').DataTable({
                            data: tableData.map(t => t.data),
                            columns: [{
                                    title: "Facility Name"
                                },
                                {
                                    title: "Facility Type"
                                },
                                {
                                    title: "Certification Type"
                                },
                                {
                                    title: "Details"
                                },
                                {
                                    title: "Certification Issue Date"
                                },
                                {
                                    title: "Validity"
                                }
                            ],
                            createdRow: function(row, data, dataIndex) {
                                const rowInfo = tableData[dataIndex];
                                if (rowInfo.expired) {
                                    $(row).addClass('expired-row');
                                }
                            },
                            pageLength: 5,
                            dom: 'Bfrtip',
                            buttons: [{
                                extend: 'excelHtml5',
                                title: 'Facility Certification Overview',
                                text: '📥 Export to Excel'
                            }]
                        });

                        // Download expired list
                        $('#download-expired').on('click', function(e) {
                            e.preventDefault();
                            if (expiredFacilities.length === 0) {
                                alert('No expired facilities found.');
                                return;
                            }
                            let csv = "Facility Name,Facility Type,Certification Type,Details,Certification Issue Date,Validity\n";
                            expiredFacilities.forEach(f => {
                                csv += `"${f.fac_name}","${f.fac_type}","${f.cert_type}","${f.cert_detailscol}","${f.cert_issue}","${f.validity}"\n`;
                            });
                            const blob = new Blob([csv], {
                                type: 'text/csv;charset=utf-8;'
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
   STEP 1: TOTAL FACILITIES (FIXED COUNT)
========================================================= */
                    $sqlTotal = "
SELECT COUNT(DISTINCT fac_id) AS total_facilities
FROM state_dash_view
WHERE fac_id <> 1
";
                    $resTotal = mysqli_query($con, $sqlTotal);
                    $total_facilities = (int)mysqli_fetch_assoc($resTotal)['total_facilities'];


                    /* =========================================================
   STEP 2: FETCH LATEST ASSESSMENT PER FACILITY
========================================================= */
                    $sql = "
SELECT v.*,
    CASE
        WHEN IFNULL(v.obt,0)=0 AND IFNULL(v.tot,0)=0 THEN 'Not Started'
        WHEN IFNULL(v.obt,0) < IFNULL(v.tot,0) THEN 'In Progress'
        WHEN IFNULL(v.obt,0) = IFNULL(v.tot,0) THEN 'Completed'
        ELSE 'Unknown'
    END AS status
FROM state_dash_view v
INNER JOIN (
    SELECT fac_id, MAX(ass_period_id) AS latest_ass
    FROM state_dash_view
    WHERE fac_id <> 1
    GROUP BY fac_id
) latest
ON v.fac_id = latest.fac_id
AND v.ass_period_id = latest.latest_ass
";

                    $res = mysqli_query($con, $sql);


                    /* =========================================================
   STEP 3: INITIALIZE COUNTERS
========================================================= */
                    $gt80 = $btw50_80 = $lt50 = 0;
                    $completed = $in_progress = $not_started = 0;
                    $total_completed_score = 0;


                    /* =========================================================
   STEP 4: PROCESS LATEST ASSESSMENTS ONLY
========================================================= */
                    while ($row = mysqli_fetch_assoc($res)) {

                        $p = floatval($row['p1']);

                        switch ($row['status']) {

                            case 'Completed':
                                $completed++;
                                $total_completed_score += $p;

                                if ($p > 80) {
                                    $gt80++;
                                } elseif ($p >= 50) {
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
   STEP 5: FIX NOT STARTED COUNT
========================================================= */
                    $not_started = $total_facilities - ($completed + $in_progress);


                    /* =========================================================
   STEP 6: AVERAGE SCORE (LATEST COMPLETED ONLY)
========================================================= */
                    $avg_p = $completed > 0 ? round($total_completed_score / $completed, 2) : 0;
                    ?>

                    <!-- =========================================================
     UI OUTPUT (UNCHANGED CARD DESIGN)
========================================================= -->

                    <div class="row g-3">

                        <!-- ================== ASSESSMENT STATUS ================== -->
                        <div class="col-lg-6 col-md-12">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light fw-bold py-2">
                                    📊 Assessment Status (Latest Assessment)
                                </div>

                                <div class="card-body py-2">
                                    <div class="row g-2 text-center">

                                        <div class="col-4">
                                            <div class="card small-card bg-success text-white">
                                                <div class="card-body py-2">
                                                    <div class="value"><?= $gt80 ?></div>
                                                    <div class="ratio">/ <?= $completed ?></div>
                                                    <div class="label">&gt; 80%</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-4">
                                            <div class="card small-card bg-warning text-dark">
                                                <div class="card-body py-2">
                                                    <div class="value"><?= $btw50_80 ?></div>
                                                    <div class="ratio">/ <?= $completed ?></div>
                                                    <div class="label">50–80%</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-4">
                                            <div class="card small-card bg-danger text-white">
                                                <div class="card-body py-2">
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

                        <!-- ================== FACILITY PROGRESS ================== -->
                        <div class="col-lg-6 col-md-12">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light fw-bold py-2">
                                    🏥 Facility Progress (Latest Assessment)
                                </div>

                                <div class="card-body py-2">
                                    <div class="row g-2 text-center">

                                        <div class="col-4">
                                            <div class="card small-card bg-success text-white">
                                                <div class="card-body py-2">
                                                    <div class="value"><?= $completed ?></div>
                                                    <div class="ratio">/ <?= $total_facilities ?></div>
                                                    <div class="label">Completed</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-4">
                                            <div class="card small-card bg-warning text-dark">
                                                <div class="card-body py-2">
                                                    <div class="value"><?= $in_progress ?></div>
                                                    <div class="ratio">/ <?= $total_facilities ?></div>
                                                    <div class="label">In Progress</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-4">
                                            <div class="card small-card bg-secondary text-white">
                                                <div class="card-body py-2">
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



                    <!-- Average Display -->
                    <div class="text-end mt-2">

                        <small class="text-muted fst-italic text-primary">
                            Average compliance score across all assessments: <strong><?= $avg_p ?>%</strong>
                        </small>
                    </div>
                </div>
            </div>
            <!-- Optional: Hover Effect -->
            <style>
                .card.shadow-sm {
                    transition: all 0.2s ease-in-out;
                }

                .card.shadow-sm:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
                }
            </style>


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
                                        State Compliance Summary – Report
                                    </h4>
                                    <h4 style="font-size: 16px; margin-bottom: 15px; color: #333;">

                                        State: <strong>Bihar</strong>
                                    </h4>
                                </center>
                                <p class="text-muted mb-1" style="font-size: 12px;">
                                    * Assessments started or Completed vs Registered facilities
                                </p>

                                <style>
                                    .fac-card {
                                        min-width: 120px;
                                        height: 105px;
                                        border-radius: 12px;
                                        background: #fff;
                                        box-shadow: 0 4px 10px rgba(0, 0, 0, .08);
                                        position: relative;
                                        text-align: center;
                                        padding-top: 14px;
                                    }

                                    /* top indicator */
                                    .fac-card::before {
                                        content: "";
                                        position: absolute;
                                        top: 0;
                                        left: 0;
                                        height: 5px;
                                        width: 100%;
                                        border-radius: 12px 12px 0 0;
                                    }

                                    /* status colors */
                                    .fac-na {
                                        background: #f3f5f7;
                                        color: #6c757d;
                                    }

                                    .fac-na::before {
                                        background: #6c757d;
                                    }

                                    .fac-danger::before {
                                        background: #dc3545;
                                    }

                                    .fac-warning::before {
                                        background: #ffc107;
                                    }

                                    .fac-success::before {
                                        background: #28a745;
                                    }

                                    /* numbers */
                                    .fac-value {
                                        font-size: 22px;
                                        font-weight: 700;
                                        line-height: 1.1;
                                    }

                                    .fac-percent {
                                        font-size: 12px;
                                        font-weight: 600;
                                        margin-top: 1px;
                                    }

                                    /* label */
                                    .fac-label {
                                        margin-top: 6px;
                                        font-size: 12px;
                                        font-weight: 700;
                                        letter-spacing: .4px;
                                        text-transform: uppercase;
                                    }
                                </style>

                                <div class="d-flex flex-nowrap gap-3 justify-content-center overflow-auto py-3">

                                    <?php
                                    $call_q1 = "CALL state_dash_count";
                                    $q22 = mysqli_query($con, $call_q1);

                                    while ($row = mysqli_fetch_array($q22)) {

                                        $facilities = [
                                            'DH'    => [$row['DH'],    $row['DHcomp']],
                                            'SH'    => [$row['SH'],    $row['SHcomp']],
                                            'CHC'   => [$row['CHC'],   $row['CHCcomp']],
                                            'PHC'   => [$row['PHC'],   $row['PHCcomp']],
                                            'UPHC'  => [$row['UPHC'],  $row['UPHCcomp']],
                                            'APHC'  => [$row['APHC'],  $row['APHCcomp']],
                                            'AAMSC' => [$row['AAMSC'], $row['AAMSCcomp']]
                                        ];

                                        foreach ($facilities as $label => [$total, $comp]) {

                                            $total = (int)$total;
                                            $comp  = (int)$comp;

                                            if ($total === 0) {
                                                $class   = "fac-card fac-na";
                                                $value   = "N/A";
                                                $percent = "—";
                                                $pcolor  = "";
                                            } else {
                                                $pct = round(($comp / $total) * 100, 1);

                                                if ($comp === 0) {
                                                    $class = "fac-card fac-danger";
                                                    $pcolor = "text-danger";
                                                } elseif ($comp === $total) {
                                                    $class = "fac-card fac-success";
                                                    $pcolor = "text-success";
                                                } else {
                                                    $class = "fac-card fac-warning";
                                                    $pcolor = "text-warning";
                                                }

                                                $value   = "$comp / $total";
                                                $percent = $pct . "%";
                                            }
                                    ?>

                                            <div class="<?= $class ?>">
                                                <div class="fac-value"><?= $value ?></div>

                                                <div class="fac-percent <?= $pcolor ?>">
                                                    <?= $percent ?>
                                                </div>

                                                <div class="fac-label"><?= $label ?></div>
                                            </div>

                                    <?php
                                        }
                                    }

                                    mysqli_free_result($q22);
                                    $con->next_result();
                                    ?>

                                </div>


                                <?php
                                $green_zone  = [];
                                $yellow_zone = [];
                                $red_zone    = [];

                                $call_count = "
    SELECT sdv.*
    FROM state_dash_view sdv
    JOIN (
        SELECT fac_id, MAX(ass_period_id) AS latest_ass_period
        FROM state_dash_view
        GROUP BY fac_id
    ) latest
    ON sdv.fac_id = latest.fac_id
    AND sdv.ass_period_id = latest.latest_ass_period
    WHERE sdv.p <> 0
    AND sdv.fac_id NOT IN (1)
    AND sdv.obt = sdv.tot
";

                                $count = mysqli_query($con, $call_count);

                                while ($row = mysqli_fetch_assoc($count)) {

                                    $p = floatval($row['p1']);

                                    $entry = [
                                        'district' => $row['Dist_Name'],
                                        'block'    => $row['Block_Name'],
                                        'name'     => $row['fac_name'],
                                        'type'     => $row['facilities_type'],
                                        'score'    => $p
                                    ];

                                    if ($p > 80) {
                                        $green_zone[] = $entry;
                                    } elseif ($p > 50 && $p <= 80) {
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

                                // $top90_100_percent = $total_facilities > 0 ? round(($top90_100 / $total_facilities) * 100, 1) : 0;
                                // $low_lt40_percent = $total_facilities > 0 ? round(($low_lt40 / $total_facilities) * 100, 1) : 0;
                                ?>
                                <div class="col-sm-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                District-wise status of facility registration and assessment progress in SaQshi (Started / Completed)
                                            </h6>

                                            <div class="table-responsive">
                                                <table id="districtStatusTable" class="table table-sm table-bordered table-hover">

                                                    <thead style="background-color: #add8e6;"> <!-- Light blue heading background -->
                                                        <tr>
                                                            <th>District</th>
                                                            <th>DH</th>
                                                            <th>SDH</th>
                                                            <th>APHC</th>
                                                            <th>CHC</th>
                                                            <th>PHC</th>
                                                            <th>UPHC</th>
                                                            <th>AAMSC</th>

                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $call_q1 = "CALL state_dash_count_dist1(1)";
                                                        $q22 = mysqli_query($con, $call_q1);

                                                        while ($row = mysqli_fetch_array($q22)) {
                                                            $categories = [
                                                               'DH' => ['total' => $row['DH'], 'completed' => $row['DHcomp']],
                                                                'SH' => ['total' => $row['SH'], 'completed' => $row['SHCcomp']],
                                                                'APHC' => ['total' => $row['APHC'], 'completed' => $row['APHCcomp']],
                                                                'CHC' => ['total' => $row['CHC'], 'completed' => $row['CHCcomp']],
                                                                'PHC' => ['total' => $row['PHC'], 'completed' => $row['PHCcomp']],
                                                                'UPHC' => ['total' => $row['UPHC'], 'completed' => $row['UPHCcomp']],
                                                                'AAMSC' => ['total' => $row['AAMSC'], 'completed' => $row['AAMSCcomp']],
                                                            ];

                                                            echo "<tr>";
                                                            // District name in bold blue
                                                            echo "<td style='font-weight:bold; color: #003366;'>" . $row['Dist_Name'] . "</td>";


                                                            foreach ($categories as $label => $data) {
                                                                $completed = intval($data['completed']);
                                                                $total = intval($data['total']);
                                                                $percentage = ($total > 0) ? round(($completed / $total) * 100, 1) : 0;

                                                                // Bootstrap contextual color
                                                                if ($percentage >= 70) {
                                                                    $colorClass = 'bg-success text-white';
                                                                } elseif ($percentage >= 50) {
                                                                    $colorClass = 'bg-warning text-dark';
                                                                } else {
                                                                    $colorClass = 'bg-danger text-white';
                                                                }

                                                                echo "<td class='$colorClass' style='padding: 4px;'>";

                                                                if ($total > 0) {
                                                                    // Show progress bar + Completed/Total + %
                                                                    echo "<div style='font-size: 12px; text-align:center; margin-bottom: 2px;'>" . $completed . "/" . $total . " (" . $percentage . "%)</div>";
                                                                    echo "
                                        <div class='progress' style='height: 6px;'>
                                            <div class='progress-bar' role='progressbar' style='width: {$percentage}%;' aria-valuenow='{$percentage}' aria-valuemin='0' aria-valuemax='100'></div>
                                        </div>
                                    ";
                                                                } else {
                                                                    echo "N/A";
                                                                }

                                                                echo "</td>";
                                                            }

                                                            echo "</tr>";
                                                        }

                                                        mysqli_free_result($q22);
                                                        $con->next_result();
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p>
                                    A total of <strong><?= $total_facilities ?></strong> assessments have been conducted in the District.
                                    Out of these, <strong><?= $gt80 ?> assessments (<?= $gt80_percent ?>%)</strong> achieved a compliance score of more than <strong>80%</strong>, indicating high performance.
                                    Additionally, <strong><?= $btw50_80 ?> assessments (<?= $btw50_80_percent ?>%)</strong> scored between <strong>50%</strong> and <strong>80%</strong>.
                                    However, <strong><?= $lt50 ?> assessments (<?= $lt50_percent ?>%)</strong> scored below <strong>50%</strong> and require focused improvement.

                                </p>

                                <p>
                                    Certification coverage includes:
                                </p>
                                <ul style="margin-bottom: 15px;">
                                    <li>State Certified Facilities: <strong><span id="state-cert-count">...</span></strong></li>
                                    <li>National Certified Facilities: <strong><span id="national-cert-count">...</span></strong></li>
                                </ul>

                                <p>
                                    District-wise performance charts reflect that some districts consistently perform above 80%, while others show a concentration of low-scoring facilities.
                                    These patterns should guide future quality improvement and support.
                                </p>
                                <h5 class="mt-3" style="font-size: 16px; color: green;">Green Zone - Facilities with > 80%</h5>
                                <div class="table-responsive">
                                    <table id="greenZoneTable" class="table table-bordered table-striped table-hover table-sm">
                                        <thead class="table-success">
                                            <tr>
                                                <th>Sl. No.</th>
                                                <th>Dist. Name</th>
                                                <th>Block Name</th>
                                                <th>Facility Name</th>
                                                <th>Facility Type</th>
                                                <th>Score %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $slno = 1;
                                            foreach ($green_zone as $row): ?>
                                                <tr>
                                                    <td><?= $slno++ ?></td>
                                                    <td><?= htmlspecialchars($row['district']) ?></td>
                                                    <td><?= htmlspecialchars($row['block']) ?></td>
                                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                                    <td><?= htmlspecialchars($row['type']) ?></td>
                                                    <td><?= $row['score'] ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>


                                <h5 class="mt-3" style="font-size: 16px; color: orange;">Yellow Zone - Facilities with 50% to <80% </h5>
                                        <div class="table-responsive">
                                            <table id="yellowZoneTable" class="table table-bordered table-striped table-hover table-sm">
                                                <thead class="table-warning">
                                                    <tr>
                                                        <th>Sl. No.</th>
                                                        <th>Dist. Name</th>
                                                        <th>Block Name</th>
                                                        <th>Facility Name</th>
                                                        <th>Facility Type</th>
                                                        <th>Score %</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $slno = 1;
                                                    foreach ($yellow_zone as $row): ?>
                                                        <tr>
                                                            <td><?= $slno++ ?></td>
                                                            <td><?= htmlspecialchars($row['district']) ?></td>
                                                            <td><?= htmlspecialchars($row['block']) ?></td>
                                                            <td><?= htmlspecialchars($row['name']) ?></td>
                                                            <td><?= htmlspecialchars($row['type']) ?></td>
                                                            <td><?= $row['score'] ?>%</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>


                                        <h5 class="mt-3" style="font-size: 16px; color: red;">Red Zone - Facilities with &lt; 50%</h5>
                                        <div class="table-responsive">
                                            <table id="redZoneTable" class="table table-bordered table-striped table-hover table-sm">
                                                <thead class="table-danger">
                                                    <tr>
                                                        <th>Sl. No.</th>
                                                        <th>Dist. Name</th>
                                                        <th>Block Name</th>
                                                        <th>Facility Name</th>
                                                        <th>Facility Type</th>
                                                        <th>Score %</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $slno = 1;
                                                    foreach ($red_zone as $row): ?>
                                                        <tr>
                                                            <td><?= $slno++ ?></td>
                                                            <td><?= htmlspecialchars($row['district']) ?></td>
                                                            <td><?= htmlspecialchars($row['block']) ?></td>
                                                            <td><?= htmlspecialchars($row['name']) ?></td>
                                                            <td><?= htmlspecialchars($row['type']) ?></td>
                                                            <td><?= $row['score'] ?>%</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>



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

            <!-- Place this BEFORE your <script> that uses html2canvas -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

            <script>
                async function downloadDashboardAsPDF() {
                    const {
                        jsPDF
                    } = window.jspdf;
                    const dashboard = document.getElementById('report-content');

                    window.scrollTo(0, 0);

                    html2canvas(dashboard, {
                        scale: 1,
                        useCORS: true,
                        ignoreElements: el => el.classList.contains('exclude-from-pdf')
                    }).then(canvas => {
                        const imgData = canvas.toDataURL('image/jpeg', 0.7);
                        const pdf = new jsPDF('p', 'mm', 'a4');
                        const pdfWidth = pdf.internal.pageSize.getWidth();
                        const pdfHeight = pdf.internal.pageSize.getHeight();

                        const imgWidth = pdfWidth;
                        const imgHeight = (canvas.height * pdfWidth) / canvas.width;

                        let heightLeft = imgHeight;
                        let position = 0;

                        // Add first page
                        pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
                        heightLeft -= pdfHeight;

                        // Add remaining pages if needed
                        while (heightLeft > 0) {
                            position = heightLeft - imgHeight;
                            pdf.addPage();
                            pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
                            heightLeft -= pdfHeight;
                        }

                        pdf.save("SaQshi_State_Report.pdf");
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
                                <a href="assets/export/export_state_score_card.php">
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
WHERE fac_id NOT IN (13061);";
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



                <button class="btn btn-success mb-3" onclick="window.location.href='assets/get/fetch_phc_kpi_statedash_data.php'">
                    Download KPI & Outcome Summary (Excel)
                </button>
                <button class="btn btn-success mb-3" onclick="window.location.href='assets/get/fetch_data_actionplan.php'">
                    DownloadAction Plan Completed/pending summary (Excel)
                </button>



                <!-- Include DataTables JS and CSS -->


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
                </script>
                <script>
                    $(document).ready(function() {
                        ['greenZoneTable', 'yellowZoneTable', 'redZoneTable', 'districtStatusTable'].forEach(function(id) {
                            if ($.fn.DataTable.isDataTable('#' + id)) {
                                $('#' + id).DataTable().destroy();
                            }
                            $('#' + id).DataTable({
                                dom: 'Bfrtip',
                                pageLength: 5,
                                buttons: [{
                                    extend: 'excelHtml5',
                                    title: id + ' Full Export',
                                    text: '📥 Export Excel',
                                    exportOptions: {
                                        modifier: {
                                            page: 'all' // Export all rows
                                        }
                                    }
                                }]
                            });
                        });
                    });
                </script>
                <?php include("assets/head/f.php"); ?>