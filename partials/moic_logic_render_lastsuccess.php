<?php
if (isset($_POST['submit1'])) {
  $_SESSION['FDepartment'] = $_SESSION['dept_id1'];
  $_SESSION['concern'] = $_POST['Concern'];
  $_SESSION['period'] = $_POST['Period'];

  $C = $_SESSION['concern'];
  $F = $_SESSION['FDepartment'];
  $Fa = $_SESSION['u_facilityid'];
  $p = $_SESSION['period'];

  if ($p == 0) {
    echo '<div class="alert alert-danger mt-3">Kindly select assessment period.</div>';
    return;
  }

  $_SESSION['q1'] = "CALL moic_action_plan($Fa,$p,$F,$C)";
  $query = $con->query($_SESSION['q1']);

  if ($query && $query->num_rows > 0) {
    while ($row = mysqli_fetch_array($query)) {
?>
      <!-- Action Plan Form Card -->
      <div class="card shadow-sm border rounded p-3 bg-light bg-gradient small mb-4">
        <h5 class="text-center mb-3 fw-bold text-primary">
          <i class="bi bi-pencil-square me-2 text-success"></i>Formulate Your Action Plan
        </h5>

        <!-- Info Row -->
        <div class="row g-3 mb-3">
          <!-- Standard -->
          <div class="col-md-3">
            <div class="card border-top border-2 border-info h-100">
              <div class="card-body p-2">
                <div class="fw-semibold text-info"><i class="bi bi-list-task me-2"></i>Standard</div>
                <div class="text-dark"><?php echo $row['c_subtype_Reference_No_fk']; ?></div>
              </div>
            </div>
          </div>
          <!-- Reference Number -->
          <div class="col-md-3">
            <div class="card border-top border-2 border-info h-100">
              <div class="card-body p-2">
                <div class="fw-semibold text-info"><i class="bi bi-hash me-2"></i>Reference Number</div>
                <div class="text-dark"><?php echo $row['csqa_reference_id']; ?></div>
              </div>
            </div>
          </div>
          <!-- Checkpoint -->
          <div class="col-md-3">
            <div class="card border-top border-2 border-info h-100">
              <div class="card-body p-2">
                <div class="fw-semibold text-info"><i class="bi bi-check2-circle me-2"></i>Checkpoint</div>
                <div class="text-dark"><?php echo $row['Checkpoint']; ?></div>
              </div>
            </div>
          </div>
          <!-- Assessment Method -->
          <div class="col-md-3">
            <div class="card border-top border-2 border-info h-100">
              <div class="card-body p-2">
                <div class="fw-semibold text-info"><i class="bi bi-journals me-2"></i>Assessment Method</div>
                <div class="text-dark"><?php echo $row['Assessment_Method']; ?></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Measurable Element -->
        <div class="card border-top border-2 border-info mb-3">
          <div class="card-body p-2">
            <div class="fw-semibold text-secondary mb-1">
              <i class="bi bi-clipboard-check me-2 text-info"></i>Measurable Element
            </div>
            <div class="text-dark"><?php echo $row['Measurable_Element']; ?></div>
          </div>
        </div>

        <!-- Means of Verification -->
        <div class="card border-top border-2 border-info mb-3">
          <div class="card-body p-2">
            <div class="fw-semibold text-secondary mb-1">
              <i class="bi bi-search me-2 text-info"></i>Means of Verification
            </div>
            <div class="text-dark"><?php echo $row['Means_of_Verification']; ?></div>
          </div>
        </div>

        <!-- Action Plan Form -->
        <form method="post" action="#">
          <input type="hidden" name="csqa_id1" value="<?php echo $_SESSION['q1']; ?>">
          <input type="hidden" name="csqa_id" value="<?php echo $row['ass_id']; ?>">

          <div class="card border-top border-2 border-warning mb-3">
            <div class="card-body p-2">
              <div class="row g-3 align-items-start">
                <div class="col-md-3">
                  <label class="form-label fw-semibold text-warning">Priority</label>
                  <select class="form-control form-control-sm" name="Priority">
                    <option value="0">Low</option>
                    <option value="1">Medium</option>
                    <option value="2">High</option>
                  </select>
                </div>

                <div class="col-md-3">
                  <label class="form-label fw-semibold text-warning">Dept. Review</label>
                  <select class="form-control form-control-sm dept-review" name="f">
                    <option value="0">--Select--</option>
                    <option value="1">Achievable</option>
                    <option value="2">Non-achievable</option>
                  </select>
                </div>

                <div class="conditional-section col-md-3">
                  <label class="form-label fw-semibold text-warning">Responsible Nodal</label>
                  <input type="text" name="res" class="form-control form-control-sm">
                </div>
                <div class="conditional-section col-md-3">
                  <label class="form-label fw-semibold text-warning">Time Period</label>
                  <input type="date" name="todate" class="form-control form-control-sm">
                </div>

                <div class="col-md-12 conditional-section">
                  <div class="card border-top border-2 border-danger my-2">
                    <div class="card-body p-2">
                      <div class="fw-semibold text-danger mb-1">
                        <i class="bi bi-lightbulb me-2 text-danger"></i>Suggested Action Plan
                      </div>
                      <div class="text-dark"><?php echo $row['action_plan']; ?></div>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold text-primary">Your Action Plan</label>
                    <textarea name="comment" class="form-control form-control-sm" rows="3"><?php echo $row['action_plan']; ?></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Submit Button -->
          <div class="text-end">
            <button type="submit" name="submit2" class="btn btn-primary btn-sm px-4">
              <i class="bi bi-save me-1"></i>Save & Next
            </button>
          </div>
        </form>
      </div>

  <?php
    }
   mysqli_free_result($query);
    $con->next_result();
  } else {
    echo '<div class="alert alert-warning mt-3"><i class="bi bi-exclamation-circle me-1"></i>No compliance found for action plan.</div>';
  }
} elseif (isset($_POST['submit2'])) {
  echo '<div class="alert alert-success mt-3"><i class="bi bi-check-circle-fill me-2"></i>Action plan saved successfully!</div>';
  ?>
  <!-- post submit2 -->
  <?php
  // include('conn.php');
  $q = $_POST['csqa_id1'];
  $id = $_POST['csqa_id'];
  $p = $_SESSION['period'];
  $pri = $_POST['Priority'];
  $moic_compliance = $_POST['f'];

  if ($moic_compliance == 2) {
    $ass_id = $id;
    $facid = $_SESSION['u_facilityid'];
    $insertfeedback = "call moic_nonach(2,$ass_id,$facid,$p,$pri)";
    $queryinsert = $con->query($insertfeedback);
    if ($queryinsert) {
      // echo '<button type="button" class="btn btn-success">Compliance status updated!</button>';

  ?>
      <p>
        <button addEventListener="function()" type="button" class="btn btn-success"><?php echo "Compliance action plane status updated..!"; ?><i class="bi bi-check-circle"></i></button>
      </p>
    <?php

    }
    //  mysqli_free_result($queryinsert);
    // $con->next_result();
  } else {
    $com = $_POST['comment'];
    $date = $_POST['todate'];
    $dres = $_POST['res'];
    if ($dres == '' || $date == '' || $dres == '0' || empty($com)) {
      echo "Please Select responsible Person/Nodal and Date";
    ?>

    <?php exit;
    }
    ?>
    <?php
    $com = $_POST['comment'];
    $p = $_SESSION['period'];
    $ass_id = $_POST['csqa_id'];
    $facid = $_SESSION['u_facilityid'];
    $insertfeedback1 = "call moic_achiv(1,$ass_id, $facid,$p,'$dres','$date','$com',$pri)";
    $queryinsert1 = $con->query($insertfeedback1);
    // $queryinsert1 = mysqli_query($con, $insertfeedback1);
    if ($queryinsert1) {
      // echo '<button type="button" class="btn btn-success">Compliance status updated!</button>';

    ?>
      <p>
        <button addEventListener="function()" type="button" class="btn btn-success"><?php echo "Compliance action plane status updated..!"; ?><i class="bi bi-check-circle"></i></button>
      </p>
    <?php }
    // mysqli_free_result($queryinsert1);
    // $con->next_result();
  }
  //$query = mysqli_query($con, $q);
  $queryd = $con->query($q);
  if ($queryd->num_rows > 0) {
    while ($row = mysqli_fetch_array($queryd)) {
    ?>
      <!-- Action Plan Form Card -->
      <div class="card shadow-sm border rounded p-3 bg-light bg-gradient small mb-4">
        <h5 class="text-center mb-3 fw-bold text-primary">
          <i class="bi bi-pencil-square me-2 text-success"></i>Formulate Your Action Plan
        </h5>

        <!-- Info Row -->
        <div class="row g-3 mb-3">
          <!-- Standard -->
          <div class="col-md-3">
            <div class="card border-top border-2 border-info h-100">
              <div class="card-body p-2">
                <div class="fw-semibold text-info"><i class="bi bi-list-task me-2"></i>Standard</div>
                <div class="text-dark"><?php echo $row['c_subtype_Reference_No_fk']; ?></div>
              </div>
            </div>
          </div>
          <!-- Reference Number -->
          <div class="col-md-3">
            <div class="card border-top border-2 border-info h-100">
              <div class="card-body p-2">
                <div class="fw-semibold text-info"><i class="bi bi-hash me-2"></i>Reference Number</div>
                <div class="text-dark"><?php echo $row['csqa_reference_id']; ?></div>
              </div>
            </div>
          </div>
          <!-- Checkpoint -->
          <div class="col-md-3">
            <div class="card border-top border-2 border-info h-100">
              <div class="card-body p-2">
                <div class="fw-semibold text-info"><i class="bi bi-check2-circle me-2"></i>Checkpoint</div>
                <div class="text-dark"><?php echo $row['Checkpoint']; ?></div>
              </div>
            </div>
          </div>
          <!-- Assessment Method -->
          <div class="col-md-3">
            <div class="card border-top border-2 border-info h-100">
              <div class="card-body p-2">
                <div class="fw-semibold text-info"><i class="bi bi-journals me-2"></i>Assessment Method</div>
                <div class="text-dark"><?php echo $row['Assessment_Method']; ?></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Measurable Element -->
        <div class="card border-top border-2 border-info mb-3">
          <div class="card-body p-2">
            <div class="fw-semibold text-secondary mb-1">
              <i class="bi bi-clipboard-check me-2 text-info"></i>Measurable Element
            </div>
            <div class="text-dark"><?php echo $row['Measurable_Element']; ?></div>
          </div>
        </div>

        <!-- Means of Verification -->
        <div class="card border-top border-2 border-info mb-3">
          <div class="card-body p-2">
            <div class="fw-semibold text-secondary mb-1">
              <i class="bi bi-search me-2 text-info"></i>Means of Verification
            </div>
            <div class="text-dark"><?php echo $row['Means_of_Verification']; ?></div>
          </div>
        </div>

        <!-- Action Plan Form -->
        <form method="post" action="#">
          <input type="hidden" name="csqa_id1" value="<?php echo $_SESSION['q1']; ?>">
          <input type="hidden" name="csqa_id" value="<?php echo $row['ass_id']; ?>">

          <div class="card border-top border-2 border-warning mb-3">
            <div class="card-body p-2">
              <div class="row g-3 align-items-start">
                <div class="col-md-3">
                  <label class="form-label fw-semibold text-warning">Priority</label>
                  <select class="form-control form-control-sm" name="Priority">
                    <option value="0">Low</option>
                    <option value="1">Medium</option>
                    <option value="2">High</option>
                  </select>
                </div>

                <div class="col-md-3">
                  <label class="form-label fw-semibold text-warning">Dept. Review</label>
                  <select class="form-control form-control-sm dept-review" name="f">
                    <option value="0">--Select--</option>
                    <option value="1">Achievable</option>
                    <option value="2">Non-achievable</option>
                  </select>
                </div>

                <div class="conditional-section col-md-3">
                  <label class="form-label fw-semibold text-warning">Responsible Nodal</label>
                  <input type="text" name="res" class="form-control form-control-sm">
                </div>
                <div class="conditional-section col-md-3">
                  <label class="form-label fw-semibold text-warning">Time Period</label>
                  <input type="date" name="todate" class="form-control form-control-sm">
                </div>

                <div class="col-md-12 conditional-section">
                  <div class="card border-top border-2 border-danger my-2">
                    <div class="card-body p-2">
                      <div class="fw-semibold text-danger mb-1">
                        <i class="bi bi-lightbulb me-2 text-danger"></i>Suggested Action Plan
                      </div>
                      <div class="text-dark"><?php echo $row['action_plan']; ?></div>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold text-primary">Your Action Plan</label>
                    <textarea name="comment" class="form-control form-control-sm" rows="3">action plan</textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="text-end">
            <button type="submit" name="submit2" class="btn btn-primary btn-sm px-4">
              <i class="bi bi-save me-1"></i>Save & Next
            </button>
          </div>
        </form>
      </div>

    <?php
    }
  }
  mysqli_free_result($queryd);
  $con->next_result();
} elseif (isset($_POST['submit3'])) {
  $F = $_SESSION['dept_id1'];
  $Fa = $_SESSION['u_facilityid'];
    $p = $_POST['Period'];

  if ($p == 0) {
    echo '<div class="alert alert-danger mt-3">Please select an assessment period.</div>';
    return;
  }

  $query = $con->query("CALL moic_action_plan_view($Fa,$p,$F)");
  if ($query && $query->num_rows > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
    ?>
    <button class="btn btn-success mb-3" onclick="exportToExcel()">Download as Excel</button>
      <div class="card shadow-sm mb-3 border-start border-3 border-info">
        <div class="card-header fw-bold">
    Assessment Action Plan Summary
  </div>
  <div class="card-body small">

    <!-- Header Row -->
    <div class="row fw-bold text-white bg-primary border border-dark">
      <div class="col-md-1 p-1 border-end border-dark">Standard</div>
      <div class="col-md-1 p-1 border-end border-dark">Ref No</div>
      <div class="col-md-2 p-2 border-end border-dark">Measurable Element</div>
      <div class="col-md-2 p-2 border-end border-dark">Checkpoint</div>
      <div class="col-md-1 p-1 border-end border-dark">Means of Verification</div>
      <div class="col-md-1 p-1 border-end border-dark">Compliance</div>
      <div class="col-md-2 p-2 border-end border-dark">Action Plan</div>
      <div class="col-md-1 p-1">Responsible</div>
      <div class="col-md-1 p-1">Date</div>
    </div>

    <!-- Data Row -->
    <div class="row border-start border-end border-bottom border-dark">
      <div class="col-md-1 p-1 border-end border-dark"><?php echo $row['c_subtype_Reference_No_fk']; ?></div>
      <div class="col-md-1 p-1 border-end border-dark"><?php echo $row['csqa_reference_id']; ?></div>
      <div class="col-md-2 p-2 border-end border-dark"><?php echo $row['Measurable_Element']; ?></div>
      <div class="col-md-2 p-2 border-end border-dark"><?php echo $row['Checkpoint']; ?></div>
      <div class="col-md-1 p-1 border-end border-dark"><?php echo $row['Means_of_Verification']; ?></div>
      <div class="col-md-1 p-1 border-end border-dark"><?php echo $row['ass_compliance']; ?></div>
      <div class="col-md-2 p-2 border-end border-dark"><?php echo $row['dept_action_plan']; ?></div>
      <div class="col-md-1 p-1"><?php echo $row['dept_res']; ?></div>
      <div class="col-md-1 p-1"><?php echo $row['dept_res_date']; ?></div>
    </div>

  </div>
</div>

<!-- Hidden Table for Export -->
<table id="exportTable" class="d-none">
  <thead>
    <tr>
      <th>Standard</th>
      <th>Ref No</th>
      <th>Measurable Element</th>
      <th>Checkpoint</th>
      <th>Means of Verification</th>
      <th>Compliance</th>
      <th>Action Plan</th>
      <th>Responsible</th>
      <th>Date</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><?php echo $row['c_subtype_Reference_No_fk']; ?></td>
      <td><?php echo $row['csqa_reference_id']; ?></td>
      <td><?php echo $row['Measurable_Element']; ?></td>
      <td><?php echo $row['Checkpoint']; ?></td>
      <td><?php echo $row['Means_of_Verification']; ?></td>
      <td><?php echo $row['ass_compliance']; ?></td>
      <td><?php echo $row['dept_action_plan']; ?></td>
      <td><?php echo $row['dept_res']; ?></td>
      <td><?php echo $row['dept_res_date']; ?></td>
    </tr>
  </tbody>
</table>

<!-- SheetJS Library -->
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

<!-- Export Function -->
<script>
function exportToExcel() {
    const table = document.getElementById('exportTable');
    const workbook = XLSX.utils.table_to_book(table, { sheet: "Action Plan" });
    XLSX.writeFile(workbook, 'Assessment_Action_Plan_Summary.xlsx');
}
</script>

<?php
    }
   mysqli_free_result($query);
    $con->next_result();
  } else {
    echo '<div class="alert alert-warning mt-3"><i class="bi bi-info-circle me-1"></i>No filled action plan available.</div>';
  }
}
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    const deptReviewElements = document.querySelectorAll('.dept-review');

    deptReviewElements.forEach(function(dropdown) {
      const form = dropdown.closest('form');
      const conditionalSections = form.querySelectorAll('.conditional-section');

      function updateVisibility(value) {
        conditionalSections.forEach(function(section) {
          section.style.display = value === '2' ? 'none' : 'block';
        });
      }

      updateVisibility(dropdown.value);

      dropdown.addEventListener('change', function() {
        updateVisibility(this.value);
      });
    });
  });
</script>
