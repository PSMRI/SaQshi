<?php
include("assets/head/h.php");
?>

<div id="dashboard-content">
    <div class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="pagetitle mb-2">
                <h5 class="fw-bold text-primary mb-1">District Dash</h5>
            </div>
           <div class="row">
    <div class="col-sm-6">
        <div class="card-body">
            <?php
            $dist_id = $_SESSION['dist'];
            $result = mysqli_query($con, "SELECT Dist_name FROM dist_master WHERE Dist_id = $dist_id");
            $row = mysqli_fetch_assoc($result);
            $dist_name = $row['Dist_name'] ?? 'Patna';
            ?>

            <h1 class="card-title">District Map - <?php echo htmlspecialchars($dist_name); ?></h1>

            <!-- Display total certification counts -->
            <div id="district-data" style="font-size: 16px; font-weight: bold; margin-bottom: 10px;"></div>

            <!-- Map SVG -->
            <svg id="map" width="600" height="500" style="border:1px solid #ccc;"></svg>

            <!-- Scripts -->
            <script src="https://d3js.org/d3.v6.min.js"></script>
            <script src="https://d3js.org/topojson.v3.min.js"></script>

         <script>
    const targetDistrict = "<?php echo addslashes($dist_name); ?>";

    Promise.all([
        fetch("bihar.json").then(response => {
            console.log("MAP FETCH STATUS:", response.status);
            return response.json();
        }),
        fetch("lat_long_details.php").then(response => response.json())
    ])
    .then(([mapData, facilities]) => {
        const width = 600, height = 500;

        const geojson = topojson.feature(mapData, mapData.objects[Object.keys(mapData.objects)[0]]);
        console.log("GEOJSON FEATURES:", geojson.features.length);

        const svg = d3.select("#map");
        const projection = d3.geoMercator()
            .center([85.3131, 25.0961]) // Bihar center
            .scale(4500)
            .translate([width / 2, height / 2]);

        const pathGenerator = d3.geoPath().projection(projection);

        const g = svg.append("g");

        // Draw ALL districts
        g.selectAll("path")
            .data(geojson.features)
            .enter().append("path")
            .attr("d", pathGenerator)
            .attr("fill", d => d.properties.district === targetDistrict ? "orange" : "#D3D3D3")
            .attr("stroke", "black")
            .attr("stroke-width", 0.5)
            .on("mouseover", function(event, d) {
                if (d.properties.district !== targetDistrict) {
                    d3.select(this).attr("fill", "lightblue");
                }
            })
            .on("mouseout", function(event, d) {
                d3.select(this).attr("fill", d.properties.district === targetDistrict ? "orange" : "#D3D3D3");
            });

        // Add district names for all districts
        g.selectAll("text")
            .data(geojson.features)
            .enter().append("text")
            .attr("x", d => pathGenerator.centroid(d)[0])
            .attr("y", d => pathGenerator.centroid(d)[1] + 5)
            .attr("text-anchor", "middle")
            .attr("font-size", "8px")
            .attr("fill", d => d.properties.district === targetDistrict ? "red" : "blue")
            .attr("stroke", "none")
            .style("pointer-events", "none")
            .text(d => d.properties.district);

        // Now handle facilities → only for target district
        if (!Array.isArray(facilities)) {
            console.error("Invalid facilities data:", facilities);
            return;
        }

        const filteredFacilities = facilities.filter(facility =>
            facility.district === targetDistrict &&
            facility.cert_type &&
            (facility.cert_type.toLowerCase().includes("state") ||
             facility.cert_type.toLowerCase().includes("national"))
        );

        const stateCertifiedCount = filteredFacilities.filter(f => f.cert_type.toLowerCase().includes("state")).length;
        const nationalCertifiedCount = filteredFacilities.filter(f => f.cert_type.toLowerCase().includes("national")).length;

        document.getElementById("district-data").innerHTML = `
            <p><strong>Total State Certified:</strong> ${stateCertifiedCount}</p>
            <p><strong>Total National Certified:</strong> ${nationalCertifiedCount}</p>
        `;

        filteredFacilities.forEach(facility => {
            const lat = parseFloat(facility.lat);
            const lon = parseFloat(facility.longi);

            if (!isNaN(lat) && !isNaN(lon)) {
                const [x, y] = projection([lon, lat]);

                let markerColor = facility.cert_type.toLowerCase().includes("state") ? "yellow" : "green";

                g.append("circle")
                    .attr("class", "facility-marker")
                    .attr("cx", x)
                    .attr("cy", y)
                    .attr("r", 3)
                    .attr("fill", markerColor)
                    .attr("stroke", "black")
                    .attr("stroke-width", 0.5)
                    .append("title")
                    .text(`Facility: ${facility.fac_name}\nCertification: ${facility.cert_type}`);
            }
        });

        // Add Zoom & Pan
        const zoom = d3.zoom()
            .scaleExtent([1, 8])
            .on('zoom', (event) => {
                g.attr('transform', event.transform);
            });

        svg.call(zoom);
    })
    .catch(error => console.error("Error loading data:", error));
