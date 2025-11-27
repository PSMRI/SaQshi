<?php include("assets/head/h.php");
$fname = $_SESSION['facname'];
?>
<div class="pcoded-main-container">
  <div class="pcoded-content">
    <div class="pagetitle mb-2">
      <h5 class="fw-bold text-primary mb-1">
        <i class="bi bi-person-badge-fill me-2"></i>Dashboard – Status of Overall Progress for <?php echo  $fname; ?> as of <?php echo date("d M Y h:i A"); ?>
      </h5>
    </div>
    <div class="text-end mb-2 exclude-from-pdf">
      <button class="btn btn-danger" onclick="downloadDashboardAsPDF()">Download Dashboard as PDF</button>
    </div>
    <! -------------------------------------------->
      <div class="row">
        <div class="col-lg-6 col-xl-4">
  <div class="card flat-card widget-primary-card">
    <div class="row-table">
      <div class="col-sm-3 card-body d-flex align-items-center justify-content-center">
        <i class="feather icon-layers text-primary"></i>
      </div>
      <div class="col-sm-9 py-3">
        <?php
        $fid = $_SESSION['u_facilityid'];
        $assid= $_SESSION['assperiod'];
        $count = 0;
        $q = mysqli_query($con, "SELECT COUNT(fac_dept_id) as total FROM sarbsoft_nqa.fac_dept_map WHERE fac_id = $fid and acc_id= $assid");
        if ($q && $row = mysqli_fetch_assoc($q)) {
          $count = $row['total'];
        }
        mysqli_free_result($q);
        $con->next_result();
        ?>
        <h2 class="text-warning"><?= htmlspecialchars($count) ?></h2>
        <h6>Departments</h6>
      </div>
    </div>
  </div>
</div>

        <div class="col-lg-6 col-xl-4">
  <div class="card flat-card widget-primary-card">
    <div class="row-table">
      <div class="col-sm-3 card-body d-flex align-items-center justify-content-center">
        <i class="feather icon-percent text-success"></i>
      </div>
      <div class="col-sm-9 py-3">
        <?php
        $f_ty_id = $_SESSION['f_type_id'];
        $fid = $_SESSION['u_facilityid'];
        $assid = $_SESSION['assperiod'];

        $percentage = 0;
        $q22 = mysqli_query($con, "CALL facility_dash_dh_perc($f_ty_id, $fid, $assid)");
        if ($q22 && $row = mysqli_fetch_assoc($q22)) {
          $obtained = $row['Obtained'] ?? 0;
          $total = $row['total'] ?? 0;
          if ($total > 0) {
            $percentage = round(($obtained / $total) * 100, 2);
          }
        }
        mysqli_free_result($q22);
        $con->next_result();

        $color = $percentage > 70 ? 'text-success' : 'text-danger';
        ?>
        <h4 class="<?= $color ?>"><?= $percentage ?>%</h4>
        <h6>Score</h6>
      </div>
    </div>
  </div>
</div>

        <!-- Indicators Card -->
