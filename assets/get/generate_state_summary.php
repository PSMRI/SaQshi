<?php
include("assets/head/h.php");
ini_set('max_execution_time', 300); // 300 seconds = 5 minutes
?>
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

                                        State: <strong> Bihar</strong>
                                    </h4>
                                </center>
                                <div class="d-flex flex-nowrap overflow-auto">
                                    <?php
                                    $call_q1 = "CALL state_dash_count";
                                    $q22 = mysqli_query($con, $call_q1);

                                    while ($row = mysqli_fetch_array($q22)) {
                                        $facilities = [
                                            'DH' => ['total' => $row['DH'], 'comp' => $row['DHcomp'], 'icon' => 'bi bi-hospital'],
                                            'SDH' => ['total' => $row['SDH'], 'comp' => $row['SDHcomp'], 'icon' => 'bi bi-hospital'],
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


                                $call_count = "SELECT * FROM state_dash_view";
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
                                                            <th>APHC</th>
                                                            <th>CHC</th>
                                                            <th>PHC</th>
                                                            <th>UPHC</th>
                                                            <th>HWC</th>

                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $call_q1 = "CALL state_dash_count_dist1(1)";
                                                        $q22 = mysqli_query($con, $call_q1);

                                                        while ($row = mysqli_fetch_array($q22)) {
                                                            $categories = [
                                                                'DH' => ['total' => $row['DH'], 'completed' => $row['DHcomp']],
                                                                'SDH' => ['total' => $row['SDH'], 'completed' => $row['SDHCcomp']],
                                                                'APHC' => ['total' => $row['APHC'], 'completed' => $row['APHCcomp']],
                                                                'CHC' => ['total' => $row['CHC'], 'completed' => $row['CHCcomp']],
                                                                'PHC' => ['total' => $row['PHC'], 'completed' => $row['PHCcomp']],
                                                                'UPHC' => ['total' => $row['UPHC'], 'completed' => $row['UPHCcomp']],
                                                                'HWC' => ['total' => $row['HWC'], 'completed' => $row['HWCcomp']],
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
                                    In this State , a total of <strong><?= $total_facilities ?> facilities</strong> were assessed.
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