<?php include("assets/head/h.php"); ?>
<div class="pcoded-main-container">
    <div class="pcoded-content">
        <div class="pagetitle mb-2">
            <h5 class="fw-bold text-primary mb-1">
                <i class="bi bi-bar-chart-line-fill me-2"></i>Key Performance Indicator
            </h5>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="post">
                  <?= csrf(); ?>
                    <div class="row align-items-end">
                        <div class="col-auto">
                            <label for="date1" class="form-label">Select Month</label>
                            <?php

                            $selected_month = date('Y-m');

                            if (isset($_POST['date1'])) {
                                $selected_month = trim((string)$_POST['date1']);
                            } elseif (isset($_POST['edit_month'])) {
                                $selected_month = trim((string)$_POST['edit_month']);
                            } elseif (isset($_POST['edit_date'])) {
                                $selected_month = trim((string)$_POST['edit_date']);
                            } elseif (isset($_SESSION['new_date1'])) {
                                $selected_month = $_SESSION['new_date1'];
                            }

                            ?>

                            <input
                                type="month"
                                class="form-control"
                                name="date1"
                                id="date1"
                                value="<?= $selected_month; ?>"
                                min="<?= date('Y-m', strtotime('-6 months')); ?>"
                                max="<?= date('Y-m'); ?>"
                                required>
                        </div>
                        <div class="col-auto">
                            <button type="submit" name="submit1" class="btn btn-primary">Fill Data</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php
        if (isset($_POST['submit1'])) {
            $new_date1 = date('Y-m', strtotime($_POST['date1']));
            $_SESSION['new_date1'] = $new_date1;

            if ($new_date1 == '1970-01') {
                echo "<div class='alert alert-danger'>Kindly select a valid month.</div>";
            } else {
                $fac_id = $_SESSION['u_facilityid'];
                $check_query = "SELECT COUNT(dh_kpi_id) AS total FROM dh_kpi_in WHERE dh_kpi_date = ? AND dh_kpi_fac_id = ?";
                $stmt1 = $con->prepare($check_query);
                $stmt1->bind_param("si", $new_date1, $fac_id);
                $stmt1->execute();
                $result1 = $stmt1->get_result();
                $row1 = $result1->fetch_assoc();

                if ((int)$row1['total'] > 0) {

                    echo "<div class='alert alert-warning d-flex justify-content-between align-items-center'>
        <div>
            KPI data for <b>" . e($new_date1) . "</b> already exists.
        </div>

        <form method='post'>
            <input type='hidden' 
                   name='csrf_token' 
                   value='" . e($_SESSION['csrf_token']) . "'>

            <input type='hidden' 
                   name='edit_month' 
                   value='" . e($new_date1) . "'>

            <button type='submit' 
                    name='load_edit'
                    class='btn btn-primary btn-sm'>
                <i class='bi bi-pencil-square'></i> View / Edit
            </button>
        </form>
      </div>";
                } else {
                    $q3 = mysqli_query($con, "SELECT * FROM dhkpi");
                    $q = 0;
                    $total = mysqli_num_rows($q3);

                    echo '<form method="post">';
              echo csrf(); 
                    while ($row = mysqli_fetch_array($q3)) {
                        $q++;
                        $readonly = ($row['dh_kpi_d'] === 'N/A') ? 'readonly' : '';
                        $isLast = ($q == $total);
                        $inputNumId = $q * 2 - 1;
                        $inputDenId = $q * 2;
                        $isYesNoSpecific = in_array($inputNumId, [43, 45]);

                        echo "<div class='card mb-3 outcome-card' id='card$q' style='" . ($q > 1 ? "display:none;" : "") . "'>
                        <div class='card-body'>
                            <h6 class='card-title text-primary'><i class='bi bi-bar-chart-fill me-2'></i>KPI Indicator: {$row['dh_kpitext']}</h6>
                            <div class='alert alert-primary py-2 px-3 small d-flex align-items-center'>
                                <i class='bi bi-info-circle-fill me-2'></i>
                                <div><strong>Expected:</strong> <span class='text-warning'><b>Numerator:</b></span> <b>{$row['dh_kpi_n']}</b>,  <span class='text-warning'><b>Denominator:</b></span> <b>{$row['dh_kpi_d']}</b></div>
                            </div>
                            <div class='row g-2'>";

                        if ($isYesNoSpecific) {
                            echo "<div class='col-md-4'>
                            <select class='form-control' name='input$inputNumId' id='input$inputNumId' onchange='calculateResult($q)'>
                                <option value=''>- Select -</option>
                                <option value='yes'>Yes</option>
                                <option value='no'>No</option>
                            </select>
                        </div>
                        <div class='col-md-4'>
                            <input type='text' class='form-control' readonly value='N/A'>
                        </div>";
                        } else {
                            echo "<div class='col-md-4'>
                            <input type='number' class='form-control' name='input$inputNumId' id='input$inputNumId' placeholder='Numerator' oninput='calculateResult($q)'>
                        </div>
                        <div class='col-md-4'>
                            <input type='number' class='form-control' name='input$inputDenId' id='input$inputDenId' placeholder='Denominator' oninput='calculateResult($q)' $readonly>
                        </div>";
                        }

                        echo "<div class='col-md-4'>
                        <input type='text' class='form-control' name='result$q' id='result$q' readonly placeholder='Result'>
                    </div>
                    </div>
                    <input type='hidden' name='out_come_id$q' value='{$row['dh_kpi_id']}'>
                    <div class='mt-3 d-flex justify-content-between'>";
                        echo ($q > 1) ? "<button type='button' class='btn btn-secondary btn-sm' onclick='showPreviousCard($q)'><i class='bi bi-arrow-left'></i> Back</button>" : "<div></div>";
                        echo $isLast
                            ? "<button type='submit' name='postsubmit3' class='btn btn-primary btn-sm'>Submit All</button>"
                            : "<button type='button' class='btn btn-success btn-sm' onclick='showNextCard($q)'> Next <i class='bi bi-arrow-right'></i></button>";
                        echo "</div></div></div>";
                    }
                    echo "</form>";
                    echo "<script src='assets/calculationjs/dhkpi.js'></script>";
                }
            }
        }
        ?>
        <?php

        if (isset($_POST['load_edit'])) {

            $date1 = $_POST['edit_month'];
            $fac_id = $_SESSION['u_facilityid'];

            $sql = "
SELECT
    k.dh_kpi_id,
    k.dh_kpitext,
    k.dh_kpi_n,
    k.dh_kpi_d,

    COALESCE(i.dh_kpi_value,0) AS dh_kpi_value,
    COALESCE(i.dh_kpi_num,0) AS dh_kpi_num,
    COALESCE(i.dh_kpi_deno,0) AS dh_kpi_deno

FROM dhkpi k

LEFT JOIN dh_kpi_in i
ON k.dh_kpi_id = i.dh_kpi_id
AND i.dh_kpi_date = '$date1'
AND i.dh_kpi_fac_id = '$fac_id'
";

            $result = mysqli_query($con, $sql);

            $q = 0;
            $total = mysqli_num_rows($result);

            echo "<form method='post' novalidate>";

            echo "<input
type='hidden'
name='csrf_token'
value='" . e($_SESSION['csrf_token']) . "'>";

            echo "<input type='hidden'
name='edit_date'
value='$date1'>";

            while ($row = mysqli_fetch_assoc($result)) {
                $q++;

                $readonly = ($row['dh_kpi_d'] == 'N/A') ? 'readonly' : '';

                $inputNumId = $q * 2 - 1;
                $inputDenId = $q * 2;

                $isYesNoSpecific = in_array($inputNumId, [43, 45]);

                $value = $row['dh_kpi_value'] ?? '';

                $num = $row['dh_kpi_num'] ?? '';

                $den = $row['dh_kpi_deno'] ?? '';

                /*
Fallback for old records
*/
                if (($num == '' || $num == 0) && ($den == '' || $den == 0)) {
                    if (str_contains($value, '/')) {
                        $parts = explode('/', $value);

                        $num = trim($parts[0]);

                        if (isset($parts[1])) {
                            $denparts = explode('=', $parts[1]);

                            $den = trim($denparts[0]);
                        }
                    }
                }

                echo "

<div class='card mb-3'
id='card$q'
style='" . ($q > 1 ? "display:none;" : "") . "'>

<div class='card-body'>

<h6 class='card-title text-primary'>
<i class='bi bi-bar-chart-fill me-2'></i>
KPI Indicator: {$row['dh_kpitext']}
</h6>

<div class='alert alert-primary py-2'>

<strong>Expected:</strong>

<b>Numerator:</b>
{$row['dh_kpi_n']}

<b>Denominator:</b>
{$row['dh_kpi_d']}

</div>

<div class='row g-2'>";

                if ($isYesNoSpecific) {

                    echo "

<div class='col-md-4'>

<select
class='form-control'
name='input$inputNumId'
id='input$inputNumId'
onchange='calculateResult($q)'>

<option value=''>Select</option>

<option value='yes'
" . ($num == 'yes' ? 'selected' : '') . ">

Yes

</option>

<option value='no'
" . ($num == 'no' ? 'selected' : '') . ">

No

</option>

</select>

</div>

<div class='col-md-4'>

<input
type='text'
readonly
class='form-control'
value='N/A'>

</div>";
                } else {

                    echo "

<div class='col-md-4'>

<input
type='number'
class='form-control'
name='input$inputNumId'
id='input$inputNumId'
value='$num'
placeholder='Numerator'
oninput='calculateResult($q)'>

</div>

<div class='col-md-4'>

<input
type='number'
class='form-control'
name='input$inputDenId'
id='input$inputDenId'
value='$den'
placeholder='Denominator'
oninput='calculateResult($q)'
$readonly>

</div>";
                }

                echo "

<div class='col-md-4'>

<input
type='text'
class='form-control'
name='result$q'
id='result$q'
value='$value'
readonly>

</div>

</div>

<input
type='hidden'
name='kpi$q'
value='{$row['dh_kpi_id']}'>

<div class='mt-3 d-flex justify-content-between'>";

                echo ($q > 1)
                    ? "<button type='button'
class='btn btn-secondary'
onclick='showPreviousCard($q)'>

Back

</button>"
                    : "<div></div>";

                echo ($q == $total)

                    ? "<button
type='submit'
name='updatekpi'
class='btn btn-success'>

Update All

</button>"

                    : "<button
type='button'
class='btn btn-primary'
onclick='showNextCard($q)'>

Next

</button>";

                echo "
</div>
</div>
</div>";
            }
            echo "</form>";

            echo "<script src='assets/calculationjs/dhkpi.js'></script>";

            echo "
<script>
document.addEventListener('DOMContentLoaded', function () {

    for(let i=1;i<=$total;i++)
    {
        calculateResult(i);
    }

});
</script>";
        }
        ?>


        <?php

        if (isset($_POST['updatekpi'])) {

            $date1 = $_POST['edit_date'];

            $fac_id = $_SESSION['u_facilityid'];

            $result = mysqli_query(
                $con,
                "SELECT dh_kpi_id FROM dhkpi"
            );

            $i = 0;

            while ($row = mysqli_fetch_assoc($result)) {

                $i++;

                $kpiid = $_POST['kpi' . $i];

                $value = $_POST['result' . $i] ?? '';

                $num = $_POST['input' . ($i * 2 - 1)] ?? 0;

                $den = $_POST['input' . ($i * 2)] ?? 0;

                $stmt = $con->prepare(
                    "CALL update_dhkpi(?,?,?,?,?,?)"
                );

                $stmt->bind_param(
                    "issdsi",
                    $kpiid,
                    $value,
                    $num,
                    $den,
                    $date1,
                    $fac_id
                );

                if (!$stmt->execute()) {
                    echo mysqli_error($con);
                }

                $stmt->close();

                while (mysqli_next_result($con));
            }

            echo "
<div class='alert alert-success'>
KPI updated successfully
</div>";
        }
        ?>
        <?php
        if (isset($_POST['postsubmit3'])) {
            $date1 = $_SESSION['new_date1'];
            $fac_id = $_SESSION['u_facilityid'];

            $result = mysqli_query($con, "SELECT dh_kpi_id FROM dhkpi");
            $success = true;
            $errorMessages = [];
            $index = 0;

            while ($row = mysqli_fetch_assoc($result)) {
                $index++;
                $kpi_id = $row['dh_kpi_id'];
                $key = "result$index";
                $value = $_POST[$key] ?? 0;

                $num = $_POST['input' . ($index * 2 - 1)] ?? 0;

                $den = $_POST['input' . ($index * 2)] ?? 0;

                if ($value !== null) {

                    $stmt = $con->prepare(
                        "CALL insert_dhkpi(?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->bind_param(
                        "idddsi",
                        $kpi_id,
                        $value,
                        $num,
                        $den,
                        $date1,
                        $fac_id
                    );

                    if (!$stmt->execute()) {

                        $success = false;

                        $errorMessages[] =
                            "Error inserting for KPI ID: {$kpi_id}";
                    }

                    $stmt->close();

                    while (mysqli_next_result($con));
                }
            }

            echo $success ? "<div class='alert alert-success'>Outcome Values inserted successfully!</div>"
                : "<div class='alert alert-danger'>There was an error while inserting data.</div>";

            if (!empty($errorMessages)) {
                echo "<div class='alert alert-danger'>" . implode("<br>", $errorMessages) . "</div>";
            }
        }
        ?>
    </div>
</div>

<script>
    function showNextCard(current) {
        const inputNum = document.getElementById('input' + (current * 2 - 1));
        const inputDen = document.getElementById('input' + (current * 2));
        let isValid = true;

        if (!inputNum.value.trim()) {
            inputNum.focus();
            inputNum.classList.add('is-invalid');
            isValid = false;
        } else {
            inputNum.classList.remove('is-invalid');
        }

        if (inputDen && !inputDen.hasAttribute('readonly') && !inputDen.value.trim()) {
            inputDen.focus();
            inputDen.classList.add('is-invalid');
            isValid = false;
        } else if (inputDen) {
            inputDen.classList.remove('is-invalid');
        }

        if (!isValid) return;

        const currentCard = document.getElementById('card' + current);
        const nextCard = document.getElementById('card' + (current + 1));
        if (currentCard) currentCard.style.display = 'none';
        if (nextCard) nextCard.style.display = 'block';
    }

    function showPreviousCard(current) {
        const currentCard = document.getElementById('card' + current);
        const prevCard = document.getElementById('card' + (current - 1));
        if (currentCard) currentCard.style.display = 'none';
        if (prevCard) prevCard.style.display = 'block';
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<?php include("assets/head/f.php"); ?>
