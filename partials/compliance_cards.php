<style>
    .score-card-light {
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 3px 10px rgba(0,0,0,0.12);
        padding: 18px;
        transition: 0.2s;
    }
    .score-card-light:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.18);
    }
    .score-icon-box {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: #f4f6f9;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 32px;
    }
    .download-btn-sm {
        background: #e9ecef;
        padding: 6px 10px;
        border-radius: 6px;
        display: inline-block;
        color: #333 !important;
        font-size: 13px;
        transition: 0.2s;
        margin-top: 6px;
    }
    .download-btn-sm:hover {
        background: #d7dce1;
        text-decoration: none;
    }
</style>


<?php
// components/compliance_cards.php
$query = "CALL count_zero($Fa, $dept_id, $p, $fat)";
$result = mysqli_query($con, $query);

$compliance_data = ['Non' => 0, 'Partially' => 0, 'Fully' => 0];

if (!$result) {
    echo "<pre>SQL Error: " . mysqli_error($con) . "\nQuery: $query</pre>";
} else {
    if ($row = mysqli_fetch_assoc($result)) {
        $compliance_data['Non']       = (int)$row['z'];
        $compliance_data['Partially'] = (int)$row['o'];
        $compliance_data['Fully']     = (int)$row['t'];
    }

    mysqli_free_result($result);

    while (mysqli_more_results($con) && mysqli_next_result($con)) {
        if ($extraResult = mysqli_store_result($con)) {
            mysqli_free_result($extraResult);
        }
    }
}
$compliance_cards = [
    ["label" => "Non", "file" => "assets/export/export_deprt_indicators_non.php", "color" => "text-danger", "value" => $compliance_data['Non']],
    ["label" => "Partially", "file" => "assets/export/export_deprt_indicators_partially.php", "color" => "text-warning", "value" => $compliance_data['Partially']],
    ["label" => "Fully", "file" => "assets/export/export_deprt_indicators_full_comp.php", "color" => "text-success", "value" => $compliance_data['Fully']],
];
?>

<?php foreach ($compliance_cards as $card): ?>
    <div class="col-lg-6 col-xl-4 mb-3">

        <div class="score-card-light">

            <div class="d-flex align-items-center">

                <!-- Icon -->
                <div class="score-icon-box me-3">
                    <i class="feather icon-star-on <?= $card['color'] ?>"></i>
                </div>

                <!-- Content -->
                <div>
                    <h3 class="<?= $card['color'] ?> mb-1"><?= $card['value'] ?></h3>
                    <div class="text-muted" style="font-size:14px;">
                        <?= $card['label'] ?> Compliance
                    </div>

                    <a href="<?= $card['file'] ?>" class="download-btn-sm">
                        <i class="bi bi-arrow-down-circle me-1"></i> Download
                    </a>
                </div>

            </div>

        </div>

    </div>
<?php endforeach; ?>