<div class="col-lg-6 col-xl-4">
  <div class="card flat-card widget-primary-card">
    <div class="row-table">
      <div class="col-sm-3 card-body d-flex align-items-center justify-content-center">
        <i class="feather icon-layers text-info"></i>
      </div>
      <div class="col-sm-9 py-3">
        <?php
        $f_ty_id = $_SESSION['f_type_id'];
        $fid = $_SESSION['u_facilityid'];
        $assid = $_SESSION['assperiod'];

        // Baseline Percentage
        $percentage1 = 0;
        $q1 = mysqli_query($con, "CALL facility_dash_dh_perc_1($f_ty_id, $fid, $assid)");
        if ($q1 && $row1 = mysqli_fetch_assoc($q1)) {
          $obtained1 = $row1['Obtained'] ?? 0;
          $total1 = $row1['total'] ?? 0;
          $percentage1 = ($total1 > 0) ? round(($obtained1 / $total1) * 100, 2) : 0;
          $_SESSION['p1l1'] = $percentage1;
        }
        mysqli_free_result($q1);
        $con->next_result();

        // Indicator Counts
        $assessed_checklist = $total_checklist = $percentage_indicators = 0;
        $z = $o = $t = 0;
        $q2 = mysqli_query($con, "CALL facility_dash_dh_perc($f_ty_id, $fid, $assid)");
        if ($q2 && $row2 = mysqli_fetch_assoc($q2)) {
          $obtained = $row2['Obtained'] ?? 0;
          $total = $row2['total'] ?? 0;
          $assessed_checklist = $row2['assessed_checklist'] ?? 0;
          $total_checklist = $row2['total_checklist'] ?? 0;
          $percentage = ($total > 0) ? round(($obtained / $total) * 100, 2) : 0;
          $percentage_indicators = ($total_checklist > 0) ? round(($assessed_checklist / $total_checklist) * 100, 2) : 0;
          $_SESSION['p1l'] = $percentage;
          $_SESSION['indicator_percentage'] = $percentage_indicators;

          // For compliance cards
          $z = $row2['z'] ?? 0;
          $o = $row2['o'] ?? 0;
          $t = $row2['t'] ?? 0;
        }
        mysqli_free_result($q2);
        $con->next_result();

        $ind_text_class = $percentage > 70 ? 'text-success' : 'text-danger';
        ?>
        <h4 class="<?= $ind_text_class ?>"><?= "$assessed_checklist / $total_checklist" ?></h4>
        <h6>Indicators</h6>
      </div>
    </div>
  </div>
</div>
      </div>
       <div class="row">
<!-- Non Compliance -->
<div class="col-lg-6 col-xl-4">
  <div class="card flat-card widget-primary-card">
    <div class="row-table">
      <div class="col-sm-3 card-body d-flex align-items-center justify-content-center">
        <i class="feather icon-x text-danger"></i>
      </div>
      <div class="col-sm-9 py-3">
        <h4 class="text-danger"><?= $z ?></h4>
        <h6>Non Compliance</h6>
      </div>
    </div>
  </div>
</div>

<!-- Partially Compliance -->
<div class="col-lg-6 col-xl-4">
  <div class="card flat-card widget-primary-card">
    <div class="row-table">
      <div class="col-sm-3 card-body d-flex align-items-center justify-content-center">
        <i class="feather icon-alert-triangle text-warning"></i>
      </div>
      <div class="col-sm-9 py-3">
        <h4 class="text-warning"><?= $o ?></h4>
        <h6>Partially Compliance</h6>
      </div>
    </div>
  </div>
</div>

<!-- Fully Compliance -->
<div class="col-lg-6 col-xl-4">
  <div class="card flat-card widget-primary-card">
    <div class="row-table">
      <div class="col-sm-3 card-body d-flex align-items-center justify-content-center">
        <i class="feather icon-check-circle text-success"></i>
      </div>
      <div class="col-sm-9 py-3">
        <h4 class="text-white"><?= $t ?></h4>
        <h6>Fully Compliance</h6>
      </div>
    </div>
  </div>