</script>

        </div>
    </div>
</div>




        <div class="row">

            <?php
            $dist_id = $_SESSION['dist'];
            $call_q1 = "CALL Dist_dash_count($dist_id)";
            $q22 = mysqli_query($con, $call_q1);
            while ($row = mysqli_fetch_array($q22)) {
                $facilities = [
                    'DH' => ['total' => $row['DH'], 'comp' => $row['DHCcomp'], 'icon' => 'bi bi-hospital'],
                    'SDH' => ['total' => $row['SDH'], 'comp' => $row['SDHCcomp'], 'icon' => 'bi bi-hospital'],
                    'CHC' => ['total' => $row['CHC'], 'comp' => $row['CHCcomp'], 'icon' => 'bi bi-hospital'],
                    'PHC' => ['total' => $row['PHC'], 'comp' => $row['PHCcomp'], 'icon' => 'bi bi-hospital'],
                    'UPHC' => ['total' => $row['UPHC'], 'comp' => $row['UPHCcomp'], 'icon' => 'bi bi-hospital'],
                    'HWC' => ['total' => $row['HWC'], 'comp' => $row['HWCcomp'], 'icon' => 'bi bi-hospital']
                ];
                foreach ($facilities as $label => $data) {
                    $colorClass = ($data['comp'] > 0) ? 'text-white' : 'text-danger';
                    echo "<div class='col-lg-2 col-md-3 col-sm-4 col-6 mb-3'>
            <div class='card flat-card widget-primary-card'>
                <div class='row-table'>
                    <div class='col-sm-3 card-body d-flex align-items-center justify-content-center'>
                        <i class='{$data['icon']} text-white'></i>
                    </div>
                    <div class='col-sm-9 py-3'>
                        <h4 class='fw-bold $colorClass'>{$data['comp']}/{$data['total']}</h4>
                        <h6>$label</h6>                        
                    </div>
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
        $dist_id = $_SESSION['dist'];
        $userid = $_SESSION['userid'];
        $call_count = "SELECT * FROM dist_dash_view WHERE Dist_id=$dist_id";
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

        // Render cards
        echo "<div class='row'>";
        foreach ($cards as $data) {
            echo "
    <div class='col-sm-2 col-md-3 col-sm-2 col-6 mb-3'>
        <div class='card flat-card widget-primary-card'>
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
        // Read block score data
        $call_block_score = "SELECT Block_Name, p FROM dist_dash_view WHERE Dist_id=$dist_id";
        $block_score_res = mysqli_query($con, $call_block_score);

        // Build Block-wise Score Category counts in PHP
        $block_score_data = [];

        while ($row = mysqli_fetch_assoc($block_score_res)) {
            $block = $row['Block_Name'];
            $p = floatval($row['p']);

            // Determine score category
            if ($p < 40) {
                $cat = '<40%';
            } elseif ($p < 50) {
                $cat = '40%-50%';
            } elseif ($p < 80) {
                $cat = '50%-80%';
            } elseif ($p < 90) {
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
                        <h5 class="card-title">Block-wise Facility Performance</h5>
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
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            Compliance Summary
                            <a href="export_dist_score_card.php">
                                <i class="bi bi-arrow-down-circle-fill"></i>
                            </a>
                        </h4>

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
                                    $dist_id = $_SESSION['dist'];
                                    $userid = $_SESSION['userid'];
                                    $call_count = "SELECT * FROM dist_dash_view WHERE Dist_id=$dist_id";
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
                                                <td class='table-success'><a href='export_dist_dash_comp.php?id={$row['fac_id']}'><i class='bi bi-arrow-down-circle-fill'></i></a></td>
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