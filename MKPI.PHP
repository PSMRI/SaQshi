<?php
include("assets/head/h.php");
?>

<div class="pcoded-main-container">
  <div class="pcoded-content">
    <div class="pagetitle mb-3 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold text-primary">
        <i class="bi bi-bar-chart-fill me-2"></i>MusQan KPI
      </h5>
    </div>

    <div class="row">
      <div class="col-sm-12">
        <!-- Date and Dept Selection Form -->
        <div class="card">
          <div class="card-body">
            <form enctype="multipart/form-data" method="post" action="#">
              <?php
              $date_value = isset($_POST['date1']) ? $_POST['date1'] : (isset($_SESSION['new_date1']) ? $_SESSION['new_date1'] : '');
              $selected_dept = isset($_POST['Facility_Department']) ? $_POST['Facility_Department'] : (isset($_SESSION['dept_values']) ? $_SESSION['dept_values'] : '');
              ?>
              <div class="row align-items-end flex-nowrap g-2">
                <div class="col-auto">              
                  <input type="date" class="form-control-sm" name="date1" id="date1"
                    value="<?php echo isset($_POST['date1']) ? $_POST['date1'] : (isset($_SESSION['new_date1']) ? $_SESSION['new_date1'] : date('Y-m-d')); ?>"
                    min="<?php echo date('Y-m-d', strtotime('-6 months')); ?>"
                    max="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-auto">
                  
                  <select class="form-control form-control-sm" id="Facility_Department" name="Facility_Department">
                    <option value=" ">Select Dept.</option>
                    <?php
                    $query = "SELECT fac_dept_id, dept_name FROM fac_department WHERE fac_dept_id IN (7, 23, 6, 5)";
                    $result = $con->query($query);
                    if ($result && $result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                        $is_selected = $row['fac_dept_id'] == $selected_dept ? "selected" : "";
                        echo "<option value='{$row['fac_dept_id']}' $is_selected>{$row['dept_name']}</option>";
                      }
                    }
                    ?>
                  </select>
                </div>
                <div class="col-auto">
                  <label class="d-block">&nbsp;</label>
                  <button type="submit" name="submit1" class="btn btn-primary">Fill Data</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- KPI Entry Section -->
    <?php
    if (isset($_POST['submit1'])) {
      $new_date1 = date('Y/m', strtotime($_POST['date1']));
      $dept_no = $_POST['Facility_Department'];

      if (!$new_date1 || $new_date1 == '1970/01') {
        echo "<div class='alert alert-danger'>Kindly select a valid date.</div>";
      } elseif (!$dept_no) {
        echo "<div class='alert alert-danger'>Kindly select a department.</div>";
      } else {
        $_SESSION['new_date1'] = $new_date1;
        $_SESSION['dept_values'] = $dept_no;
        $fac_id = $_SESSION['u_facilityid'];
        $check_query = "SELECT COUNT(mkpi_date) AS total FROM mkpi_in WHERE mkpi_date = ? AND mkpi_dept_id = ? and mkpi_fac_id= ?";
        $stmt1 = $con->prepare($check_query);
        $stmt1->bind_param("sii", $new_date1, $dept_no, $fac_id);
        $stmt1->execute();
        $result1 = $stmt1->get_result();
        $row = $result1->fetch_assoc();

        if ($row['total'] > 0) {
          echo "<div class='alert alert-danger'>The values for this month <b>$new_date1</b> have already been recorded.</div>";
        } else {
          $query = "SELECT kpi_id, kpi_text FROM mkpi WHERE kpi_dept = ?";
          $stmt2 = $con->prepare($query);
          $stmt2->bind_param("i", $dept_no);
          $stmt2->execute();
          $result = $stmt2->get_result();

          if ($result->num_rows > 0) {
            echo "<form method='post' action='#' id='kpiForm'>";
            $counter = 1;
            $totalKPIs = $result->num_rows;
            while ($row = $result->fetch_assoc()) {
              echo "<div class='card kpi-card mb-3' id='kpi-card-$counter' style='" . ($counter == 1 ? "" : "display:none;") . "'>
                      <div class='card-body'>
                      
                        
                        <p class='d-flex align-items-center text-success mb-3'>
                          <i class='bi bi-bar-chart-fill me-2'></i>
                          MusQan Key Performance Indicator $counter of $totalKPIs: {$row['kpi_text']}
                        </p>
                        
                        <input type='number' name='v[]' value='0' required class='form-control mb-3'>
                        <input type='hidden' name='kpi_id[]' value='{$row['kpi_id']}'>
                        <div class='d-flex justify-content-between'>";
              if ($counter > 1) {
                echo "<button type='button' class='btn btn-secondary' onclick='showPrev($counter)'>Back</button>";
              } else {
                echo "<div></div>";
              }

              if ($counter < $totalKPIs) {
                echo "<button type='button' class='btn btn-primary' onclick='showNext($counter)'>Next</button>";
              } else {
                echo "<button type='submit' name='submit2' class='btn btn-success'>Submit All</button>";
              }

              echo "</div>
                      </div>
                    </div>";
              $counter++;
            }
            echo "</form>";
          } else {
            echo "<div class='alert alert-warning'>No KPI data found for the selected department.</div>";
          }
        }
      }
    }

    // Save Data Logic
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit2'])) {
      if (isset($_SESSION['new_date1'], $_SESSION['dept_values'], $_POST['v'], $_POST['kpi_id'])) {
        $v_values = $_POST['v'];
        $kpi_ids = $_POST['kpi_id'];
        $new_date = $_SESSION['new_date1'];
        $new_dept_values = $_SESSION['dept_values'];
        $fac_id1 = $_SESSION['u_facilityid'];
        $success = true;

        for ($i = 0; $i < count($v_values); $i++) {
          $v_value = (int)$v_values[$i];
          $kpi_id = (int)$kpi_ids[$i];

          $stmt = $con->prepare("INSERT INTO mkpi_in (mkpi_id, mkpi_value, mkpi_date, mkpi_dept_id, mkpi_fac_id) VALUES (?, ?, ?, ?, ?)");
          $stmt->bind_param("iisii", $kpi_id, $v_value, $new_date, $new_dept_values, $fac_id1);

          if (!$stmt->execute()) {
            $success = false;
          }
          $stmt->close();
        }

        echo $success ?
          "<div class='alert alert-success'>KPI Values saved successfully!</div>" :
          "<div class='alert alert-danger'>There was an error while saving  data. Please try again.</div>";
      } else {
        echo "<div class='alert alert-danger'>Error: Missing form data.</div>";
      }
    }
    ?>

  </div>

  <!-- JS for Next / Back -->
  <script>
    function showNext(current) {
      document.getElementById('kpi-card-' + current).style.display = 'none';
      document.getElementById('kpi-card-' + (current + 1)).style.display = 'block';
    }

    function showPrev(current) {
      document.getElementById('kpi-card-' + current).style.display = 'none';
      document.getElementById('kpi-card-' + (current - 1)).style.display = 'block';
    }
  </script>

  <?php include("assets/head/f.php"); ?>