</div>
      </div>
        <! -------------------------------------------->
          <! -------------------------------------------->
          <label> <a href="assets/reports/export_facility_all_rpt.php?id=<?php echo $_SESSION['u_facilityid']; ?>">Download compliance for all departments*<i class="bi bi-arrow-down-circle-fill"></a></i> </label>
          <!------------------------>
          <div class="row">
            <div class="col-lg-6">
                <div class="card">
                   <div class="card-header bg-primary  fw-bold">
                     <h5 class="card-title text-white">Base Line Score</h5>
                  </div>
                    <div class="card-body">
           
                        <!-- Pie Chart -->
                        <div id="pieChart"></div>
                        <?php
                          $fid = $_SESSION['u_facilityid'];
                          $assid =  $_SESSION['assperiod'];
                        $query_1 = "CALL facility_dept_caht1($fid,$assid)";
                        $result_1 = $con->query($query_1);
                        while ($row = mysqli_fetch_assoc($result_1)) {



                            $h0 =  $row['o'];
                            $h1 = $row['z'];
                            $h2 = $row['t'];
                        ?>
                            <script>
                                document.addEventListener("DOMContentLoaded", () => {
                                    new ApexCharts(document.querySelector("#pieChart"), {
                                        series: [0, <?php echo $h2; ?>, <?php echo $h0; ?>, <?php echo $h1; ?>],
                                        chart: {
                                            height: 300,
                                            type: 'pie',
                                            toolbar: {
                                                show: true
                                            }
                                        },
                                        labels: ['Total Score:<h2><?php echo  $_SESSION['p1l1'];echo "%"; ?></h2>', 'Fully compliance', 'Partially Compliance', 'Non compliance']
                                    }).render();
                                });
                            </script>
                        <?php }
                        mysqli_free_result($result_1);
                        $con->next_result();
                        ?>
                        <!-- End Pie Chart -->

                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                  <div class="card-header bg-primary  fw-bold">
                     <h5 class="card-title text-white">Current Score</h5>
                  </div>
                    <div class="card-body">
                 
                        <!-- Pie Chart -->
                        <div id="pieChart1"></div>
                        <?php
                        $fid = $_SESSION['u_facilityid'];
                        $assid =  $_SESSION['assperiod'];
                        $query_12 = "CALL facility_dept_caht12($fid,$assid)";
                        $result_12 = $con->query($query_12);
                        while ($row = mysqli_fetch_assoc($result_12)) {
                            $h0 =  $row['o'];
                            $h1 = $row['z'];
                            $h2 = $row['t'];
                        ?>
                            <script>
                                document.addEventListener("DOMContentLoaded", () => {
                                    new ApexCharts(document.querySelector("#pieChart1"), {
                                        series: [0, <?php echo $h2; ?>, <?php echo $h0; ?>, <?php echo $h1; ?>],
                                        chart: {
                                            height: 300,
                                            type: 'pie',
                                            toolbar: {
                                                show: true
                                            }
                                        },
                                        labels: ['Total Score:<h2><?php echo  $_SESSION['p1l'];echo "%"; ?></h2>', 'Fully compliance', 'Partially Compliance', 'Non compliance']
                                    }).render();
                                });
                            </script>
                        <?php }
                        mysqli_free_result($result_12);
                        $con->next_result();
                        ?>
                        <!-- End Pie Chart -->

                    </div>
                </div>
            </div>

        </div>
          <!-------------------------->
          
          
          <div class="row">
              <div class="col-sm-6">
                <div class="card">
                    <div class="card-header bg-primary  fw-bold">
                     <h5 class="card-title text-white">Departments Score(%)</h5>
                  </div>
                  <div class="card-body">
                    
                    <?php
                    $f_ty_id = $_SESSION['f_type_id'];
                    $fid = $_SESSION['u_facilityid'];
                    $assid =  $_SESSION['assperiod'];
                    $tablequery = "CALL depart_dash_dh($f_ty_id, $fid, $assid)";
                    $q = mysqli_query($con, $tablequery);
                    while ($row = mysqli_fetch_array($q)){
                      $perce=round(($row['Obtained'] / $row['total']) * 100, 2);
                    if ($perce>70){
                  ?>
                    <span class="badge bg-primary"><i class="bi bi-check-circle me-1"></i> <?php echo  $row['dept_name']; ?></span>
                    <span class="badge bg-success"> <?php echo  $perce; ?>%</span><br>

                  <?php }elseif($perce>65 and $perce<70){
                    ?>
                    <span class="badge bg-primary"><i class="bi bi-check-circle me-1"></i> <?php echo  $row['dept_name']; ?></span>
                    <span class="badge bg-warning"> <?php echo  $perce; ?>%</span><br>
                    <?php
                  }elseif($perce<65){

                   ?>
                    <span class="badge bg-primary"><i class="bi bi-check-circle me-1"></i> <?php echo  $row['dept_name']; ?></span>
                    <span class="badge bg-danger"> <?php echo  $perce; ?>%</span><br>
                    <?php 
                    }else{
              $perce=0;
                        ?>
                       <span class="badge bg-primary"><i class="bi bi-check-circle me-1"></i> <?php echo  $row['dept_name']; ?></span>
                    <span class="badge bg-danger"> <?php echo  $perce; ?>%</span><br>  
                    <?php }
                    }

                  mysqli_free_result($q);
                  $con->next_result();
                  ?>
                  </div>
                </div>
              </div>



              <div class="col-sm-6">
                <div class="card">
