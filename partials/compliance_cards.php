<?php
// components/compliance_cards.php
$query = "CALL count_zero($Fa, $dept_id, $p, $fat)";
$result = mysqli_query($con, $query);

$compliance_data = ['Non' => 0, 'Partially' => 0, 'Fully' => 0];
if ($result && $row = mysqli_fetch_assoc($result)) {
    $compliance_data['Non'] = (int)$row['z'];
    $compliance_data['Partially'] = (int)$row['o'];
    $compliance_data['Fully'] = (int)$row['t'];
}
mysqli_free_result($result);
$con->next_result();

$compliance_cards = [
    ["label" => "Non", "file" => "assets/export/export_deprt_indicators_non.php", "color" => "text-danger", "value" => $compliance_data['Non']],
    ["label" => "Partially", "file" => "assets/export/export_deprt_indicators_partially.php", "color" => "text-warning", "value" => $compliance_data['Partially']],
    ["label" => "Fully", "file" => "assets/export/export_deprt_indicators_full_comp.php", "color" => "text-success", "value" => $compliance_data['Fully']],
];
?>

<?php foreach ($compliance_cards as $card): ?>
    <div class="col-lg-6 col-xl-4">
  <div class="card flat-card widget-primary-card">
    <div class="row-table">
      <div class="col-sm-3 card-body d-flex align-items-center justify-content-center">
        <i class="feather icon-star-on <?= $card['color'] ?>"></i>
      </div>
      <div class="col-sm-9 py-3">
        <h4><?= $card['value'] ?></h4>
        <h6><?= $card['label'] ?> Compliance</h6>
       <small>
  <a href="<?= $card['file'] ?>" class="text-white text-decoration-none">
    <i class="bi bi-arrow-down-circle-fill me-1"></i>
     </a>
</small>

      </div>
    </div>
  </div>
</div>

<?php endforeach; ?>
