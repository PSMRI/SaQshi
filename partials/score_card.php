<?php
// components/score_card.php
if (!isset($dept_id)) {
    $dept_id = $_SESSION['dept_id1'] ?? 0;
    $Fa = $_SESSION['u_facilityid'] ?? 0;
    $p = $_SESSION['assperiod'] ?? 0;
    $fat = $_SESSION['f_type_id'] ?? 0;
}

$score_percentage = 0;
$percentage_class = 'text-secondary';

$query = "CALL overall_dept_percentage($dept_id, $Fa, $p, $fat)";
$result = mysqli_query($con, $query);

if ($result && $row = mysqli_fetch_assoc($result)) {
    $obtained = (float) $row['obtained'];
    $total = (float) $row['total'];

    if ($total > 0) {
        $score_percentage = round(($obtained / $total) * 100, 2);

        if ($score_percentage >= 70) {
            $percentage_class = 'text-success';
        } elseif ($score_percentage >= 65) {
            $percentage_class = 'text-warning';
        } else {
            $percentage_class = 'text-danger';
        }
    }
}
mysqli_free_result($result);
$con->next_result();
?>

<div class="col-lg-4">
  <div class="card flat-card widget-primary-card">
    <div class="row-table">
      <div class="col-sm-3 card-body d-flex align-items-center justify-content-center">
        <i class="feather icon-activity <?= $percentage_class ?>"></i>
      </div>
      <div class="col-sm-9 py-3">
        <h4 class="<?= $percentage_class ?>"><?= $score_percentage ?>%</h4>
        <h6>Overall Score</h6>
        <small>
          <i class="bi bi-arrow-down-circle-fill me-1"></i>
          <a href="assets/export/export_deprt_score_card.php">Download Data</a>
        </small>
      </div>
    </div>
  </div>
</div>

