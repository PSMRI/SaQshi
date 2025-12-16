<?php
include("assets/head/h.php");
ini_set('max_execution_time', 300); // 300 seconds = 5 minutes
?>
<div class="pcoded-main-container">
    <div class="pcoded-content">
        <div class="pagetitle mb-2">
            <h5 class="fw-bold text-primary mb-1">Division Dashboard</h5>
        </div>
        <!-- Leaflet CSS and JS (should be in <head> ideally, but here for simplicity) -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
                var map = L.map('map').setView([25.32, 82.99], 9);
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
                $.getJSON('assets/get/get_cert_data_div.php', function(facilities) {

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
                            title: 'State Certification Overview',
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
            #facTable tbody tr.expired-row {
                background-color: #ffe5e5 !important;
                color: #b30000 !important;
                font-weight: 500;
            }
        </style>
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
                $divid = $_SESSION['div_id'];
                $call_count = "SELECT Dist_Name,p1 FROM state_dash_view WHERE p <> 0 and division_id=$divid";
                $count = mysqli_query($con, $call_count);

                // Initialize counters
                $gt80 = 0;
                $btw50_80 = 0;
                $lt50 = 0;
                $total_facilities_assessments = 0;
                $total_p_sum = 0;
                $top90_100 = 0; // >=90
                $low_lt40 = 0;  // <40

                while ($row = mysqli_fetch_assoc($count)) {
                    $p = floatval($row['p1']);
                    $total_facilities_assessments++;
                    $total_p_sum += $p;

                    if ($p > 80) {
                        $gt80++;
                    } elseif ($p >= 50 && $p <= 80) {
                        $btw50_80++;
                    } elseif ($p < 50) {
                        $lt50++;
                    }

                    //  if ($p >= 90 && $p <= 100) {
                    //      $top90_100++;
                    //  }
                    // if ($p < 40) {
                    //     $low_lt40++;
                    // }
                }

                // Calculate Average %
                $avg_p = $total_facilities_assessments > 0 ? round($total_p_sum / $total_facilities_assessments, 2) : 0;

                // Prepare cards
                $cards = [
                    [
                        'icon' => 'bi bi-award-fill',
                        'comp' => $gt80,
                        'total' => $total_facilities_assessments,
                        'label' => '>80%',
                        'colorClass' => 'bg-success text-white'
                    ],
                    [
                        'icon' => 'bi bi-bar-chart-line-fill',
                        'comp' => $btw50_80,
                        'total' => $total_facilities_assessments,
                        'label' => '50%-80%',
                        'colorClass' => 'bg-warning text-dark'
                    ],
                    [
                        'icon' => 'bi bi-exclamation-circle-fill',
                        'comp' => $lt50,
                        'total' => $total_facilities_assessments,
                        'label' => '<50%',
                        'colorClass' => 'bg-danger text-white'
                    ]
                ];
                ?>

                <!-- Cards in Single Line -->
                <div class="d-flex justify-content-between align-items-stretch text-center" style="flex-wrap: wrap;">
                    <?php
                    foreach ($cards as $data) {
                        echo "
          <div class='card {$data['colorClass']} shadow-sm border-0 flex-fill mx-1' 
               style='border-radius:10px; min-width:150px;'>
              <div class='card-body p-2'>
                  <i class='{$data['icon']} mb-1' style='font-size: 1.6rem;'></i>
                  <h5 class='fw-bold mb-0'>{$data['comp']} / {$data['total']}</h5>
                  <div class='fw-semibold' style='font-size: 13px;'>{$data['label']}</div>
              </div>
                </div>";
                    }
                    ?>
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
                        <h6 class="card-title">
                            District-Level Assessment Summary
                            <a href="export_division_dist_score_card.php">
                                <i class="bi bi-arrow-down-circle-fill"></i>
                            </a>
                        </h6>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover">
                                <thead style="background-color: #add8e6;"> <!-- Light blue heading background -->
                                    <tr>
                                        <th>District</th>
                                        <th>DH</th>
                                        <th>SSH</th>
                                        <th>CHC</th>
                                        <th>PHC</th>
                                        <th>UPHC</th>
                                        <th>AMMSC</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $dist_id = $_SESSION['div_id'];
                                    $call_q1 = "CALL Division_dash_count_dist($dist_id)";
                                    $q22 = mysqli_query($con, $call_q1);

                                    while ($row = mysqli_fetch_array($q22)) {
                                        $categories = [
                                            'DH' => ['total' => $row['DH'], 'completed' => $row['DHcomp']],
                                            'SH' => ['total' => $row['SH'], 'completed' => $row['SHCcomp']],
                                            'CHC' => ['total' => $row['CHC'], 'completed' => $row['CHCcomp']],
                                            'PHC' => ['total' => $row['PHC'], 'completed' => $row['PHCcomp']],
                                            'APHC' => ['total' => $row['UPHC'], 'completed' => $row['UPHCcomp']],
                                            'AAMSC' => ['total' => $row['AAMSC'], 'completed' => $row['AAMSCcomp']]
                                        ];

                                        echo "<tr>";
                                        // District name in bold blue
                                        echo "<td style='font-weight:bold; color: #003366;'>" . htmlspecialchars($row['Dist_Name']) . "</td>";

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
        </div>


        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            Assessment Summary by Facility
                            <a href="assets/export/export_dist_score_card.php">
                                <i class="bi bi-arrow-down-circle-fill"></i>
                            </a>
                        </h6>

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
                                        <th><i class="bi bi-arrow-down-circle-fill"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $call_count = "SELECT * FROM state_dash_view where fac_id in (select fac_id from facilities where division_id=  $divid )";
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
                                                <td class='table-info'><a href='pdist_details.php?id={$row['fac_id']}'>{$row['non']}</a></td>
                                                <td class='{$marksClass}'>{$row['marks']}</td>
                                                <td class='{$marksClass}'>{$row['f']}</td>
                                                <td class='{$p1Class}'>{$row['p1']}%</td>
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
        </div>


        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">

                        <!-- ================= REPORT CONTENT ================= -->
                        <div id="report-content" style="
                                font-family: Arial, sans-serif;
                                font-size: 14px;
                                line-height: 1.6;
                                padding: 10px;
                                background: #fff;
                                color: #333;
                            ">
                            <style>
                                /* Container */
                                .facility-strip {
    padding-bottom: 4px;
}

.facility-card {
    min-width: 130px;
    height: 92px;
    margin-right: 22px;   /* spacing between cards */
}

/* 🔑 Remove margin after last card */
.facility-card:last-child {
    margin-right: 0;
}


                                /* Card base */
                                .facility-card {
                                    min-width: 130px;
                                    height: 92px;
                                    border-radius: 10px;
                                    padding: 10px;
                                    text-align: center;
                                    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
                                    display: flex;
                                    flex-direction: column;
                                    justify-content: center;
                                    background: #f8f9fa;
                                }

                                /* Main value */
                                .facility-value {
                                    font-size: 18px;
                                    font-weight: 700;
                                    line-height: 1.1;
                                }

                                /* Percentage */
                                .facility-percent {
                                    font-size: 12px;
                                    font-weight: 600;
                                    margin-top: 2px;
                                }

                                /* Label */
                                .facility-label {
                                    font-size: 12px;
                                    margin-top: 6px;
                                    font-weight: 600;
                                    letter-spacing: 0.3px;
                                }

                                /* States */
                                .facility-card.good {
                                    border-top: 4px solid #198754;
                                    color: #198754;
                                }

                                .facility-card.avg {
                                    border-top: 4px solid #ffc107;
                                    color: #8a6d00;
                                }

                                .facility-card.poor {
                                    border-top: 4px solid #dc3545;
                                    color: #dc3545;
                                }

                                .facility-card.na {
                                    border-top: 4px solid #6c757d;
                                    color: #6c757d;
                                    background: #f1f3f5;
                                }

                                .facility-percent.muted {
                                    color: #999;
                                }
                            </style>
                            <center>
                                <h4 style="color:#0056b3;margin-bottom:5px;">Division Quality Compliance Summary – Report</h4>
                                <h5>Division: <strong><?= htmlspecialchars($_SESSION['div_name']) ?></strong></h5>
                            </center>

                            <?php
                            $divid = $_SESSION['div_id'];

                            /* =======================
                                FACILITY TYPE SUMMARY
                                ======================= */
                            $res = mysqli_query($con, "CALL Division_dash_count($divid)");
                            $row = mysqli_fetch_assoc($res);
                            mysqli_free_result($res);
                            $con->next_result();

                            $facilityTypes = [
                                'DH'    => [$row['DH'],    $row['DHCcomp']],
                                'SH'    => [$row['SH'],    $row['SHCcomp']],
                                'CHC'   => [$row['CHC'],   $row['CHCcomp']],
                                'PHC'   => [$row['PHC'],   $row['PHCcomp']],
                                'UPHC'  => [$row['UPHC'],  $row['UPHCcomp']],
                                'AAMSC' => [$row['AAMSC'], $row['AAMSCcomp']]
                            ];
                            ?>

                            <div class="d-flex flex-nowrap overflow-auto mb-3 facility-strip">


                                <?php foreach ($facilityTypes as $label => [$total, $comp]):

                                    if ($total == 0) {
                                        $statusClass = 'na';
                                        $display = 'N/A';
                                        $percent = null;
                                    } else {
                                        $percent = round(($comp / $total) * 100, 1);
                                        $display = "$comp / $total";

                                        if ($percent >= 70)      $statusClass = 'good';
                                        elseif ($percent >= 40)  $statusClass = 'avg';
                                        else                     $statusClass = 'poor';
                                    }
                                ?>

                                    <div class="facility-card <?= $statusClass ?>">
                                        <div class="facility-value"><?= $display ?></div>

                                        <?php if ($percent !== null): ?>
                                            <div class="facility-percent"><?= $percent ?>%</div>
                                        <?php else: ?>
                                            <div class="facility-percent muted">—</div>
                                        <?php endif; ?>

                                        <div class="facility-label"><?= htmlspecialchars($label) ?></div>
                                    </div>

                                <?php endforeach; ?>

                            </div>

                            <?php
                            /* =======================
                            SCORE ZONE CLASSIFICATION
                            ======================= */
                            $sql = "
                                SELECT Dist_Name, Block_Name, fac_name, facilities_type, p1
                                FROM state_dash_view
                                WHERE fac_id IN (
                                    SELECT fac_id FROM facilities WHERE division_id=$divid
                                )";
                            $res = mysqli_query($con, $sql);

                            $zones = ['green' => [], 'yellow' => [], 'red' => []];
                            $gt80 = $btw50_80 = $lt50 = 0;

                            while ($r = mysqli_fetch_assoc($res)) {
                                $p = floatval($r['p1']);
                                $entry = [
                                    'district' => $r['Dist_Name'],
                                    'block' => $r['Block_Name'],
                                    'name' => $r['fac_name'],
                                    'type' => $r['facilities_type'],
                                    'score' => $p
                                ];

                                if ($p > 80) {
                                    $zones['green'][] = $entry;
                                    $gt80++;
                                } elseif ($p >= 50) {
                                    $zones['yellow'][] = $entry;
                                    $btw50_80++;
                                } else {
                                    $zones['red'][] = $entry;
                                    $lt50++;
                                }
                            }
                            mysqli_free_result($res);

                            $total = $gt80 + $btw50_80 + $lt50;
                            ?>

                            <?php
                            // -----------------------------
                            // Percentage Calculations
                            // -----------------------------
                            $total_facilities = $gt80 + $btw50_80 + $lt50;

                            $gt80_percent     = $total_facilities ? round(($gt80 / $total_facilities) * 100, 1) : 0;
                            $btw50_80_percent = $total_facilities ? round(($btw50_80 / $total_facilities) * 100, 1) : 0;
                            $lt50_percent     = $total_facilities ? round(($lt50 / $total_facilities) * 100, 1) : 0;

                            /* Optional advanced indicators
   (keep if already calculated elsewhere) */
                            $top90_100_percent = $total_facilities && isset($top90_100)
                                ? round(($top90_100 / $total_facilities) * 100, 1)
                                : 0;

                            $low_lt40_percent = $total_facilities && isset($low_lt40)
                                ? round(($low_lt40 / $total_facilities) * 100, 1)
                                : 0;
                            ?>

                            <p>
                                In this Division, a total no of
                                <strong><?= $total_facilities ?> facilities</strong> are aristered.
                                Out of these,
                                <strong><?= $gt80 ?> facilities (<?= $gt80_percent ?>%)</strong>
                                achieved a compliance score greater than <strong>80%</strong>,
                                reflecting high performance.
                                An additional
                                <strong><?= $btw50_80 ?> facilities (<?= $btw50_80_percent ?>%)</strong>
                                scored between <strong>50% and 80%</strong>.
                                However,
                                <strong><?= $lt50 ?> facilities (<?= $lt50_percent ?>%)</strong>
                                are below the <strong>50%</strong> compliance threshold and need focused improvement.
                            </p>

                            <p>
                                Certification coverage includes:
                            </p>
                            <ul style="margin-bottom:15px;">
                                <li>
                                    State Certified Facilities:
                                    <strong><span id="state-cert-count">…</span></strong>
                                </li>
                                <li>
                                    National Certified Facilities:
                                    <strong><span id="national-cert-count">…</span></strong>
                                </li>
                            </ul>

                            <p>
                                The Facility Score Distribution indicates
                                <strong><?= $top90_100_percent ?>%</strong> of facilities
                                have scored above <strong>90%</strong>.
                                However,
                                <strong><?= $low_lt40_percent ?>%</strong> of facilities
                                are below <strong>40%</strong>, signaling urgent attention.
                            </p>

                            <p>
                                District-wise performance charts reflect that some districts
                                consistently perform above 80%, while others show a concentration
                                of low-scoring facilities. These patterns should guide future
                                quality improvement and targeted support.
                            </p>

                            <?php
                            /* =======================
                                ZONE TABLE RENDERER
                                ======================= */
                            function zoneTable($title, $color, $data)
                            {
                                echo "<h5 style='color:$color;margin-top:20px;'>$title</h5>";
                                echo "<table class='table table-sm table-bordered'>
    <tr><th>#</th><th>District</th><th>Block</th><th>Facility</th><th>Type</th><th>%</th></tr>";
                                $i = 1;
                                foreach ($data as $r) {
                                    echo "<tr>
        <td>$i</td>
        <td>{$r['district']}</td>
        <td>{$r['block']}</td>
        <td>{$r['name']}</td>
        <td>{$r['type']}</td>
        <td>{$r['score']}%</td>
        </tr>";
                                    $i++;
                                }
                                echo "</table>";
                            }

                            zoneTable("Green Zone (>80%)", "green", $zones['green']);
                            zoneTable("Yellow Zone (50–80%)", "orange", $zones['yellow']);
                            zoneTable("Red Zone (<50%)", "red", $zones['red']);
                            ?>

                            <p class="text-muted mt-3">
                                <em>Report generated on <?= date('d M Y h:i A') ?> by <strong>SaQshi</strong></em>
                            </p>




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
                        scale: 1,
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

                        pdf.save("SaQshi_Division_Report.pdf");
                    }).catch(err => {
                        console.error("PDF generation error:", err);
                    });
                }
            </script>



        </div>
    </div>

    <!-- Include DataTables JS and CSS -->

    <?php include("assets/head/f.php"); ?>
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