<?php
include("assets/head/h.php");
ini_set('max_execution_time', 300); // 300 seconds = 5 minutes
?>

<div id="dashboard-content">
    <div class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="pagetitle mb-2">
                <h5 class="fw-bold text-primary mb-1">Division Dashboard</h5>
            </div>
            <!-- Leaflet CSS and JS (should be in <head> ideally, but here for simplicity) -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <h5>Facility Certification Overview</h5>
                            <!-- Map container MUST have height -->
                            <div id="map" style="width: 100%; height: 400px; border: 1px solid #ccc;"></div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <h5>Facility Certification details Overview</h5>

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
                                            <th>Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- JS Script -->
            <script>
                var map = L.map('map').setView([25.2, 85.5], 8);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                }).addTo(map);

                function getMarkerIcon(certType) {
                    var iconUrl;

                    if (certType.toLowerCase() === 'state') {
                        iconUrl = 'https://maps.gstatic.com/mapfiles/ms2/micons/blue.png';
                    } else if (certType.toLowerCase() === 'national') {
                        iconUrl = 'https://maps.gstatic.com/mapfiles/ms2/micons/green.png';
                    } else {
                        iconUrl = 'https://maps.gstatic.com/mapfiles/ms2/micons/red.png';
                    }

                    return new L.Icon({
                        iconUrl: iconUrl,
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                        shadowSize: [41, 41]
                    });
                }

                // Load data via AJAX - ONLY ONCE
                $.getJSON('assets/get/get_cert_data_div.php', function(facilities) {

                    facilities.forEach(function(facility) {
                        let districtName = facilities.length > 0 ? facilities[0].dist : 'Unknown District';
                        $('#district-name-span').text(districtName);
                        // Add marker on map
                        var icon = getMarkerIcon(facility.cert_type);

                        var marker = L.marker([facility.lat, facility.longi], {
                            icon: icon
                        }).addTo(map);

                        var tooltipContent = "<b>" + facility.fac_name + "</b><br>" +
                            "Certification Type: " + facility.cert_type + "<br>" +
                            "Details: " + facility.cert_detailscol;

                        marker.bindTooltip(tooltipContent, {
                            permanent: false,
                            direction: 'top',
                            offset: [0, -10]
                        });

                        // Add row to table
                        var row = '<tr>' +
                            '<td>' + facility.fac_name + '</td>' +
                            '<td>' + facility.fac_type + '</td>' +
                            '<td>' + facility.cert_type + '</td>' +
                            '<td>' + facility.cert_detailscol + '</td>' +
                            '<td>' + facility.cert_issue + '</td>' +
                            '<td>' + facility.validity + '</td>' +
                            '<td>' + (facility.score !== null ? facility.score : 'N/A') + '</td>' +
                            '</tr>';

                        $('#facTable tbody').append(row);

                    });

                    // Initialize DataTable after all rows are added
                    $('#facTable').DataTable();
                    let stateCount = 0;
                    let nationalCount = 0;

                    facilities.forEach(function(facility) {
                        const type = facility.cert_type?.toLowerCase();
                        if (type === 'state') stateCount++;
                        else if (type === 'national') nationalCount++;
                    });

                    $('#state-cert-count').text(stateCount);
                    $('#national-cert-count').text(nationalCount);

                });
            </script>


            <div class="row">
                <div class="d-flex flex-nowrap overflow-auto">
                    <?php
                    $divid = $_SESSION['div_id'];
                    $call_q1 = "CALL Division_dash_count($divid)";
                    $q22 = mysqli_query($con, $call_q1);

                    while ($row = mysqli_fetch_array($q22)) {
                        $facilities = [
                            'DH' => ['total' => $row['DH'], 'comp' => $row['DHCcomp'], 'icon' => 'bi bi-hospital'],
                            'SDH' => ['total' => $row['SDH'], 'comp' => $row['SDHCcomp'], 'icon' => 'bi bi-hospital'],
                            'APHC' => ['total' => $row['APHC'], 'comp' => $row['APHCcomp'], 'icon' => 'bi bi-hospital'],
                            'CHC' => ['total' => $row['CHC'], 'comp' => $row['CHCcomp'], 'icon' => 'bi bi-hospital'],
                            'PHC' => ['total' => $row['PHC'], 'comp' => $row['PHCcomp'], 'icon' => 'bi bi-hospital'],
                            'UPHC' => ['total' => $row['UPHC'], 'comp' => $row['UPHCcomp'], 'icon' => 'bi bi-hospital'],
                            'HWC' => ['total' => $row['HWC'], 'comp' => $row['HWCcomp'], 'icon' => 'bi bi-hospital']
                        ];

                        foreach ($facilities as $label => $data) {
                            $comp = intval($data['comp']);
                            $total = intval($data['total']);

                            if ($total == 0) {
                                $bgColor = 'bg-secondary';
                                $displayValue = 'N/A';
                            } elseif ($comp == $total) {
                                $bgColor = 'bg-success';
                                $displayValue = "$comp/$total";
                            } elseif ($comp > 0) {
                                $bgColor = 'bg-warning';
                                $displayValue = "$comp/$total";
                            } else {
                                $bgColor = 'bg-danger';
                                $displayValue = "$comp/$total";
                            }

                            echo "<div class='card flat-card widget-primary-card $bgColor text-white m-2' style='min-width: 125px;' title='Facility Type: $label | Completed: $comp / Total: $total'>
                <div class='row-table'>
                    <div class='col-sm-3 card-body d-flex align-items-center justify-content-between p-2'>
                        <i class='{$data['icon']} text-white' style='font-size: 24px;'></i>
                    </div>
                    <div class='col-sm-9 py-3'>
                        <h4 class='fw-bold mb-1'>$displayValue</h4>
                        <h5 class='mb-0'>$label</h5>
                    </div>
                </div>
            </div>";
                        }
                    }

                    mysqli_free_result($q22);
                    $con->next_result();
                    ?>
                </div>

            </div>



            <?php
            $divid = $_SESSION['div_id'];
            $call_count = "SELECT * FROM state_dash_view where fac_id in (select fac_id from facilities where division_id= $divid)";
            $count = mysqli_query($con, $call_count);

            // Initialize counters
            $gt80 = 0;
            $btw50_80 = 0;
            $lt50 = 0;
            $total_facilities = 0;
            $total_p_sum = 0;
            $total_pending_action = 0; // sum of 'non' column
            $top90_100 = 0; // >=90
            $low_lt40 = 0; // <40

            while ($row = mysqli_fetch_assoc($count)) {
                $p = floatval($row['p']);

                $total_facilities++;
                $total_p_sum += $p;

                if ($p > 80) {
                    $gt80++;
                } elseif ($p >= 50 && $p <= 80) {
                    $btw50_80++;
                } elseif ($p < 50) {
                    $lt50++;
                }

                // Extra indicators
                if ($p >= 90 && $p <= 100) {
                    $top90_100++;
                }
                if ($p < 40) {
                    $low_lt40++;
                }
            }

            // Calculate Average %
            $avg_p = $total_facilities > 0 ? round($total_p_sum / $total_facilities, 2) : 0;

            // Prepare cards
            $cards = [
                [
                    'icon' => 'bi bi-award-fill',
                    'comp' => $gt80,
                    'total' => $total_facilities,
                    'label' => '>80%',
                    'colorClass' => 'text-white'
                ],
                [
                    'icon' => 'bi bi-bar-chart-line-fill',
                    'comp' => $btw50_80,
                    'total' => $total_facilities,
                    'label' => '50%-80%',
                    'colorClass' => 'text-warning'
                ],
                [
                    'icon' => 'bi bi-exclamation-circle-fill',
                    'comp' => $lt50,
                    'total' => $total_facilities,
                    'label' => '<50%',
                    'colorClass' => 'text-danger'
                ],
                [
                    'icon' => 'bi bi-star-fill',
                    'comp' => $top90_100,
                    'total' => $total_facilities,
                    'label' => '>=90%',
                    'colorClass' => 'text-white'
                ],
                [
                    'icon' => 'bi bi-emoji-frown-fill',
                    'comp' => $low_lt40,
                    'total' => $total_facilities,
                    'label' => '<40%',
                    'colorClass' => 'text-danger'
                ]
            ];

            // Render cards with flexbox for one-line display
            echo "<div class='d-flex flex-nowrap overflow-auto'>";
            foreach ($cards as $data) {
                echo "
    <div class='card flat-card widget-primary-card m-2' style='min-width: 180px;' title='Category: {$data['label']} | {$data['comp']} / {$data['total']}'>
        <div class='row-table'>
            <div class='col-sm-3 card-body d-flex align-items-center justify-content-center'>
                <i class='{$data['icon']} text-white' style='font-size: 2rem;'></i>
            </div>
            <div class='col-sm-9 py-3'>
                <h4 class='fw-bold {$data['colorClass']}'>{$data['comp']} / {$data['total']}</h4>
                <h6>{$data['label']}</h6>                        
            </div>
        </div>
    </div>
    ";
            }
            echo "</div>";
            ?>


            <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

            <?php
            $chartData = [
                '>80%' => $gt80,
                '50%-80%' => $btw50_80,
                '<50%' => $lt50,
                '>=90%' => $top90_100,
                '<40%' => $low_lt40
            ];
            ?>

            <?php
            $divid =   $_SESSION['div_id'];
            // Read block score data
            $call_block_score = "SELECT Dist_Name,p1 FROM sarbsoft_nqa.state_dash_view  where fac_id in (select fac_id from facilities where division_id= $divid)";
            $block_score_res = mysqli_query($con, $call_block_score);

            // Build Block-wise Score Category counts in PHP
            $block_score_data = [];

            while ($row = mysqli_fetch_assoc($block_score_res)) {
                $block = $row['Dist_Name'];
                $p1 = floatval($row['p1']);

                // Determine score category
                if ($p1 < 40) {
                    $cat = '<40%';
                } elseif ($p1 < 50) {
                    $cat = '40%-50%';
                } elseif ($p1 < 80) {
                    $cat = '50%-80%';
                } elseif ($p1 < 90) {
                    $cat = '80%-90%';
                } else {
                    $cat = '>=90%';
                }

                if (!isset($block_score_data[$block])) {
                    $block_score_data[$block] = [
                        '<40%' => 0,
                        '40%-50%' => 0,
                        '50%-80%' => 0,
                        '80%-90%' => 0,
                        '>=90%' => 0
                    ];
                }

                $block_score_data[$block][$cat]++;
            }
            $block_labels = array_keys($block_score_data);
            $score_categories = ['<40%', '40%-50%', '50%-80%', '80%-90%', '>=90%'];

            $series_data = [];
            foreach ($score_categories as $category) {
                $data_series = [];
                foreach ($block_labels as $block) {
                    $data_series[] = $block_score_data[$block][$category];
                }
                $series_data[] = [
                    'name' => $category,
                    'data' => $data_series
                ];
            }

            ?>


            <div class="row">
                <!-- Pie Chart Card -->
                <div class="col-lg-6 col-md-6 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Facility Score Distribution</h5>
                            <div id="pie-chart-1" style="width:100%; height: 300px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Block Stacked Bar Chart Card -->
                <div class="col-lg-6 col-md-6 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">District-wise Facility Performance</h5>
                            <div id="block-stacked-bar" style="width:100%; height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Pie Chart
                var optionsPie = {
                    series: <?php echo json_encode(array_values($chartData)); ?>,
                    chart: {
                        width: '100%',
                        type: 'pie'
                    },
                    labels: <?php echo json_encode(array_keys($chartData)); ?>,
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: '100%'
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }],
                    colors: [
                        'rgba(7, 156, 42, 0.89)', // >80% green
                        'rgba(255, 193, 7, 0.89)', // 50%-80% yellow
                        'rgba(227, 19, 40, 0.9)', // <50% red
                        'rgba(15, 116, 225, 0.93)', // >=90% blue
                        'rgba(85, 89, 92, 0.88)' // <40% grey
                    ]
                };

                var chartPie = new ApexCharts(document.querySelector("#pie-chart-1"), optionsPie);
                chartPie.render();

                // Block Stacked Bar Chart
                var optionsBar = {
                    series: <?php echo json_encode($series_data); ?>,
                    chart: {
                        type: 'bar',
                        height: 300,
                        stacked: true,
                        toolbar: {
                            show: true
                        }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            borderRadius: 2,
                            dataLabels: {
                                position: 'top'
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true
                    },
                    xaxis: {
                        categories: <?php echo json_encode($block_labels); ?>
                    },
                    colors: [
                        '#dc3545', // <40% red
                        '#ffc107', // 40%-50% yellow
                        '#28a745', // 50%-80% green
                        '#007bff', // 80%-90% blue
                        '#20c997' // >=90% teal
                    ],
                    legend: {
                        position: 'bottom'
                    },
                    fill: {
                        opacity: 1
                    }
                };

                var chartBar = new ApexCharts(document.querySelector("#block-stacked-bar"), optionsBar);
                chartBar.render();
            </script>

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
                                        Division Compliance Summary – Report
                                    </h4>
                                    <h4 style="font-size: 16px; margin-bottom: 15px; color: #333;">
                                        <?php $divname = $_SESSION['div_name']; ?>
                                        Division: <strong> <?php echo $divname; ?></strong>
                                    </h4>
                                </center>
                                <div class="d-flex flex-nowrap overflow-auto">
                                    <?php
                                    $divid = $_SESSION['div_id'];
                                    $call_q1 = "CALL Division_dash_count($divid)";
                                    $q22 = mysqli_query($con, $call_q1);

                                    while ($row = mysqli_fetch_array($q22)) {
                                        $facilities = [
                                            'DH' => ['total' => $row['DH'], 'comp' => $row['DHCcomp'], 'icon' => 'bi bi-hospital'],
                                            'SDH' => ['total' => $row['SDH'], 'comp' => $row['SDHCcomp'], 'icon' => 'bi bi-hospital'],
                                            'APHC' => ['total' => $row['APHC'], 'comp' => $row['APHCcomp'], 'icon' => 'bi bi-hospital'],
                                            'CHC' => ['total' => $row['CHC'], 'comp' => $row['CHCcomp'], 'icon' => 'bi bi-hospital'],
                                            'PHC' => ['total' => $row['PHC'], 'comp' => $row['PHCcomp'], 'icon' => 'bi bi-hospital'],
                                            'UPHC' => ['total' => $row['UPHC'], 'comp' => $row['UPHCcomp'], 'icon' => 'bi bi-hospital'],
                                            'HWC' => ['total' => $row['HWC'], 'comp' => $row['HWCcomp'], 'icon' => 'bi bi-hospital']
                                        ];

                                        foreach ($facilities as $label => $data) {
                                            $comp = intval($data['comp']);
                                            $total = intval($data['total']);

                                            if ($total == 0) {
                                                $bgColor = 'bg-secondary';
                                                $displayValue = 'N/A';
                                            } elseif ($comp == $total) {
                                                $bgColor = 'bg-success';
                                                $displayValue = "$comp/$total";
                                            } elseif ($comp > 0) {
                                                $bgColor = 'bg-warning';
                                                $displayValue = "$comp/$total";
                                            } else {
                                                $bgColor = 'bg-danger';
                                                $displayValue = "$comp/$total";
                                            }

                                            echo "<div class='card flat-card widget-primary-card $bgColor text-white m-2' style='min-width: 120px;' title='Facility Type: $label | Completed: $comp / Total: $total'>
                <div class='row-table'>
                    <div class='col-sm-3 card-body d-flex align-items-center justify-content-between p-2'>
                        <i class='{$data['icon']} text-white' style='font-size: 24px;'></i>
                    </div>
                    <div class='col-sm-9 py-3'>
                        <h4 class='fw-bold mb-1'>$displayValue</h4>
                        <h5 class='mb-0'>$label</h5>
                    </div>
                </div>
            </div>";
                                        }
                                    }

                                    mysqli_free_result($q22);
                                    $con->next_result();
                                    ?>
                                </div>
                                <?php
                                $green_zone = [];
                                $yellow_zone = [];
                                $red_zone = [];
                                $divid =   $_SESSION['div_id'];

                                $call_count = "SELECT * FROM state_dash_view where fac_id in (select fac_id from facilities where division_id=  $divid )";
                                $count = mysqli_query($con, $call_count);

                                while ($row = mysqli_fetch_assoc($count)) {
                                    $p = floatval($row['p1']);
                                    $entry = [
                                        'district' => $row['Dist_Name'],
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

                                $top90_100_percent = $total_facilities > 0 ? round(($top90_100 / $total_facilities) * 100, 1) : 0;
                                $low_lt40_percent = $total_facilities > 0 ? round(($low_lt40 / $total_facilities) * 100, 1) : 0;
                                ?>
                                <div class="col-sm-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                District-wise status of facility registration and assessment progress in SaQshi (Started / Completed)
                                            </h6>

                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered table-hover">
                                                    <thead style="background-color: #add8e6;"> <!-- Light blue heading background -->
                                                        <tr>
                                                            <th>District</th>
                                                            <th>DH</th>
                                                            <th>SDH</th>
                                                            <th>CHC</th>
                                                            <th>PHC</th>
                                                            <th>HWC</th>
                                                            <th>APHC</th>
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
                                                                'SDH' => ['total' => $row['SDH'], 'completed' => $row['SDHCcomp']],
                                                                'CHC' => ['total' => $row['CHC'], 'completed' => $row['CHCcomp']],
                                                                'PHC' => ['total' => $row['PHC'], 'completed' => $row['PHCcomp']],
                                                                'HWC' => ['total' => $row['HWC'], 'completed' => $row['HWCcomp']],
                                                                'APHC' => ['total' => $row['APHC'], 'completed' => $row['APHCcomp']],
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
                                <p>
                                    In this Division , a total of <strong><?= $total_facilities ?> facilities</strong> were assessed.
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

                                <p>
                                    The Facility Score Distribution indicates <strong><?= $top90_100_percent ?>%</strong> of facilities have scored above <strong>90%</strong>.
                                    However, <strong><?= $low_lt40_percent ?>%</strong> of facilities are below <strong>40%</strong>, signaling urgent attention.
                                </p>

                                <p>
                                    District-wise performance charts reflect that some districts consistently perform above 80%, while others show a concentration of low-scoring facilities.
                                    These patterns should guide future quality improvement and support.
                                </p>
                                <h5 style="font-size: 16px; margin-top: 20px; color: green;">Green Zone - Facilities with > 80%</h5>
                                <table style="width:100%; border-collapse: collapse; font-size: 13px;">
                                    <thead>
                                        <tr>
                                            <th style="border: 1px solid #ccc; padding: 5px;">Sl. No.</th>
                                            <th style="border: 1px solid #ccc; padding: 5px;">Dist. Name</th>
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
                                                <td style="border: 1px solid #ccc; padding: 5px;"><?= htmlspecialchars($row['district']) ?></td>
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
                                            <th style="border: 1px solid #ccc; padding: 5px;">Dist. Name</th>
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
                                                <td style="border: 1px solid #ccc; padding: 5px;"><?= htmlspecialchars($row['district']) ?></td>
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
                                                    <th style="border: 1px solid #ccc; padding: 5px;">Dist. Name</th>
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
                                                        <td style="border: 1px solid #ccc; padding: 5px;"><?= htmlspecialchars($row['district']) ?></td>
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

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">
                                District Summary Overview
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
                                            <th>SDH</th>
                                            <th>CHC</th>
                                            <th>PHC</th>
                                            <th>HWC</th>
                                            <th>APHC</th>
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
                                                'SDH' => ['total' => $row['SDH'], 'completed' => $row['SDHCcomp']],
                                                'CHC' => ['total' => $row['CHC'], 'completed' => $row['CHCcomp']],
                                                'PHC' => ['total' => $row['PHC'], 'completed' => $row['PHCcomp']],
                                                'HWC' => ['total' => $row['HWC'], 'completed' => $row['HWCcomp']],
                                                'APHC' => ['total' => $row['APHC'], 'completed' => $row['APHCcomp']],
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
                                Compliance Summary
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

        </div>
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