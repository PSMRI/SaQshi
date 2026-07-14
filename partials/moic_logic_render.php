<?php
if (!function_exists('clean_sp_buffers_safe')) {
    function clean_sp_buffers_safe($con): void
    {
        if (!($con instanceof mysqli)) {
            return;
        }

        while ($con->more_results()) {
            if (!$con->next_result()) {
                break;
            }

            $extraResult = $con->store_result();

            if ($extraResult instanceof mysqli_result) {
                $extraResult->free();
            }
        }
    }
}

if (isset($_POST['submit1'])) {
    $_SESSION['FDepartment'] = (int) ($_SESSION['dept_id1'] ?? 0);
    $_SESSION['concern'] = (int) ($_POST['Concern'] ?? 0);
    $_SESSION['period'] = (int) ($_POST['Period'] ?? 0);

    $C  = (int) $_SESSION['concern'];
    $F  = (int) $_SESSION['FDepartment'];
    $Fa = (int) ($_SESSION['u_facilityid'] ?? 0);
    $p  = (int) $_SESSION['period'];

    if ($p <= 0) {
        echo '<div class="alert alert-danger mt-3">Kindly select assessment period.</div>';
        return;
    }

    if ($C <= 0) {
        echo '<div class="alert alert-info mt-3">
                <i class="bi bi-info-circle me-1"></i>
                No Action Plan is available for the selected assessment cycle.
              </div>';
        return;
    }

    clean_sp_buffers_safe($con);

    $_SESSION['q1'] = "CALL moic_action_plan($Fa,$p,$F,$C)";
    $query = $con->query($_SESSION['q1']);

    if ($query && $query->num_rows > 0) {
        while ($row = mysqli_fetch_array($query)) {
?>
<!-- ===== Action Plan Card Start ===== -->
<div class="card shadow-sm border rounded p-3 bg-light bg-gradient small mb-4">
    <h5 class="text-center mb-3 fw-bold text-primary">
        <i class="bi bi-pencil-square me-2 text-success"></i>Formulate Your Action Plan
    </h5>

    <!-- Info Row -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-top border-2 border-info h-100">
                <div class="card-body p-2">
                    <div class="fw-semibold text-info"><i class="bi bi-list-task me-2"></i>Standard</div>
                    <div class="text-dark"><?= $row['c_subtype_Reference_No_fk']; ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-top border-2 border-info h-100">
                <div class="card-body p-2">
                    <div class="fw-semibold text-info"><i class="bi bi-hash me-2"></i>Reference Number</div>
                    <div class="text-dark"><?= $row['csqa_reference_id']; ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-top border-2 border-info h-100">
                <div class="card-body p-2">
                    <div class="fw-semibold text-info"><i class="bi bi-check2-circle me-2"></i>Checkpoint</div>
                    <div class="text-dark"><?= $row['Checkpoint']; ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-top border-2 border-info h-100">
                <div class="card-body p-2">
                    <div class="fw-semibold text-info"><i class="bi bi-journals me-2"></i>Assessment Method</div>
                    <div class="text-dark"><?= $row['Assessment_Method']; ?></div>
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
            <div class="text-dark"><?= $row['Measurable_Element']; ?></div>
        </div>
    </div>

    <!-- Means of Verification -->
    <div class="card border-top border-2 border-info mb-3">
        <div class="card-body p-2">
            <div class="fw-semibold text-secondary mb-1">
                <i class="bi bi-search me-2 text-info"></i>Means of Verification
            </div>
            <div class="text-dark"><?= $row['Means_of_Verification']; ?></div>
        </div>
    </div>

    <!-- Action Form -->
    <form method="post" action="#" class="actionForm">
      <?= csrf(); ?>
        <input type="hidden" name="csqa_id1" value="<?= $_SESSION['q1']; ?>">
        <input type="hidden" name="csqa_id"  value="<?= $row['ass_id']; ?>">

        <div class="card border-top border-2 border-warning mb-3">
            <div class="card-body p-2">
                <div class="row g-3">

                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-warning">Priority</label>
                        <select name="Priority" class="form-control form-control-sm">
                            <option value="0">Low</option>
                            <option value="1">Medium</option>
                            <option value="2">High</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-warning">Dept. Review</label>
                        <select name="f" class="form-control form-control-sm dept-review">
                            <option value="3">--Select--</option>
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
                                <div class="text-dark"><?= $row['action_plan']; ?></div>
                            </div>
                        </div>

                        <label class="form-label fw-semibold text-primary">Your Action Plan</label>
                        <textarea name="comment" class="form-control form-control-sm" rows="3"><?= $row['action_plan']; ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="text-end">
            <button type="submit" name="submit2" class="btn btn-primary btn-sm px-4 saveBtn">
                <i class="bi bi-save me-1"></i>Save & Next
            </button>
        </div>

    </form>
</div>
<!-- ===== Action Plan Card End ===== -->

<?php
        }
    } else {
        echo '<div class="alert alert-info mt-3">
                <i class="bi bi-info-circle me-1"></i>
                No pending Action Plan is available for the selected assessment cycle.
              </div>';
    }

    if ($query instanceof mysqli_result) {
        $query->free();
    }

    clean_sp_buffers_safe($con);

} elseif (isset($_POST['submit2'])) {
  //echo '<div class="alert alert-success mt-3"><i class="bi bi-check-circle-fill me-2"></i>Action plan saved successfully!</div>';
  ?>
  <!-- post submit2 -->
  <?php
  // include('conn.php');
  $q = $_SESSION['q1'] ?? '';
  $id = (int) ($_POST['csqa_id'] ?? 0);
  $p = (int) ($_SESSION['period'] ?? 0);
  $pri = (int) ($_POST['Priority'] ?? 0);
  $moic_compliance = (int) ($_POST['f'] ?? 0);

  if ($q === '' || $id <= 0 || $p <= 0) {
      echo '<div class="alert alert-danger mt-3">
              Invalid Action Plan request. Please select the assessment cycle again.
            </div>';
      return;
  }

  if ($moic_compliance == 2) {
    $ass_id = $id;
    $facid = $_SESSION['u_facilityid'];
    $insertfeedback = "CALL moic_nonach(2,$ass_id,$facid,$p,$pri)";
    $queryinsert = $con->query($insertfeedback);

    if (!$queryinsert) {
        throw new Exception("Unable to update non-achievable Action Plan: " . $con->error);
    }

    if ($queryinsert instanceof mysqli_result) {
        $queryinsert->free();
    }

    clean_sp_buffers_safe($con);

    echo '<div class="alert alert-success mt-3">
            <i class="bi bi-check-circle-fill me-2"></i>
            Action Plan status updated successfully.
          </div>';
  } else {
    $com  = trim($_POST['comment'] ?? '');
    $date = trim($_POST['todate'] ?? '');
    $dres = trim($_POST['res'] ?? '');

    if ($dres === '' || $date === '' || $dres === '0' || $com === '') {
        echo '<div class="alert alert-warning mt-3">
                Please select the responsible person/nodal officer, date, and Action Plan.
              </div>';
        return;
    }

    $p      = (int) ($_SESSION['period'] ?? 0);
    $ass_id = (int) ($_POST['csqa_id'] ?? 0);
    $facid  = (int) ($_SESSION['u_facilityid'] ?? 0);

    $dresEsc = $con->real_escape_string($dres);
    $dateEsc = $con->real_escape_string($date);
    $comEsc  = $con->real_escape_string($com);

    $insertfeedback1 = "CALL moic_achiv(
        1,
        $ass_id,
        $facid,
        $p,
        '$dresEsc',
        '$dateEsc',
        '$comEsc',
        $pri
    )";

    $queryinsert1 = $con->query($insertfeedback1);

    if (!$queryinsert1) {
        throw new Exception(
            "Unable to save achievable Action Plan: " . $con->error
        );
    }

    if ($queryinsert1 instanceof mysqli_result) {
        $queryinsert1->free();
    }

    clean_sp_buffers_safe($con);
  }

  clean_sp_buffers_safe($con);

  $queryd = $con->query($q);

  if (!$queryd) {
      throw new Exception("Unable to load the next Action Plan: " . $con->error);
  }

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
      <form method="post" action="#" class="actionForm">
        <?= csrf(); ?>
          <input type="hidden" name="csqa_id1" value="<?php echo $_SESSION['q1']; ?>">
          <input type="hidden" name="csqa_id" value="<?php echo $row['ass_id']; ?>">

          <div class="card border-top border-2 border-warning mb-3">
            <div class="card-body p-2">
              <div class="row g-3 align-items-start">
                <div class="col-md-3">
                  <label class="form-label fw-semibold text-warning">Priority</label>
                  <select class="form-control form-control-sm" name="Priority">
                    <option value="3">Low</option>
                    <option value="1">Medium</option>
                    <option value="2">High</option>
                  </select>
                </div>

                <div class="col-md-3">
                  <label class="form-label fw-semibold text-warning">Dept. Review</label>
                  <select class="form-control form-control-sm dept-review" name="f">
                    <option value="3">--Select--</option>
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
           <button type="submit" name="submit2" class="btn btn-primary btn-sm px-4 saveBtn">
              <i class="bi bi-save me-1"></i>Save & Next
            </button>
          </div>
        </form>
      </div>

    <?php
    }
  } else {
      echo '<div class="alert alert-success mt-3">
              <i class="bi bi-check-circle-fill me-2"></i>
              Action Plan completed successfully. No pending Action Plan remains for the selected assessment cycle.
            </div>';
  }

  if ($queryd instanceof mysqli_result) {
      $queryd->free();
  }

  clean_sp_buffers_safe($con);

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
  } else {
    echo '<div class="alert alert-warning mt-3"><i class="bi bi-info-circle me-1"></i>No filled action plan available.</div>';
  }

  if ($query instanceof mysqli_result) {
      $query->free();
  }

  clean_sp_buffers_safe($con);
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // Hide/Show conditional inputs on dropdown change
    document.addEventListener("change", function(e) {
        if (e.target.classList.contains("dept-review")) {

            let form = e.target.closest("form");
            let sections = form.querySelectorAll(".conditional-section");

            (e.target.value === "2")
                ? sections.forEach(s => s.style.display = "none")
                : sections.forEach(s => s.style.display = "block");
        }
    });

    // Form submit validation
    document.addEventListener("submit", function(e) {

        const form = e.target;
        if (!form.classList.contains("actionForm")) return;

        const btn = document.activeElement;
        if (!btn.classList.contains("saveBtn")) return;

        const review = form.querySelector("select[name='f']").value;
        const responsible = form.querySelector("input[name='res']").value.trim();
        const date = form.querySelector("input[name='todate']").value.trim();
        const comment = form.querySelector("textarea[name='comment']").value.trim();

        // Review not selected
        if (review === "3" || review === "0") {
            e.preventDefault();
            Swal.fire("Dept. Review Required", "Select Achievable or Non-Achievable.", "warning");
            return;
        }

        // Achievable requires mandatory fields
        if (review === "1") {
            if (responsible === "" || date === "" || comment === "") {
                e.preventDefault();
                Swal.fire("Incomplete Details", "Please fill Responsible Person, Date and Action Plan.", "warning");
                return;
            }
        }
    });

});
</script>