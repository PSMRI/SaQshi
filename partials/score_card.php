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
        color: #555;
    }
    .score-value {
        font-size: 38px;
        font-weight: 700;
        margin: 0;
        line-height: 1;
    }
    .score-label {
        font-size: 15px;
        margin-top: 4px;
        color: #555;
    }
    .download-btn {
        background: #e9ecef;
        padding: 6px 12px;
        border-radius: 6px;
        display: inline-block;
        margin-top: 10px;
        color: #333 !important;
        font-size: 14px;
        transition: 0.2s;
    }
    .download-btn:hover {
        background: #d7dce1;
        text-decoration: none;
    }
</style>

<div class="col-lg-6 col-xl-4 mb-3">
    <div class="score-card-light">

        <div class="d-flex align-items-center">
            <!-- Icon -->
            <div class="score-icon-box me-3">
                <i class="feather icon-activity <?= $percentage_class ?>"></i>
            </div>

            <!-- Score Text -->
            <div>
                <div class="score-value <?= $percentage_class ?>">
                    <?= $score_percentage ?>%
                </div>
                <div class="score-label">Overall Score</div>
            </div>
        </div>

        <!-- Download Button -->
        <a href="assets/export/export_deprt_score_card.php" class="download-btn">
            <i class="bi bi-arrow-down-circle me-1"></i> Download Score Card
        </a>

    </div>
</div>