<div class="card-header bg-primary  fw-bold">
                     <h5 class="card-title text-white">Checkpoint assessed</h5>
                  </div>
                  <div class="card-body">
                    
                    <?php

                    $f_ty_id = $_SESSION['f_type_id'];
                    $fid = $_SESSION['u_facilityid'];
                    $assid =  $_SESSION['assperiod'];
                    $tablequery = "CALL depart_dash_indicator_count_dh($f_ty_id, $fid, $assid)";
                    $q = mysqli_query($con, $tablequery);
                    while ($row = mysqli_fetch_array($q)) {
                      $perce=round(($row['Obtained'] / $row['total']) * 100, 2);
                      if ($perce>70){
                    ?>
                      <span class="badge bg-primary"><i class="bi bi-check-circle me-1"></i> <?php echo  $row['dept_name']; ?></span>
                      <span class="badge bg-success"> <?php echo  $row['Obtained']; ?></span>/<span class="badge bg-secondary rounded-pill"><?php echo  $row['total']; ?></span><br>

                    <?php }else{
                      ?>
                      <span class="badge bg-primary"><i class="bi bi-check-circle me-1"></i> <?php echo  $row['dept_name']; ?></span>
                      <span class="badge bg-danger"> <?php echo  $row['Obtained']; ?></span>/<span class="badge bg-secondary rounded-pill"><?php echo  $row['total']; ?></span><br>
                      <?php }
                    }

                    mysqli_free_result($q);
                    $con->next_result();
                    ?>
                  </div>
                </div>
              </div>
              <!------>
            </div>

            <div class=row>
              <div class="col-lg-6">
                <div class="card">
 <div class="card-header bg-primary  fw-bold">
                     <h5 class="card-title text-white">Areas of Concern wise Facility Score(%)</h5>
                  </div>
                  <div class="card-body">
                    
                    <?php
                    $f_ty_id = $_SESSION['f_type_id'];
                    $fid = $_SESSION['u_facilityid'];
                    $assid =  $_SESSION['assperiod'];
                    $tablequery = "CALL admin_dash($f_ty_id, $fid, $assid)";
                    $q = mysqli_query($con, $tablequery);
                    while ($row = mysqli_fetch_array($q)) {
                      $conc=$row['concern_name'];
                      $perce=round(($row['Obtained'] / $row['total']) * 100, 2);
                     
                      if ($perce>70){
                        ?>
                <!-- Progress Bars with labels-->
                <label><?php echo  $conc; ?></label>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped bg-success progress-bar-animated" role="progressbar" style="width: <?php echo round(($row['Obtained'] / $row['total']) * 100, 2) ?>%" aria-valuenow="<?php echo $row['Obtained']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $row['total']; ?>"><?php echo round(($row['Obtained'] / $row['total']) * 100, 2) ?>%</div>
                </div>
                <?php }elseif($perce>65 and $perce<70) {
                          
                            ?>
                            <label><?php echo  $conc; ?></label>
                            <div class="progress">
                    <div class="progress-bar progress-bar-striped bg-warning progress-bar-animated" role="progressbar" style="width: <?php echo round(($row['Obtained'] / $row['total']) * 100, 2) ?>%" aria-valuenow="<?php echo $row['Obtained']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $row['total']; ?>"><?php echo round(($row['Obtained'] / $row['total']) * 100, 2) ?>%</div>
                </div>
                <?php
                          }elseif($perce<65) {
                            ?> 
                            <label><?php echo $conc; ?></label>
                            <div class="progress">
                    <div class="progress-bar progress-bar-striped bg-danger progress-bar-animated" role="progressbar" style="width: <?php echo round(($row['Obtained'] / $row['total']) * 100, 2) ?>%" aria-valuenow="<?php echo $row['Obtained']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $row['total']; ?>"><?php echo round(($row['Obtained'] / $row['total']) * 100, 2) ?>%</div>
                </div>
                <?php
            }else{
              $perce=0;
                        ?>
                        <label><?php echo  $conc; ?></label>
                        <div class="progress">
                    <div class="progress-bar progress-bar-striped bg-danger progress-bar-animated" role="progressbar" style="width: <?php echo round(($row['Obtained'] / $row['total']) * 100, 2) ?>%" aria-valuenow="<?php echo $row['Obtained']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $row['total']; ?>"><?php echo round(($row['Obtained'] / $row['total']) * 100, 2) ?>%</div>
                </div>
            <?php }}
            mysqli_free_result($q);
            $con->next_result();
            ?>
                  </div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="card">
                  <div class="card-header bg-primary  fw-bold">
                     <h5 class="card-title text-white">Department Wise Compliance Status</h5>
                  </div>
                  <div class="card-body">
                   

                    <!-- Column Chart -->
                    <div id="columnChart">
                      <?php

                      $fid = $_SESSION['u_facilityid'];
                      $assid =  $_SESSION['assperiod'];
                      $query = "CALL facility_dept_caht($fid,$assid)";
                      $result = $con->query($query);
                      if ($result->num_rows > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                          $values[] = $row['dept_name'];
                          $values1[] = "'" . $row['o'] . "'";
                          $values0[] = "'" . $row['z'] . "'";
                          $values2[] = "'" . $row['t'] . "'";
                        }
                        $h = "'" . implode("','", $values) . "'";
                        $h0 = implode(",", $values0);
                        $h1 = implode(",", $values1);
                        $h2 = implode(",", $values2);
                      ?>
                        <script>
                          document.addEventListener("DOMContentLoaded", () => {
                            new ApexCharts(document.querySelector("#columnChart"), {
                              series: [{
                                name: 'Non compliance',
                                data: [<?php echo $h0; ?>]
                              }, {
                                name: 'Partially  Compliance',
                                data: [<?php echo $h1; ?>]
                              }, {
                                name: 'Fully compliance',
                                data: [<?php echo $h2; ?>]
                              }],
                              chart: {
                                type: 'bar',
                                height: 350
                              },
                              plotOptions: {
                                bar: {
                                  horizontal: false,
                                  columnWidth: '25%',
                                  endingShape: 'rounded'
                                },
                              },
                              dataLabels: {
                                enabled: false
                              },
                              stroke: {
                                show: true,
                                width: 2,
                                colors: ['transparent']
                              },
                              xaxis: {
                                categories: [<?php echo $h; ?>],
                              },
                              yaxis: {
                                title: {
                                  text: 'No of Indicators'
                                }
                              },
                              fill: {
                                opacity: 1
                              },
                              tooltip: {
                                y: {
                                  formatter: function(val) {
                                    return val + " " + "indicators"
                                  }
                                }
                              }
                            }).render();
                          });
                        </script>
                      <?php } ?>
                      <!-- End Column Chart -->
                    </div>
                  </div>
                </div>
              </div>
            </div>
   </div>
    <div id="pdf-header" class="d-none">
         <h5 style="text-align: center;">
            SaQshi Dashboard Report -Facility Progress Overview - <?php echo $fname; ?><br>
            Generated on: <?php echo date("d M Y h:i A"); ?>
         </h5>
      </div>
            </div>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
          <script>
  async function downloadDashboardAsPDF() {
    const { jsPDF } = window.jspdf;
    const dashboard = document.querySelector('.pcoded-main-container');
    const header = document.getElementById('pdf-header');

    header?.classList.remove('d-none');
    window.scrollTo(0, 0);

    html2canvas(dashboard, {
      scale: 2,
      useCORS: true,
      ignoreElements: el => el.classList.contains('exclude-from-pdf')
    }).then(canvas => {
      const imgData = canvas.toDataURL('image/png');
      const pdf = new jsPDF('p', 'mm', 'a4');
      const pdfWidth = pdf.internal.pageSize.getWidth();
      const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

      pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
      pdf.save("SaQshi_Dashboard_Report.pdf");

      header?.classList.add('d-none');
    }).catch(err => {
      console.error("PDF generation error:", err);
      header?.classList.add('d-none');
    });
  }
</script>
            <?php include("assets/head/f.php"); ?>