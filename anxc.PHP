<?php
include("assets/head/h.php");
$facid = $_SESSION['u_facilityid'] ?? null;
?>

<div class="pcoded-main-container">
  <div class="pcoded-content">
    <div class="pagetitle mb-3 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold text-primary">
        <i class="bi bi-bar-chart-fill me-2"></i>Annexure C - Indicator Entry
      </h5>
    </div>

    <!-- Date selection -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="post" action="">
          <?= csrf(); ?>
          <div class="row align-items-end g-2">
            <div class="col-auto">
              <label for="date1" class="form-label">Select Month</label>
              <input type="month" class="form-control" name="date1" id="date1"
                value="<?= date('Y-m') ?>"
                min="<?= date('Y-m', strtotime('-6 months')) ?>"
                max="<?= date('Y-m') ?>" required>
            </div>
            <div class="col-auto">
              <label class="d-block">&nbsp;</label>
              <button type="submit" name="submit1" class="btn btn-primary">Proceed</button>
            </div>
          </div>
        </form>
      </div>
    </div>

<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit1'])) {
    $month = date('Y/m', strtotime($_POST['date1']));

    // Check for duplicate entry
    $checkQuery = "SELECT COUNT(*) as cnt FROM anexc_in WHERE anexc_date = ? AND anexc_fac_id = ?";
    $stmt = $con->prepare($checkQuery);
    $stmt->bind_param("si", $month, $facid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if ($row['cnt'] > 0) {
        echo "<div class='alert alert-warning'>Data for <strong>$month</strong> already exists for your facility.</div>";
    } else {
        $indicators = $con->query("SELECT anexcid, anexc_indicators FROM anexc");

        if ($indicators->num_rows > 0) {
            echo "<form method='post' id='anexcForm'>";
            echo csrf();
            echo "<input type='hidden' name='final_submit' value='1'>";
            echo "<input type='hidden' name='month' value='$month'>";
            $counter = 1;
            $total = $indicators->num_rows;

            while ($row = $indicators->fetch_assoc()) {
                $visible = $counter === 1 ? "" : "style='display:none'";
                echo "
                <div class='card shadow-lg border-0 mb-4 animate__animated animate__fadeIn anexc-card' id='card-$counter' $visible>
                  <div class='card-header bg-primary text-white fw-semibold rounded-top'>
                    <i class='bi bi-clipboard-check-fill me-2'></i> Indicator $counter of $total
                  </div>
                  <div class='card-body'>
                    <p class='fs-5 text-dark mb-4'>
                      <i class='bi bi-arrow-right-circle me-2 text-success'></i>{$row['anexc_indicators']}
                    </p>

                    <input type='hidden' name='anexcid[]' value='{$row['anexcid']}'>

                   <div class='row g-3 align-items-end'>
  <div class='col-md-6'>
    <label for='response-$counter' class='form-label'>Response <span class='text-danger'>*</span></label>
    <select class='form-control' name='anexc_values[]' id='response-$counter' required>
      <option value=''>Select your response</option>
      <option value='Yes'>Yes</option>
      <option value='No'>No</option>
    </select>
  </div>

  <div class='col-md-6'>
    <label for='remarks-$counter' class='form-label'>Remarks <span class='text-danger'></span></label>
    <textarea class='form-control' name='remarks[]' id='remarks-$counter' rows='2'  placeholder='Enter remarks here'></textarea>
  </div>
</div>

                    <div class='d-flex justify-content-between mt-4'>";
                if ($counter > 1) {
                    echo "<button type='button' class='btn btn-outline-secondary' onclick='showPrev($counter)'>
                            <i class='bi bi-arrow-left-circle me-1'></i> Back
                          </button>";
                } else {
                    echo "<div></div>";
                }

                if ($counter < $total) {
                    echo "<button type='button' class='btn btn-outline-primary' onclick='showNext($counter)'>
                            Next <i class='bi bi-arrow-right-circle ms-1'></i>
                          </button>";
                } else {
                    echo "<button type='submit' class='btn btn-success'>
                            <i class='bi bi-check-circle-fill me-1'></i> Submit All
                          </button>";
                }

                echo "</div>
                  </div>
                </div>";

                $counter++;
            }

            echo "</form>";
        } else {
            echo "<div class='alert alert-info'>No indicators found.</div>";
        }
    }
}

// Final submission handler
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['final_submit'])) {
    $month = $_POST['month'];

    // Prevent duplicate on manual resubmission
    $checkQuery = "SELECT COUNT(*) as cnt FROM anexc_in WHERE anexc_date = ? AND anexc_fac_id = ?";
    $stmt = $con->prepare($checkQuery);
    $stmt->bind_param("si", $month, $facid);
    $stmt->execute();
    $res = $stmt->get_result();
    $existing = $res->fetch_assoc();

    if ($existing['cnt'] > 0) {
        echo "<div class='alert alert-danger'>Submission rejected. Data already exists for <strong>$month</strong>.</div>";
    } else {
        $anexcid = $_POST['anexcid'];
        $values = $_POST['anexc_values'];
        $remarks = $_POST['remarks'];
        $success = true;

        for ($i = 0; $i < count($anexcid); $i++) {
            $stmt = $con->prepare("INSERT INTO anexc_in (anexcid, anexc_values, anexc_date, anexc_fac_id, remarks) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issis", $anexcid[$i], $values[$i], $month, $facid, $remarks[$i]);
            if (!$stmt->execute()) {
                $success = false;
            }
        }

        echo $success
          ? "<div class='alert alert-success'>All indicators saved successfully for <strong>$month</strong>.</div>"
          : "<div class='alert alert-danger'>An error occurred while saving data. Please try again.</div>";
    }
}
?>

  </div>
</div>

<!-- JS for navigation and validation -->
<script>
  function showNext(current) {
    const card = document.getElementById("card-" + current);
    const select = card.querySelector("select");
    const textarea = card.querySelector("textarea");

    if (!select || !select.value) {
      alert("Please select Yes or No before proceeding.");
      return;
    }

    //if (!textarea || !textarea.value.trim()) {
     // alert("Please enter remarks before proceeding.");
     // return;
   // }

    card.style.display = "none";
    document.getElementById("card-" + (current + 1)).style.display = "block";
  }

  function showPrev(current) {
    document.getElementById("card-" + current).style.display = "none";
    document.getElementById("card-" + (current - 1)).style.display = "block";
  }
</script>

<?php include("assets/head/f.php"); ?>