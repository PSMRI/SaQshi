<?php
include("assets/head/h.php");
?>
<div class="pcoded-main-container">
  <div class="pcoded-content">
    <!-- Page Title -->
    <div class="pagetitle mb-2">
      <h5 class="fw-bold text-info mb-1">
        <i class="bi bi-person-badge-fill me-2"></i>Old Reports ( For HWC, if you have entered data dated before 01-June-202. )
      </h5>
    </div>
    <div class="row">
      <div class="col-sm-12">
        <div class="card">
          <div class="card-body">

            <form enctype="multipart/form-data" method="post" action="#">

              <div class="form-group row">
                <div class="col-auto">
                  <label for="complainant">Select Department</label>
                  <select class="form-control-sm form-control" id="Facility_Department"
                    name="Facility_Department" onchange="getText(this)">
                    <option value="0">--Select--</option>
                    <?php
                    $fsid = $_SESSION['u_facilityid'];
                    $dept = $_SESSION['dept_id1'];
                    $query = "SELECT  fac_dept_id, dept_name FROM fac_department where fac_dept_id in (select fac_dept_id from fac_dept_map where fac_id=$fsid and fac_dept_id= $dept )";
                    // $query = mysqli_query($con, $qr);
                    $result = $con->query($query);
                    if ($result->num_rows > 0) {
                      while ($row = mysqli_fetch_assoc($result)) {
                    ?>
                        <option value="<?php echo $row['fac_dept_id']; ?>"><?php echo $row['dept_name']; ?>
                        </option>
                    <?php
                      }
                    }
                    ?>
                  </select>

                  <input type="hidden" name="Department_name_hidden" id="Department_name_hidden">

                </div>
                <div class="col-auto">
                  <label for="complainant">Select Assessment </label>
                  <select class="form-control-sm form-control" id="Period" name="Period">
                    <option value="0">---Select--</option>
                    <?php
                    $fsid = $_SESSION['u_facilityid'];
                    $dept = $_SESSION['dept_id1'];
                    $query = "SELECT distinct id, ass_name  FROM assessment_desc where fac_id_fk=$fsid";
                    // $query = mysqli_query($con, $qr);
                    $result = $con->query($query);
                    if ($result->num_rows > 0) {
                      while ($row = mysqli_fetch_assoc($result)) {
                    ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['ass_name']; ?></option>
                    <?php
                      }
                    }
                    ?>
                  </select>

                </div>
                <div class="col-auto">
                  <label for="complainant">Select Reports type </label>
                  <select class="form-control-sm form-control" id="rt" name="rt">
                    <option value="0">---Select--</option>
                    <option value="3">Out Come Indicators</option>
                    <option value="5"> KPI </option>
                  </select>
                </div>
                <div class="col-auto">
                  <br>
                  <button type="submit" value="Submit1" name="submit1" class="btn btn-primary">View</button>

                </div>
              </div>


            </form>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-lr-12">
        <div class="card">
          <div class="card-body">
            <?php
            if (isset($_POST['submit1'])) {

            ?>
              <script>
                $("#Period option").each(function(index) {
                  var item = $(this).val();
                  if (item == <?php echo json_encode((string)($_POST['Period'] ?? '')); ?>) {
                    $(this).prop('selected', true);
                  }
                });
              </script>
              <script>
                $("#rt option").each(function(index) {
                  var item = $(this).val();
                  if (item == <?php echo json_encode((string)($_POST['rt'] ?? '')); ?>) {
                    $(this).prop('selected', true);
                  }
                });
              </script>
              <script>
                $("#Facility_Department option").each(function(index) {
                  var item = $(this).val();
                  if (item == <?php echo json_encode((string)($_POST['Facility_Department'] ?? '')); ?>) {
                    $(this).prop('selected', true);
                  }
                });
              </script>

              <?php



              $dept_id = $_POST["Facility_Department"];
              $_SESSION['period'] = $_POST['Period'];
              $Fa = $_SESSION['u_facilityid'];
              $fat = $_SESSION['f_type_id'];
              $p = $_SESSION['period'];
              $t = $_SESSION['userid'];
              //$dept=$_SESSION['dept_id1'];
              ?>

              <?php
              if ($_POST["rt"] == 3) {
                $dept_id = $_SESSION['dept_id1'];
                $Fa = $_SESSION['u_facilityid'];
                $p =  $_SESSION['assperiod'];
                //////APHC dept 35 outcome report ///////////////
                if ($dept_id == 35) {
                  $out_comerpt = "CALL outcome_report_test_dept_35($dept_id, $Fa,$p)";
                  $out_comerptquery = $con->query($out_comerpt);
                  if ($out_comerptquery->num_rows > 0) {
              ?>
                    <input type="button" value="Export" class="btn btn-success" onclick="exportToExcel('table3')" />
                    <table class="table w-auto small  table-bordered" style="border-color:#FF5733" id="table3">
                      <?php
                      while ($row = mysqli_fetch_array($out_comerptquery)) {
                      ?>

                        <thead>
                          <tr>
                            <th colspan="5" style="background-color:#FF5733">
                              <center>Out Come Indicator for the month of
                                <?php
                                $date_conv = $row['month_in'];
                                //  $date_conv1 = date("F Y", strtotime($date_conv));

                                echo $date_conv;

                                ?></center>
                            </th>
                          </tr>
                          <tr>
                            <th colspan="1" style="background-color:#e9e510"><i
                                class="bi bi-arrow-down-circle-fill"
                                onclick="ExportToExcel('xlsx')"> </th>
                            <th style="background-color:#e9e510">INDICATOR</th>
                            <th style="background-color:#e9e510">NUMERATOR</th>
                            <th style="background-color:#e9e510">DENOMINATOR</th>
                            <th style="background-color:#e9e510">OUTCOME REPORT</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td rowspan="2">Productivity</td>
                            <td>Number of test done per 100 patients</td>
                            <td><?php echo $row['a329']; ?></td>
                            <td><?php echo $row['a330']; ?></td>

                            <td><?php
                                $r1 =  $row['a329'];
                                $r2 =  $row['a330'];
                                if ($r1 == 0 or $r2 == 0) {
                                  echo "0";
                                } else {
                                  $round1 = (($row['a329'] * 100) / $row['a330']);
                                  echo round($round1, 2);
                                } ?></td>
                          </tr>
                          <tr>
                            <td>Number of HB test done per ANC</td>
                            <td><?php echo $row['a331']; ?></td>
                            <td><?php echo $row['a332']; ?></td>
                            <td><?php
                                $r1 =  $row['a333'];
                                $r2 =  $row['a332'];
                                if ($r1 == 0 or $r2 == 0) {
                                  echo "0";
                                } else {
                                  $round1 = (($row['a331']) / $row['a332']);
                                  echo round($round1, 2);
                                } ?></td>
                          </tr>
                          <tr>
                            <td rowspan="1">Efficiency</td>
                            <td>Number of stock out incidences of Reagents and Kits</td>
                            <td>NA</td>
                            <td>NA</td>
                            <td><?php echo $row['a334']; ?></td>
                          </tr>
                          <tr>
                            <td rowspan="2">Clinical care and safety</td>
                            <td>Number of Hb reported less than 7gm%</td>
                            <td>NA</td>
                            <td>NA</td>
                            <td><?php echo $row['a335']; ?></td>
                          </tr>
                          <tr>

                            <td>Number of rapid diagnostic kits discarded because of unsatisfactory reasons</td>
                            <td>NA</td>
                            <td>NA</td>
                            <td><?php echo $row['a336']; ?></td>
                          </tr>
                          <tr>
                            <td colspan="5"></td>
                          </tr>
                          <tr>
                            <td colspan="5" style="background-color:#f1e8bc">
                              <center>End Of Report for the month of <?php echo $date_conv; ?> </center>
                            </td>
                          </tr>
                          <tr>
                            <td colspan="5"></td>
                          </tr>
                        <?php
                      }
                    }
                  }
                  //////APHC dept 35 outcome report end 37 start ///////////////
                  elseif ($dept_id == 37) {
                    $out_comerpt = "CALL outcome_report_test_dept_37($dept_id, $Fa,$p)";
                    $out_comerptquery = $con->query($out_comerpt);

                    if ($out_comerptquery->num_rows > 0) {
                        ?>
                        <input type="button" value="Export" class="btn btn-success" onclick="exportToExcel('table3')" />
                        <table class="table w-auto small  table-bordered" style="border-color:#FF5733" id="table3">
                          <?php
                          while ($row = mysqli_fetch_array($out_comerptquery)) {
                          ?>

                            <thead>
                              <tr>
                                <th colspan="5" style="background-color:#FF5733">
                                  <center>Out Come Indicator for the month of
                                    <?php
                                    $date_conv = $row['month_in'];
                                    //  $date_conv1 = date("F Y", strtotime($date_conv));

                                    echo $date_conv;

                                    ?></center>
                                </th>
                              </tr>
                              <tr>
                                <th colspan="1" style="background-color:#e9e510"><i
                                    class="bi bi-arrow-down-circle-fill"
                                    onclick="ExportToExcel('xlsx')"> </th>
                                <th style="background-color:#e9e510">INDICATOR</th>
                                <th style="background-color:#e9e510">NUMERATOR</th>
                                <th style="background-color:#e9e510">DENOMINATOR</th>
                                <th style="background-color:#e9e510">OUTCOME REPORT</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr>
                                <td rowspan="4">Productivity</td>
                                <td>Stock out percent of supplies for RMNCH+A</td>
                                <td>NA</td>
                                <td>NA</td>
                                <td><?php echo $row['a337']; ?></td>
                              </tr>
                              <tr>

                                <td>Non availability of nursing days</td>
                                <td>NA</td>
                                <td>NA</td>
                                <td><?php echo $row['a338']; ?></td>
                              </tr>
                              <tr>

                                <td>Non availability of doctors days</td>
                                <td>NA</td>
                                <td>NA</td>
                                <td><?php echo $row['a339']; ?></td>
                              </tr>
                              <tr>

                                <td>Non availability of Support services</td>
                                <td>NA</td>
                                <td>NA</td>
                                <td><?php echo $row['a340']; ?></td>
                              </tr>
                              <tr>
                                <td>Service Quality Indicators</td>
                                <td>Staff Satisfaction Score</td>
                                <td><?php echo $row['a341']; ?></td>
                                <td><?php echo $row['a342']; ?></td>
                                <td><?php
                                    $r1 =  $row['a341'];
                                    $r2 =  $row['a342'];
                                    if ($r1 == 0 or $r2 == 0) {
                                      echo "0";
                                    } else {
                                      $round1 = (($row['a340']) / $row['a341']);
                                      echo round($round1, 2);
                                    } ?></td>
                              </tr>
                              <tr>
                                <td colspan="5"></td>
                              </tr>
                              <tr>
                                <td colspan="5" style="background-color:#f1e8bc">
                                  <center>End Of Report for the month of <?php echo $date_conv; ?> </center>
                                </td>
                              </tr>
                              <tr>
                                <td colspan="5"></td>
                              </tr>
                            <?php
                          }
                        }
                      }
                      ////////////APHC 37 out come rpt end 36  //////////////////   


                      elseif ($dept_id == 36) {
                        $out_comerpt = "CALL outcome_report_test_dept_36($dept_id, $Fa,$p)";
                        $out_comerptquery = $con->query($out_comerpt);

                        if ($out_comerptquery->num_rows > 0) {
                            ?>
                            <input type="button" value="Export" class="btn btn-success" onclick="exportToExcel('table3')" />
                            <table class="table w-auto small  table-bordered" style="border-color:#FF5733" id="table3">
                              <?php
                              while ($row = mysqli_fetch_array($out_comerptquery)) {
                              ?>
                                <thead>
                                  <tr>
                                    <th colspan="5" style="background-color:#FF5733">
                                      <center>Out Come Indicator for the month of
                                        <?php
                                        $date_conv = $row['month_in'];
                                        //  $date_conv1 = date("F Y", strtotime($date_conv));

                                        echo $date_conv;

                                        ?></center>
                                    </th>
                                  </tr>
                                  <tr>
                                    <th colspan="1" style="background-color:#e9e510"><i
                                        class="bi bi-arrow-down-circle-fill"
                                        onclick="ExportToExcel('xlsx')"> </th>
                                    <th style="background-color:#e9e510">INDICATOR</th>
                                    <th style="background-color:#e9e510">NUMERATOR</th>
                                    <th style="background-color:#e9e510">DENOMINATOR</th>
                                    <th style="background-color:#e9e510">OUTCOME REPORT</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <tr>
                                    <td rowspan="6">Productivity</td>
                                    <td>Number of AFB examined per 1000 population</td>
                                    <td><?php echo $row['a343']; ?></td>
                                    <td><?php echo $row['a344']; ?></td>
                                    <td><?php
                                        $r1 =  $row['a343'];
                                        $r2 =  $row['a344'];
                                        if ($r1 == 0 or $r2 == 0) {
                                          echo "0";
                                        } else {
                                          $round1 = (($row['a343'] * 1000) / $row['a344']);
                                          echo round($round1, 2);
                                        } ?></td>
                                  </tr>
                                  <tr>

                                    <td>Number of blood smear examined per 1000 population for malaria </td>
                                    <td><?php echo $row['a345']; ?></td>
                                    <td><?php echo $row['a346']; ?></td>
                                    <td><?php
                                        $r1 =  $row['a345'];
                                        $r2 =  $row['a346'];
                                        if ($r1 == 0 or $r2 == 0) {
                                          echo "0";
                                        } else {
                                          $round1 = (($row['a345'] * 1000) / $row['a346']);
                                          echo round($round1, 2);
                                        } ?></td>
                                  </tr>
                                  <tr>
                                    <td>Number of water sample tested per month</td>
                                    <td><?php echo $row['a347']; ?></td>
                                    <td>NA</td>
                                    <td><?php echo $row['a347']; ?></td>
                                  </tr>
                                  <tr>
                                    <td>Number of school visited under School Health Program</td>
                                    <td><?php echo $row['a348']; ?></td>
                                    <td>NA</td>
                                    <td><?php echo $row['a348']; ?></td>
                                  </tr>
                                  <tr>

                                    <td>Number of HIV test done per 1000 population</td>
                                    <td><?php echo $row['a349']; ?></td>
                                    <td><?php echo $row['a350']; ?></td>
                                    <td><?php
                                        $r1 =  $row['a349'];
                                        $r2 =  $row['a350'];
                                        if ($r1 == 0 or $r2 == 0) {
                                          echo "0";
                                        } else {
                                          $round1 = (($row['a349'] * 100) / $row['a350']);
                                          echo round($round1, 2);
                                        } ?></td>
                                  </tr>
                                  <tr>

                                    <td>Number of women HIV Positive out of total registered</td>
                                    <td><?php echo $row['a351']; ?></td>
                                    <td><?php echo $row['a352']; ?></td>
                                    <td><?php
                                        $r1 =  $row['a351'];
                                        $r2 =  $row['a352'];
                                        if ($r1 == 0 or $r2 == 0) {
                                          echo "0";
                                        } else {
                                          $round1 = (($row['a351']) / $row['a352']);
                                          echo round($round1, 2);
                                        } ?></td>
                                  </tr>
                                  <tr>
                                    <td rowspan="5">Efficiency</td>
                                    <td>Percentage of DOTS cases completed successfully</td>
                                    <td><?php echo $row['a353']; ?></td>
                                    <td>NA</td>
                                    <td><?php echo $row['a353']; ?></td>
                                  </tr>
                                  <tr>

                                    <td>Failure rate including death and default under RNTCP</td>
                                    <td><?php echo $row['a354']; ?></td>
                                    <td>NA</td>
                                    <td><?php echo $row['a354']; ?></td>
                                  </tr>
                                  <tr>

                                    <td>Number of children referred to higher centre under School Health Program</td>
                                    <td><?php echo $row['a355']; ?></td>
                                    <td>NA</td>
                                    <td><?php echo $row['a355']; ?></td>
                                  </tr>
                                  <tr>

                                    <td>Number of refraction error detected</td>
                                    <td><?php echo $row['a356']; ?></td>
                                    <td>NA</td>
                                    <td><?php echo $row['a356']; ?></td>
                                  </tr>
                                  <tr>

                                    <td>Number of diabetic and hypertensive cases detected</td>
                                    <td><?php echo $row['a357']; ?></td>
                                    <td>NA</td>
                                    <td><?php echo $row['a357']; ?></td>
                                  </tr>
                                  <tr>
                                    <td rowspan="4">Clinical care and safety</td>
                                    <td>Percentage of suspected TB cases reffered for HIV</td>
                                    <td><?php echo $row['a358']; ?></td>
                                    <td><?php echo $row['a359']; ?></td>
                                    <td><?php
                                        $r1 =  $row['a358'];
                                        $r2 =  $row['a359'];
                                        if ($r1 == 0 or $r2 == 0) {
                                          echo "0";
                                        } else {
                                          $round1 = (($row['a358'] * 100) / $row['a359']);
                                          echo round($round1, 2);
                                        } ?></td>
                                  </tr>
                                  <tr>

                                    <td>Monthly blood examination rate</td>
                                    <td><?php echo $row['a360']; ?></td>
                                    <td>NA</td>
                                    <td><?php echo $row['a360']; ?></td>
                                  </tr>
                                  <tr>

                                    <td>Multidrug treatment completion rate</td>
                                    <td><?php echo $row['a361']; ?></td>
                                    <td>NA</td>
                                    <td><?php echo $row['a361']; ?></td>
                                  </tr>
                                  <tr>

                                    <td>Number of babies followed up after delivery at 6 week, 6 months, 12 months and 18 months under NACP</td>
                                    <td><?php echo $row['a362']; ?></td>
                                    <td><?php echo $row['a363']; ?></td>
                                    <td><?php
                                        $r1 =  $row['a362'];
                                        $r2 =  $row['a363'];
                                        if ($r1 == 0 or $r2 == 0) {
                                          echo "0";
                                        } else {
                                          $round1 = (($row['a362'] * 100) / $row['a363']);
                                          echo round($round1, 2);
                                        } ?></td>
                                  </tr>
                                  <tr>
                                    <td colspan="5"></td>
                                  </tr>
                                  <tr>
                                    <td colspan="5" style="background-color:#f1e8bc">
                                      <center>End Of Report for the month of <?php echo $date_conv; ?> </center>
                                    </td>
                                  </tr>
                                  <tr>
                                    <td colspan="5"></td>
                                  </tr>
                                <?php
                              }
                            }
                          } elseif ($dept_id == 34) {
                            $out_comerpt = "CALL outcome_report_test_dept_34($dept_id, $Fa,$p)";
                            $out_comerptquery = $con->query($out_comerpt);

                            if ($out_comerptquery->num_rows > 0) {
                                ?>
                                <input type="button" value="Export" class="btn btn-success" onclick="exportToExcel('table3')" />
                                <table class="table w-auto small  table-bordered" style="border-color:#FF5733" id="table3">
                                  <?php
                                  while ($row = mysqli_fetch_array($out_comerptquery)) {
                                  ?>
                                    <thead>
                                      <tr>
                                        <th colspan="5" style="background-color:#FF5733">
                                          <center>Out Come Indicator for the month of
                                            <?php
                                            $date_conv = $row['month_in'];
                                            //  $date_conv1 = date("F Y", strtotime($date_conv));

                                            echo $date_conv;

                                            ?></center>
                                        </th>
                                      </tr>
                                      <tr>
                                        <th colspan="1" style="background-color:#e9e510"><i
                                            class="bi bi-arrow-down-circle-fill"
                                            onclick="ExportToExcel('xlsx')"> </th>
                                        <th style="background-color:#e9e510">INDICATOR</th>
                                        <th style="background-color:#e9e510">NUMERATOR</th>
                                        <th style="background-color:#e9e510">DENOMINATOR</th>
                                        <th style="background-color:#e9e510">OUTCOME REPORT</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <tr>
                                        <td rowspan="10">Productivity</td>
                                        <td>OPD Per Day</td>
                                        <td><?php echo $row['a364']; ?></td>
                                        <td>NA</td>
                                        <td><?php echo $row['a364']; ?></td>
                                      </tr>
                                      <tr>

                                        <td>IUCD inserted per 1000 eligible female</td>
                                        <td><?php echo $row['a365']; ?></td>
                                        <td><?php echo $row['a366']; ?></td>
                                        <td><?php
                                            $r1 =  $row['a365'];
                                            $r2 =  $row['a366'];
                                            if ($r1 == 0 or $r2 == 0) {
                                              echo "0";
                                            } else {
                                              $round1 = (($row['a365'] * 1000) / $row['a366']);
                                              echo round($round1, 2);
                                            } ?></td>
                                      </tr>
                                      <tr>

                                        <td>Total number of Ambulance visits/trips</td>
                                        <td><?php echo $row['a367']; ?></td>
                                        <td>NA</td>
                                        <td><?php echo $row['a367']; ?></td>
                                      </tr>
                                      <tr>

                                        <td>Adolescent OPD per month </td>
                                        <td><?php echo $row['a368']; ?></td>
                                        <td>NA</td>
                                        <td><?php echo $row['a368']; ?></td>
                                      </tr>
                                      <tr>

                                        <td>Children attended in OPD per month </td>
                                        <td><?php echo $row['a369']; ?></td>
                                        <td>NA</td>
                                        <td><?php echo $row['a369']; ?></td>
                                      </tr>
                                      <tr>

                                        <td>Patients attended after OPD hours </td>
                                        <td><?php echo $row['a370']; ?></td>
                                        <td>NA</td>
                                        <td><?php echo $row['a370']; ?></td>
                                      </tr>
                                      <tr>

                                        <td>AYUSH OPD per month </td>
                                        <td><?php echo $row['a371']; ?></td>
                                        <td>NA</td>
                                        <td><?php echo $row['a371']; ?></td>
                                      </tr>
                                      <tr>

                                        <td>ANC conducted per month</td>
                                        <td><?php echo $row['a372']; ?></td>
                                        <td>NA</td>
                                        <td><?php echo $row['a372']; ?></td>
                                      </tr>
                                      <tr>

                                        <td>Minor Procedure conducted per month</td>
                                        <td><?php echo $row['a373']; ?></td>
                                        <td>NA</td>
                                        <td><?php echo $row['a373']; ?></td>
                                      </tr>
                                      <tr>

                                        <td>Number of children immunized per month</td>
                                        <td><?php echo $row['a374']; ?></td>
                                        <td>NA</td>
                                        <td><?php echo $row['a374']; ?></td>
                                      </tr>
                                      <tr>
                                        <td rowspan="5">Efficiency</td>
                                        <td>OPD Per Doctor</td>
                                        <td><?php echo $row['a375']; ?></td>
                                        <td><?php echo $row['a376']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a375'];
                                          $r2 =  $row['a376'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a375']) / $row['a376']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Percentage of missed out ANC's</td>
                                        <td><?php echo $row['a377']; ?></td>
                                        <td><?php echo $row['a378']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a377'];
                                          $r2 =  $row['a378'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a377'] * 100) / $row['a378']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Percentage of follow up patients</td>
                                        <td><?php echo $row['a379']; ?></td>
                                        <td><?php echo $row['a380']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a379'];
                                          $r2 =  $row['a380'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a379'] * 100) / $row['a380']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Percentage of clients accepted limiting out of total counselled</td>
                                        <td><?php echo $row['a381']; ?></td>
                                        <td><?php echo $row['a382']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a381'];
                                          $r2 =  $row['a382'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a381'] * 100) / $row['a382']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Percentage of drop out of DPT vaccine</td>
                                        <td><?php echo $row['a383']; ?></td>
                                        <td><?php echo $row['a384']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a383'];
                                          $r2 =  $row['a384'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a384'] - $row['a383']) / $row['a384']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td rowspan="7">Clinical care and safety</td>
                                        <td> Percentage of Anaemia cases treated successfully at PHC</td>
                                        <td><?php echo $row['a385']; ?></td>
                                        <td><?php echo $row['a386']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a385'];
                                          $r2 =  $row['a386'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a385'] * 100) / $row['a386']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Percentage of pregnant women therapeutic dose of IFA</td>
                                        <td><?php echo $row['a387']; ?></td>
                                        <td><?php echo $row['a388']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a387'];
                                          $r2 =  $row['a388'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a387'] * 100) / $row['a388']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>IUCD rejection or Complication rate</td>
                                        <td><?php echo $row['a389']; ?></td>
                                        <td><?php echo $row['a390']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a389'];
                                          $r2 =  $row['a390'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a389']) / $row['a390']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Percentage of high risk pregnancy detected during ANC</td>
                                        <td><?php echo $row['a391']; ?></td>
                                        <td><?php echo $row['a392']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a391'];
                                          $r2 =  $row['a392'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a391'] * 100) / $row['a392']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Percentage of AEFI cases reported</td>
                                        <td><?php echo $row['a393']; ?></td>
                                        <td><?php echo $row['a394']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a393'];
                                          $r2 =  $row['a394'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a393'] * 100) / $row['a394']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Percentage of children with diarrhoea treated with ORS and Zinc</td>
                                        <td><?php echo $row['a395']; ?></td>
                                        <td><?php echo $row['a396']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a395'];
                                          $r2 =  $row['a396'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a395']) / $row['a396']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Percentage of children with Pneumonia treated with antibiotic</td>
                                        <td><?php echo $row['a397']; ?></td>
                                        <td><?php echo $row['a398']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a397'];
                                          $r2 =  $row['a398'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a397']) / $row['a398']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Patient Satisfaction Score</td>
                                        <td rowspan="5">Clinical care and safety</td>
                                        <td><?php echo $row['a399']; ?></td>
                                        <td><?php echo $row['a400']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a399'];
                                          $r2 =  $row['a400'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a399']) / $row['a400']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Waiting time for Consultation</td>
                                        <td><?php echo $row['a401']; ?></td>
                                        <td><?php echo $row['a402']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a401'];
                                          $r2 =  $row['a402'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a400']) / $row['a402']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Waiting time at Drug distribution counter</td>
                                        <td><?php echo $row['a403']; ?></td>
                                        <td><?php echo $row['a404']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a403'];
                                          $r2 =  $row['a404'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a403']) / $row['a404']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Average Consultation time in OPD</td>
                                        <td><?php echo $row['a405']; ?></td>
                                        <td><?php echo $row['a406']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a405'];
                                          $r2 =  $row['a406'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a405']) / $row['a406']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td>Waiting time at ANC Clinic</td>
                                        <td><?php echo $row['a407']; ?></td>
                                        <td><?php echo $row['a408']; ?></td>
                                        <td>
                                          <?php
                                          $r1 =  $row['a407'];
                                          $r2 =  $row['a408'];
                                          if ($r1 == 0 or $r2 == 0) {
                                            echo "0";
                                          } else {
                                            $round1 = (($row['a407']) / $row['a408']);
                                            echo round($round1, 2);
                                          } ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td colspan="5"></td>
                                      </tr>
                                      <tr>
                                        <td colspan="5" style="background-color:#f1e8bc">
                                          <center>End Of Report for the month of <?php echo $date_conv; ?> </center>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td colspan="5"></td>
                                      </tr>
                                    <?php
                                  }
                                }
                              } elseif ($dept_id == 32) {
                                $out_comerpt = "CALL outcome_report_test($dept_id, $Fa,$p)";
                                $out_comerptquery = $con->query($out_comerpt);
                                if ($out_comerptquery->num_rows > 0) {
                                    ?>
                                    <input type="button" value="Export" class="btn btn-success" onclick="exportToExcel('table3')" />
                                    <table class="table w-auto small  table-bordered" style="border-color:#FF5733" id="table3">
                                      <?php
                                      while ($row = mysqli_fetch_array($out_comerptquery)) {
                                      ?>

                                        <thead>
                                          <tr>
                                            <th colspan="5" style="background-color:#FF5733">
                                              <center>Out Come Indicator for the month of
                                                <?php
                                                $date_conv = $row['month_in'];
                                                //  $date_conv1 = date("F Y", strtotime($date_conv));

                                                echo $date_conv;

                                                ?></center>
                                            </th>
                                          </tr>
                                          <tr>
                                            <th colspan="1" style="background-color:#e9e510"><i
                                                class="bi bi-arrow-down-circle-fill"
                                                onclick="ExportToExcel('xlsx')"> </th>
                                            <th style="background-color:#e9e510">INDICATOR</th>
                                            <th style="background-color:#e9e510">NUMERATOR</th>
                                            <th style="background-color:#e9e510">DENOMINATOR</th>
                                            <th style="background-color:#e9e510">OUTCOME REPORT</th>
                                          </tr>
                                        </thead>
                                        <tbody>
                                          <tr>
                                            <td rowspan="7">The facility measures productivity
                                              indicators&nbsp;&nbsp;services on monthly basis</td>
                                            <td>No. of OPD Cases(Pregnant mothers, neonate,&nbsp;&nbsp;infant, children,
                                              adolescent, FP and CD )</td>
                                            <td><?php echo $row['a1']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a1']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>No. of follow up cases (repeat visit)-( Pregnant mothers,
                                              neonate,&nbsp;&nbsp;infant, children, adolescent, FP and CD )</td>
                                            <td><?php echo $row['a2']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a2']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>No. of&nbsp;&nbsp;cases referred to higher centre( Pregnant mothers,
                                              neonate,&nbsp;&nbsp;infant, children, adolescent, FP and CD )</td>
                                            <td><?php echo $row['a3']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a3']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>No. of Case specific OPD((Hypertension,Diebetes and Cancer)-NCD</td>
                                            <td><?php echo $row['a4']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a4']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>No. of&nbsp;&nbsp;cases referred to higher centre(Hypertension,Diebetes
                                              and Cancer)-NCD</td>
                                            <td><?php echo $row['a5']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a5']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>No. of case specific follow up (Hypertension,Diebetes and Cancer)-NCD
                                            </td>
                                            <td><?php echo $row['a6']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a6']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>Total no of&nbsp;&nbsp;drop out cases following identification in
                                              OPD(Hypertension,Diebetes and Cancer)-NCD</td>
                                            <td><?php echo $row['a7']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a7']; ?></td>
                                          </tr>
                                          <tr>
                                            <td rowspan="7">The facility measures efficiency indicators on monthly basis
                                            </td>
                                            <td>Percentage of women receiving all four ANCs</td>
                                            <td><?php echo $row['a8']; ?></td>
                                            <td><?php echo $row['a9']; ?></td>
                                            <td><?php
                                                $r1 =  $row['a8'];
                                                $r2 =  $row['a9'];
                                                if ($r1 == 0 or $r2 == 0) {
                                                  echo "0";
                                                } else {
                                                  $round1 = (($row['a8'] / $row['a9']) * 100);
                                                  echo round($round1, 2);
                                                } ?></td>
                                          </tr>
                                          <tr>
                                            <td>Drop out rate for Pentavalent immunization</td>
                                            <td><?php echo ($row['a10'] - $row['a11']); ?></td>
                                            <td><?php echo $row['a10']; ?></td>

                                            <td><?php
                                                $r1 =  $row['a10'];
                                                $r2 =  $row['a11'];
                                                if ($r1 == 0 or $r2 == 0) {
                                                  echo "0";
                                                } else {
                                                  $round1 = (($row['a10'] / $row['a11']) * 100);
                                                  echo round($round1, 2);
                                                } ?></td>
                                          </tr>
                                          <tr>
                                            <td>Drop out rate for NCDs</td>
                                            <td><?php echo $row['a12']; ?></td>
                                            <td><?php echo $row['a13']; ?></td>

                                            <td><?php
                                                $r1 =  $row['a12'];
                                                $r2 =  $row['a13'];
                                                if ($r1 == 0 or $r2 == 0) {
                                                  echo "0";
                                                } else {
                                                  $round1 = (($row['a12'] / $row['a13']) * 100);
                                                  echo round($round1, 2);
                                                } ?></td>
                                          </tr>
                                          <tr>
                                            <td>No. of stock out days of essential medicines</td>
                                            <td><?php echo $row['a14']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a14']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>No. of stock out days of essential diagnostic test </td>
                                            <td><?php echo $row['a15']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a15']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>No. of Yoga session conducted in month</td>
                                            <td><?php echo $row['a16']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a16']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>No of VHNDs conducted (for vulnerable population)</td>
                                            <td><?php echo $row['a17']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a17']; ?></td>
                                          </tr>
                                          <tr>
                                            <td rowspan="10">The facility measures clinical care
                                              indicators&nbsp;&nbsp;on monthly basis</td>
                                            <td>No. of high risk pregnancy identified during ANC</td>
                                            <td><?php echo $row['a18']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a18']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>No. of AEFI cases reported</td>
                                            <td><?php echo $row['a19']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a19']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>No. of Children with diarrhoea treated with ORS &amp; Zn</td>
                                            <td><?php echo $row['a20']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a20']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>Contraceptives acceptance rate</td>
                                            <td><?php echo $row['a21']; ?></td>
                                            <td><?php echo $row['a22']; ?></td>
                                            <td><?php
                                                $r1 =  $row['a21'];
                                                $r2 =  $row['a22'];
                                                if ($r1 == 0 or $r2 == 0) {
                                                  echo "0";
                                                } else {
                                                  $round1 = (($row['a21'] / $row['a22']) * 100);
                                                  echo round($round1, 2);
                                                } ?></td>
                                          </tr>
                                          <tr>
                                            <td>No. of Anaemia cases treated successfully </td>
                                            <td><?php echo $row['a23']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a23']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>Treatment completion rate for Tuberculosis</td>
                                            <td><?php echo $row['a24']; ?></td>
                                            <td><?php echo $row['a25']; ?></td>
                                            <td><?php
                                                $r1 =  $row['a24'];
                                                $r2 =  $row['a25'];
                                                if ($r1 == 0 or $r2 == 0) {
                                                  echo "0";
                                                } else {
                                                  $round1 = (($row['a24'] / $row['a25']) * 100);
                                                  echo round($round1, 2);
                                                } ?></td>
                                          </tr>
                                          <tr>
                                            <td>Percentage of cases on treatment achieved blood pressure control</td>
                                            <td><?php echo $row['a26']; ?></td>
                                            <td><?php echo $row['a27']; ?></td>
                                            <td><?php
                                                $r1 =  $row['a26'];
                                                $r2 =  $row['a27'];
                                                if ($r1 == 0 or $r2 == 0) {
                                                  echo "0";
                                                } else {
                                                  $round1 = (($row['a26'] / $row['a27']) * 100);
                                                  echo round($round1, 2);
                                                } ?></td>
                                          </tr>
                                          <tr>
                                            <td>Percentage of cases on treatment achieved blood sugar control</td>
                                            <td><?php echo $row['a28']; ?></td>
                                            <td><?php echo $row['a29']; ?></td>
                                            <td><?php
                                                $r1 =  $row['a28'];
                                                $r2 =  $row['a29'];
                                                if ($r1 == 0 or $r2 == 0) {
                                                  echo "0";
                                                } else {
                                                  $round1 = (($row['a28'] / $row['a29']) * 100);
                                                  echo round($round1, 2);
                                                } ?></td>
                                          </tr>
                                          <tr>
                                            <td>Percentage of cases screened positive for cancer underwent biopsy</td>
                                            <td><?php echo $row['a30']; ?></td>
                                            <td><?php echo $row['a31']; ?></td>
                                            <td><?php
                                                $r1 =  $row['a30'];
                                                $r2 =  $row['a31'];
                                                if ($r1 == 0 or $r2 == 0) {
                                                  echo "0";
                                                } else {
                                                  $round1 = (($row['a30'] / $row['a31']) * 100);
                                                  echo round($round1, 2);
                                                } ?></td>
                                          </tr>
                                          <tr>
                                            <td>Percentage of cancer cases underwent treatment for each cancer </td>
                                            <td><?php echo $row['a32']; ?></td>
                                            <td><?php echo $row['a31']; ?></td>
                                            <td><?php
                                                $r1 =  $row['a32'];
                                                $r2 =  $row['a31'];
                                                if ($r1 == 0 or $r2 == 0) {
                                                  echo "0";
                                                } else {
                                                  $round1 = (($row['a32'] / $row['a31']) * 100);
                                                  echo round($round1, 2);
                                                } ?></td>
                                          </tr>
                                          <tr>
                                            <td rowspan="3">The facility measures service quality
                                              indicators&nbsp;&nbsp;&nbsp;on monthly basis</td>
                                            <td>Client Satisfaction Score (Patients)-Sum of average satisfaction score
                                              of each respondent<br>(Average satisfaction score = sum total of scores
                                              of attributes/number of total attributes)</td>
                                            <td><?php echo $row['a34']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a34']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>Client Satisfaction Score (Community)- Sum of average satisfaction score
                                              of each respondent<br>(Average satisfaction score = sum total of scores
                                              of attributes/number of total attributes)</td>
                                            <td><?php echo $row['a35']; ?></td>
                                            <td>NA</td>
                                            <td><?php echo $row['a35']; ?></td>
                                          </tr>
                                          <tr>
                                            <td>Percentage of&nbsp;&nbsp;chronic cases who started treatment at
                                              PHC/above are still under treatment for last 3 months</td>
                                            <td><?php echo $row['a36']; ?></td>
                                            <td><?php echo $row['a37']; ?></td>
                                            <td><?php
                                                $r1 =  $row['a36'];
                                                $r2 =  $row['a37'];
                                                if ($r1 == 0 or $r2 == 0) {
                                                  echo "0";
                                                } else {
                                                  $round1 = (($row['a36'] / $row['a37']) * 100);
                                                  echo round($round1, 2);
                                                } ?></td>
                                          </tr>
                                          <tr>
                                            <td colspan="5"></td>
                                          </tr>
                                          <tr>
                                            <td colspan="5" style="background-color:#f1e8bc">
                                              <center>End Of Report for the month of <?php echo $date_conv; ?> </center>
                                            </td>
                                          </tr>
                                          <tr>
                                            <td colspan="5"></td>
                                          </tr>
                                        <?php
                                      }
                                    } else {
                                      echo "No Report Found";
                                    }
                                  }
                                  //==========================================
                                  elseif ($dept_id == 25) {
                                    $out_comerpt = "CALL outcome_report_test_25($dept_id, $Fa,$p)";
                                    $out_comerptquery = $con->query($out_comerpt);
                                    if ($out_comerptquery->num_rows > 0) {
                                        ?>
                                        <input type="button" value="Export" class="btn btn-success" onclick="exportToExcel('table3')" />
                                        <table class="table w-auto small  table-bordered" style="border-color:#FF5733" id="table3">
                                          <?php
                                          while ($row = mysqli_fetch_array($out_comerptquery)) {
                                          ?>

                                            <thead>
                                              <tr>
                                                <th colspan="5" style="background-color:#FF5733">
                                                  <center>Out Come Indicator for the month of
                                                    <?php
                                                    $date_conv = $row['month_in'];
                                                    //  $date_conv1 = date("F Y", strtotime($date_conv));

                                                    echo $date_conv;

                                                    ?></center>
                                                </th>
                                              </tr>
                                              <tr>
                                                <th colspan="1" style="background-color:#e9e510"><i
                                                    class="bi bi-arrow-down-circle-fill"
                                                    onclick="ExportToExcel('xlsx')"> </th>
                                                <th style="background-color:#e9e510">INDICATOR</th>
                                                <th style="background-color:#e9e510">NUMERATOR</th>
                                                <th style="background-color:#e9e510">DENOMINATOR</th>
                                                <th style="background-color:#e9e510">OUTCOME REPORT</th>
                                              </tr>
                                            </thead>
                                            <tbody>
                                              <tr>
                                                <td rowspan="7">The facility measures productivity
                                                  indicators&nbsp;&nbsp;services on monthly basis</td>
                                                <td>No. of OPD Cases(Pregnant mothers, neonate,&nbsp;&nbsp;infant, children,
                                                  adolescent, FP and CD )</td>
                                                <td><?php echo $row['a1']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a1']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>No. of follow up cases (repeat visit)-( Pregnant mothers,
                                                  neonate,&nbsp;&nbsp;infant, children, adolescent, FP and CD )</td>
                                                <td><?php echo $row['a2']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a2']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>No. of&nbsp;&nbsp;cases referred to higher centre( Pregnant mothers,
                                                  neonate,&nbsp;&nbsp;infant, children, adolescent, FP and CD )</td>
                                                <td><?php echo $row['a3']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a3']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>No. of Case specific OPD((Hypertension,Diebetes and Cancer)-NCD</td>
                                                <td><?php echo $row['a4']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a4']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>No. of&nbsp;&nbsp;cases referred to higher centre(Hypertension,Diebetes
                                                  and Cancer)-NCD</td>
                                                <td><?php echo $row['a5']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a5']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>No. of case specific follow up (Hypertension,Diebetes and Cancer)-NCD
                                                </td>
                                                <td><?php echo $row['a6']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a6']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>Total no of&nbsp;&nbsp;drop out cases following identification in
                                                  OPD(Hypertension,Diebetes and Cancer)-NCD</td>
                                                <td><?php echo $row['a7']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a7']; ?></td>
                                              </tr>
                                              <tr>
                                                <td rowspan="7">The facility measures efficiency indicators on monthly basis
                                                </td>
                                                <td>Percentage of women receiving all four ANCs</td>
                                                <td><?php echo $row['a8']; ?></td>
                                                <td><?php echo $row['a9']; ?></td>
                                                <td><?php
                                                    $r1 =  $row['a8'];
                                                    $r2 =  $row['a9'];
                                                    if ($r1 == 0 or $r2 == 0) {
                                                      echo "0";
                                                    } else {
                                                      $round1 = (($row['a8'] / $row['a9']) * 100);
                                                      echo round($round1, 2);
                                                    } ?></td>
                                              </tr>
                                              <tr>
                                                <td>Drop out rate for Pentavalent immunization</td>
                                                <td><?php echo ($row['a10'] - $row['a11']); ?></td>
                                                <td><?php echo $row['a10']; ?></td>

                                                <td><?php
                                                    $r1 =  $row['a10'];
                                                    $r2 =  $row['a11'];
                                                    if ($r1 == 0 or $r2 == 0) {
                                                      echo "0";
                                                    } else {
                                                      $round1 = (($row['a10'] / $row['a11']) * 100);
                                                      echo round($round1, 2);
                                                    } ?></td>
                                              </tr>
                                              <tr>
                                                <td>Drop out rate for NCDs</td>
                                                <td><?php echo $row['a12']; ?></td>
                                                <td><?php echo $row['a13']; ?></td>

                                                <td><?php
                                                    $r1 =  $row['a12'];
                                                    $r2 =  $row['a13'];
                                                    if ($r1 == 0 or $r2 == 0) {
                                                      echo "0";
                                                    } else {
                                                      $round1 = (($row['a12'] / $row['a13']) * 100);
                                                      echo round($round1, 2);
                                                    } ?></td>
                                              </tr>
                                              <tr>
                                                <td>No. of stock out days of essential medicines</td>
                                                <td><?php echo $row['a14']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a14']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>No. of stock out days of essential diagnostic test </td>
                                                <td><?php echo $row['a15']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a15']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>No. of Yoga session conducted in month</td>
                                                <td><?php echo $row['a16']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a16']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>No of VHNDs conducted (for vulnerable population)</td>
                                                <td><?php echo $row['a17']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a17']; ?></td>
                                              </tr>
                                              <tr>
                                                <td rowspan="10">The facility measures clinical care
                                                  indicators&nbsp;&nbsp;on monthly basis</td>
                                                <td>No. of high risk pregnancy identified during ANC</td>
                                                <td><?php echo $row['a18']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a18']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>No. of AEFI cases reported</td>
                                                <td><?php echo $row['a19']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a19']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>No. of Children with diarrhoea treated with ORS &amp; Zn</td>
                                                <td><?php echo $row['a20']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a20']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>Contraceptives acceptance rate</td>
                                                <td><?php echo $row['a21']; ?></td>
                                                <td><?php echo $row['a22']; ?></td>
                                                <td><?php
                                                    $r1 =  $row['a21'];
                                                    $r2 =  $row['a22'];
                                                    if ($r1 == 0 or $r2 == 0) {
                                                      echo "0";
                                                    } else {
                                                      $round1 = (($row['a21'] / $row['a22']) * 100);
                                                      echo round($round1, 2);
                                                    } ?></td>
                                              </tr>
                                              <tr>
                                                <td>No. of Anaemia cases treated successfully </td>
                                                <td><?php echo $row['a23']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a23']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>Treatment completion rate for Tuberculosis</td>
                                                <td><?php echo $row['a24']; ?></td>
                                                <td><?php echo $row['a25']; ?></td>
                                                <td><?php
                                                    $r1 =  $row['a24'];
                                                    $r2 =  $row['a25'];
                                                    if ($r1 == 0 or $r2 == 0) {
                                                      echo "0";
                                                    } else {
                                                      $round1 = (($row['a24'] / $row['a25']) * 100);
                                                      echo round($round1, 2);
                                                    } ?></td>
                                              </tr>
                                              <tr>
                                                <td>Percentage of cases on treatment achieved blood pressure control</td>
                                                <td><?php echo $row['a26']; ?></td>
                                                <td><?php echo $row['a27']; ?></td>
                                                <td><?php
                                                    $r1 =  $row['a26'];
                                                    $r2 =  $row['a27'];
                                                    if ($r1 == 0 or $r2 == 0) {
                                                      echo "0";
                                                    } else {
                                                      $round1 = (($row['a26'] / $row['a27']) * 100);
                                                      echo round($round1, 2);
                                                    } ?></td>
                                              </tr>
                                              <tr>
                                                <td>Percentage of cases on treatment achieved blood sugar control</td>
                                                <td><?php echo $row['a28']; ?></td>
                                                <td><?php echo $row['a29']; ?></td>
                                                <td><?php
                                                    $r1 =  $row['a28'];
                                                    $r2 =  $row['a29'];
                                                    if ($r1 == 0 or $r2 == 0) {
                                                      echo "0";
                                                    } else {
                                                      $round1 = (($row['a28'] / $row['a29']) * 100);
                                                      echo round($round1, 2);
                                                    } ?></td>
                                              </tr>
                                              <tr>
                                                <td>Percentage of cases screened positive for cancer underwent biopsy</td>
                                                <td><?php echo $row['a30']; ?></td>
                                                <td><?php echo $row['a31']; ?></td>
                                                <td><?php
                                                    $r1 =  $row['a30'];
                                                    $r2 =  $row['a31'];
                                                    if ($r1 == 0 or $r2 == 0) {
                                                      echo "0";
                                                    } else {
                                                      $round1 = (($row['a30'] / $row['a31']) * 100);
                                                      echo round($round1, 2);
                                                    } ?></td>
                                              </tr>
                                              <tr>
                                                <td>Percentage of cancer cases underwent treatment for each cancer </td>
                                                <td><?php echo $row['a32']; ?></td>
                                                <td><?php echo $row['a31']; ?></td>
                                                <td><?php
                                                    $r1 =  $row['a32'];
                                                    $r2 =  $row['a31'];
                                                    if ($r1 == 0 or $r2 == 0) {
                                                      echo "0";
                                                    } else {
                                                      $round1 = (($row['a32'] / $row['a31']) * 100);
                                                      echo round($round1, 2);
                                                    } ?></td>
                                              </tr>
                                              <tr>
                                                <td rowspan="3">The facility measures service quality
                                                  indicators&nbsp;&nbsp;&nbsp;on monthly basis</td>
                                                <td>Client Satisfaction Score (Patients)-Sum of average satisfaction score
                                                  of each respondent<br>(Average satisfaction score = sum total of scores
                                                  of attributes/number of total attributes)</td>
                                                <td><?php echo $row['a34']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a34']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>Client Satisfaction Score (Community)- Sum of average satisfaction score
                                                  of each respondent<br>(Average satisfaction score = sum total of scores
                                                  of attributes/number of total attributes)</td>
                                                <td><?php echo $row['a35']; ?></td>
                                                <td>NA</td>
                                                <td><?php echo $row['a35']; ?></td>
                                              </tr>
                                              <tr>
                                                <td>Percentage of&nbsp;&nbsp;chronic cases who started treatment at
                                                  PHC/above are still under treatment for last 3 months</td>
                                                <td><?php echo $row['a36']; ?></td>
                                                <td><?php echo $row['a37']; ?></td>
                                                <td><?php
                                                    $r1 =  $row['a36'];
                                                    $r2 =  $row['a37'];
                                                    if ($r1 == 0 or $r2 == 0) {
                                                      echo "0";
                                                    } else {
                                                      $round1 = (($row['a36'] / $row['a37']) * 100);
                                                      echo round($round1, 2);
                                                    } ?></td>
                                              </tr>
                                              <tr>
                                                <td colspan="5"></td>
                                              </tr>
                                              <tr>
                                                <td colspan="5" style="background-color:#f1e8bc">
                                                  <center>End Of Report for the month of <?php echo $date_conv; ?> </center>
                                                </td>
                                              </tr>
                                              <tr>
                                                <td colspan="5"></td>
                                              </tr>
                                            <?php
                                          }
                                        } else {
                                            ?>
                                            <p>
                                              <button addEventListener="function()" type="button" class="btn btn-danger"><?php echo "Sorry, No records found..!"; ?><i class="bi bi-check-circle"></i></button>
                                            </p>

                                          <?php

                                        }
                                      } elseif (($dept_id == 4) || in_array($dept_id, [1, 2, 3, 4, 5, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 23, 33, 40, 24, 26, 27])) {
                                        $fsid = $_SESSION['u_facilityid'];
                                        $out_comerpt = "CALL dh_outcomerpt($fsid,$dept_id)";
                                        $result = $con->query($out_comerpt);
                                        if ($result->num_rows > 0) {
                                          // Start the table
                                          ?>
                                            <input type="button" value="Export" class="btn btn-success" onclick="exportToExcel('table31')" />
                                            <table class="table w-auto small  table-bordered" style="border-color:#FF5733" id="table31">

                                              <?php
                                              // Fetch column names (month names) dynamically from the first row
                                              $columns = $result->fetch_fields();

                                              // Print table header (month names as columns)
                                              echo "<thead><tr><th>INDICATOR</th>";
                                              foreach ($columns as $column) {
                                                // Skip the first column, which is `out_come_hwcindi`
                                                if ($column->name != 'out_come_hwcindi') {
                                                  echo "<th>" . ucfirst(str_replace('_', ' ', $column->name)) . "</th>";
                                                }
                                              }
                                              echo "</tr></thead>";

                                              // Start table body and fetch data row by row
                                              echo "<tbody>";
                                              while ($row = $result->fetch_assoc()) {
                                                echo "<tr>";

                                                // Print the `out_come_hwcindi` (indicator)
                                                echo "<td>" . $row['out_come_hwcindi'] . "</td>";

                                                // Print each month's value for the indicator
                                                foreach ($columns as $column) {
                                                  // Skip the first column (`out_come_hwcindi`)
                                                  if ($column->name != 'out_come_hwcindi') {
                                                    echo "<td>" . (isset($row[$column->name]) ? $row[$column->name] : '-') . "</td>";
                                                  }
                                                }

                                                echo "</tr>";
                                              }
                                              echo "</tbody>";

                                              // End the table
                                              echo "</table>";
                                            }

                                            //================
                                            else {
                                              ?>
                                              <p>
                                                <button addEventListener="function()" type="button" class="btn btn-danger"><?php echo "Sorry, No records found..!"; ?><i class="bi bi-check-circle"></i></button>
                                              </p>

                                          <?php

                                            }
                                          }
                                        } elseif ($_POST["rt"] == 4) {

                                          ?>

                                          <br>

                                          <?php
                                          $_SESSION['FDepartment'] = $_SESSION['dept_id1'];
                                          //    $_SESSION['concern'] = $_POST['Concern'];
                                          $_SESSION['period'] = $_POST['Period'];
                                          //  $_SESSION['F_type']=$_POST["Facility_type"];
                                          //$_SESSION['Cn']=$_POST["Concern"];
                                          // $_SESSION['cy']=$_POST["category"];           
                                          //   $C = $_SESSION['concern'];
                                          $F = $_SESSION['FDepartment'];
                                          $Fa = $_SESSION['u_facilityid'];
                                          $p = $_SESSION['period'];
                                          if ($p == 0) {
                                          ?>
                                            <p>
                                              <button addEventListener="function()" type="button" class="btn btn-danger"><?php echo "Kindly Select Assessment Period..!"; ?><i class="bi bi-check-circle"></i></button>
                                            </p>

                                            <?php
                                          } else {
                                            // $ca= $_SESSION['cy'];
                                            $_SESSION['q1'] = "CALL moic_action_plan_view($Fa,$p,$F)";
                                            $_SESSION['q'] = mysqli_query($con, $_SESSION['q1']);
                                            $query = $_SESSION['q'];
                                            if ($query->num_rows > 0) {
                                            ?>

                                              <input type="button" value="Export" class="btn btn-success" onclick="exportToExcel('table4')" />
                                              <table class="table w-auto small  table-bordered" style="border-color:#FF5733" id="table4">

                                                <thead>
                                                  <tr>
                                                    <th colspan="10">
                                                      <center>Action Plan</center>
                                                    </th>

                                                  </tr>
                                                  <tr>

                                                    <th data-column-id="concern_subtype_chklist.c_subtype_Reference_No">Standard</th>
                                                    <th data-column-id="concern_subtype_chklist.Reference_No">Ref.</th>
                                                    <th data-column-id="concern_subtype_chklist.Measurable_Element">Mea.Element</th>
                                                    <th data-column-id="concern_subtype_chklist.Checkpoint">Checkpoint </th>
                                                    <th data-column-id="concern_subtype_chklist.Means_of_Verification">Ass.Methord</th>
                                                    <th data-column-id="concern_subtype_chklist.Means_of_Verification">Veri.Meth.</th>
                                                    <th data-column-id="chk_list_assessment.ass_compliance">Act.Plan</th>
                                                    <th data-column-id="chk_list_assessment.ass_compliance">Comp.</th>
                                                    <th data-column-id="chk_list_assessment.ass_compliance">Res.Person</th>
                                                    <th data-column-id="chk_list_assessment.ass_compliance">Date</th>
                                                  </tr>
                                                </thead>
                                                <?php
                                                while ($row = mysqli_fetch_array($query)) {
                                                ?>


                                                  <tbody>
                                                    <tr>

                                                      <td><?php echo $row['c_subtype_Reference_No_fk']; ?></td>
                                                      <td><?php echo $row['csqa_reference_id']; ?></td>
                                                      <td><?php echo $row['Measurable_Element']; ?></td>
                                                      <td><?php echo $row['Checkpoint']; ?></td>
                                                      <td><?php echo $row['Assessment_Method']; ?></td>
                                                      <td><?php echo $row['Means_of_Verification']; ?></td>
                                                      <td><?php echo $row['dept_action_plan']; ?></td>
                                                      <td><?php echo $row['ass_compliance']; ?></td>
                                                      <td><?php echo $row['dept_res']; ?></td>
                                                      <td><?php echo $row['dept_res_date']; ?></td>
                                                    </tr>
                                                  <?php }
                                              } else {
                                                  ?>
                                                  <p>
                                                    <button type="button" class="btn btn-warning">
                                                      <?php echo "Sorry No compliance..!"; ?><i class="bi bi-check-circle"></i>
                                                    </button>
                                                  </p>


                                                <?php  }
                                              mysqli_free_result($query);
                                              $con->next_result();
                                            }
                                          } elseif ($_POST["rt"] == 5 and $fat == 8 || 4) {
                                            if ($dept_id == 32) {
                                              /////////////KPIHWC///////////////////
                                              $out_comerpt = "CALL outcome_report_test($dept_id, $Fa,$p)";
                                            } elseif ($dept_id == 25) {
                                              $out_comerpt = "CALL outcome_report_test_25($dept_id, $Fa,$p)";
                                            } else {
                                              echo "Departent not mapped";
                                              exit;
                                            }
                                            $out_comerptquery = $con->query($out_comerpt);
                                            if ($out_comerptquery->num_rows > 0) {
                                                ?>
                                                <input type="button" value="Export" class="btn btn-success" onclick="exportToExcel('table3')" />
                                                <table class="table w-auto small  table-bordered" style="border-color:#FF5733" id="table3">
                                                  <?php
                                                  while ($row = mysqli_fetch_array($out_comerptquery)) {
                                                  ?>

                                                    <thead>
                                                      <tr>
                                                        <th colspan="5" style="background-color:#FF5733">
                                                          <center>KPI for the month of
                                                            <?php
                                                            $date_conv = $row['month_in'];
                                                            //  $date_conv1 = date("F Y", strtotime($date_conv));

                                                            echo $date_conv;

                                                            ?></center>
                                                        </th>
                                                      </tr>
                                                      <tr>

                                                        <th style="background-color:#e9e510">Type</th>
                                                        <th style="background-color:#e9e510">Indicators</th>
                                                        <th style="background-color:#e9e510">Numerator</th>
                                                        <th style="background-color:#e9e510">Denominator</th>
                                                        <th style="background-color:#e9e510">KPI Value</th>
                                                      </tr>
                                                    </thead>
                                                    <tbody>
                                                      <tr>
                                                        <td rowspan="3">Productivity</td>
                                                        <td>No. of OPD Cases per month</td>
                                                        <td><?php echo $row['a1']; ?></td>
                                                        <td>NA</td>
                                                        <td><?php echo $row['a1']; ?></td>
                                                      </tr>
                                                      <tr>

                                                        <td>No. of follow up cases (repeat visit) per month</td>
                                                        <td><?php echo $row['a2']; ?></td>
                                                        <td>NA</td>
                                                        <td><?php echo $row['a2']; ?></td>
                                                      </tr>
                                                      <tr>

                                                        <td>No. of drop out cases following start of the treatment</td>
                                                        <td><?php echo $row['a7']; ?></td>
                                                        <td>NA</td>
                                                        <td><?php echo $row['a7']; ?></td>
                                                      </tr>
                                                      <tr>
                                                        <td rowspan="3">Efficiency</td>
                                                        <td>Drop out rate for NCDs</td>
                                                        <td><?php echo $row['a12']; ?></td>
                                                        <td><?php echo $row['a13']; ?></td>
                                                        <td><?php
                                                            $r1 =  $row['a12'];
                                                            $r2 =  $row['a13'];
                                                            if ($r1 == 0 or $r2 == 0) {
                                                              echo "0";
                                                            } else {
                                                              $round1 = (($row['a12']  / $row['a13']) * 100);
                                                              echo round($round1, 2);
                                                            } ?></td>
                                                      </tr>
                                                      <tr>
                                                        <td>No. of stock out days of essential medicines (as per service Package)</td>
                                                        <td><?php echo $row['a14']; ?></td>
                                                        <td>NA</td>
                                                        <td><?php echo $row['a14']; ?></td>
                                                      </tr>
                                                      <tr>
                                                        <td>No of VHNDs conducted (for vulnerable population)</td>
                                                        <td><?php echo $row['a17']; ?></td>
                                                        <td>NA</td>
                                                        <td><?php echo $row['a17']; ?></td>
                                                      </tr>
                                                      <tr>
                                                        <td rowspan="4">Clinical Care Indicators</td>
                                                        <td>No. of high risk pregnancy identified during ANC</td>
                                                        <td><?php echo $row['a18']; ?></td>
                                                        <td>NA</td>
                                                        <td><?php echo $row['a18']; ?></td>
                                                      </tr>
                                                      <tr>
                                                        <td>No. of Children with diarrhoea treated with ORS & Zn</td>
                                                        <td><?php echo $row['a20']; ?></td>
                                                        <td>NA</td>
                                                        <td><?php echo $row['a20']; ?></td>
                                                      </tr>
                                                      <tr>
                                                        <td>No. of Anaemia cases treated successfully</td>
                                                        <td><?php echo $row['a23']; ?></td>
                                                        <td>NA</td>
                                                        <td><?php echo $row['a23']; ?></td>
                                                      </tr>
                                                      <tr>
                                                        <td>Percentage of cases on treatment achieved blood pressure & blood sugar control</td>
                                                        <td><?php $sum1 = $row['a26'] + $row['a28'];
                                                            echo $sum1;
                                                            ?>
                                                        </td>
                                                        <td><?php $sum2 = $row['a27'] + $row['a29'];
                                                            echo $sum2;
                                                            ?></td>
                                                        <td><?php
                                                            $r1 =  $row['a26'] + $row['a28'];
                                                            $r2 =  $row['a27'] + $row['a29'];
                                                            if ($r1 == 0 or $r2 == 0) {
                                                              echo "0";
                                                            } else {
                                                              $round1 = (($r1  / $r2) * 100);
                                                              echo round($round1, 2);
                                                            } ?></td>
                                                      </tr>
                                                      <tr>
                                                        <td rowspan="2">Service Quality</td>
                                                        <td>Client Satisfaction Score (Patients)</td>
                                                        <td><?php echo $row['a34']; ?></td>
                                                        <td>NA</td>
                                                        <td><?php echo $row['a34']; ?></td>
                                                      </tr>
                                                      <tr>

                                                        <td>Percentage of chronic cases who started treatment at PHC/above are still under treatment for last 3 months</td>
                                                        <td><?php echo $row['a36']; ?></td>
                                                        <td><?php echo $row['a37']; ?></td>
                                                        <td><?php
                                                            $r1 =  $row['a36'];
                                                            $r2 =  $row['a37'];
                                                            if ($r1 == 0 or $r2 == 0) {
                                                              echo "0";
                                                            } else {
                                                              $round1 = (($row['a36']  / $row['a37']) * 100);
                                                              echo round($round1, 2);
                                                            } ?></td>
                                                      </tr>
                                                      <tr>
                                                        <td colspan="5"></td>
                                                      </tr>
                                                      <tr>
                                                        <td colspan="5" style="background-color:#f1e8bc">
                                                          <center>End Of Report for the month of <?php echo $date_conv; ?> </center>
                                                        </td>
                                                      </tr>
                                                      <tr>
                                                        <td colspan="5"></td>
                                                      </tr>
                                                    <?php
                                                  }
                                                } else {
                                                    ?>
                                                    <p>
                                                      <button addEventListener="function()" type="button" class="btn btn-danger"><?php echo "Sorry, No records found..!"; ?><i class="bi bi-check-circle"></i></button>
                                                    </p>

                                                <?php
                                                }
                                              } else {
                                                echo "Department not mapped for KPI";
                                              }


                                              ///////////////////////////////////////////KPI HWC



                                                ?>
                                                    </tbody>
                                                </table>
                                              <?php } ?>
                                              <script src="assets/js/exportToExcel.js" defer></script>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<script>
  $(document).ready(function() {
    $("#Period1").on('change', function() {
      var Concernid = $('#Period1').val();
      $.ajax({
        method: "POST",
        cache: false,
        url: "response_m.php",
        data: {
          cid: Concernid,
        },
        datatype: "html",
        success: function(data) {
          $("#Concern1").html(data);
        },
        error: function(data) {}
      });
    });
  });
</script>
<!-- ======= Footer ======= -->

<?php include("assets/head/f.php"); ?>
</body>
<script>
  function getText(element) {
    var textHolder = element.options[element.selectedIndex].text
    document.getElementById("Department_name_hidden").value = textHolder;
  }
</script>
