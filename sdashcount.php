<?php
include("assets/head/h.php");

$currentMonth = date('Y-m');

/* =====================================================
   TOTAL COMPLETED ASSESSMENTS
===================================================== */

$sql1 = "

SELECT
    COUNT(DISTINCT ass_period_id) AS total_assessment

FROM state_dash_view

WHERE
   
    ass_completed IS NOT NULL
    AND TRIM(ass_completed) <> ''

    AND MONTH(
        STR_TO_DATE(
            TRIM(REPLACE(ass_completed,'/','-')),
            '%d-%m-%Y'
        )
    ) = MONTH(CURDATE())

    AND YEAR(
        STR_TO_DATE(
            TRIM(REPLACE(ass_completed,'/','-')),
            '%d-%m-%Y'
        )
    ) = YEAR(CURDATE())

ORDER BY
    STR_TO_DATE(
        TRIM(REPLACE(ass_completed,'/','-')),
        '%d-%m-%Y'
    ) DESC

";

$res1 = mysqli_query($con, $sql1);

if (!$res1) {
    die(mysqli_error($con));
}

$row1 = mysqli_fetch_assoc($res1);

$totalAssessments =
    $row1['total_assessment'] ?? 0;

/* =====================================================
   COMPLETED ASSESSMENTS DETAILS
===================================================== */

$sql2 = "

SELECT

    Dist_Name,
    Block_Name,
    fac_name,
    facilities_type,
    ass_name,
    ass_completed

FROM state_dash_view

WHERE
    ass_completed IS NOT NULL
    AND TRIM(ass_completed) <> ''

    AND MONTH(
        STR_TO_DATE(
            TRIM(REPLACE(ass_completed,'/','-')),
            '%d-%m-%Y'
        )
    ) = MONTH(CURDATE())

    AND YEAR(
        STR_TO_DATE(
            TRIM(REPLACE(ass_completed,'/','-')),
            '%d-%m-%Y'
        )
    ) = YEAR(CURDATE())

ORDER BY
    STR_TO_DATE(
        TRIM(REPLACE(ass_completed,'/','-')),
        '%d-%m-%Y'
    ) DESC
";

$res2 = mysqli_query($con, $sql2);

if (!$res2) {
    die(mysqli_error($con));
}

/* =====================================================
   OUTCOME REPORT FACILITY COUNT
===================================================== */

$sql3 = "

SELECT COUNT(*) AS total_outcome

FROM
(
    SELECT

        f.fac_id

    FROM outcome_values_in o

    JOIN facilities f
    ON f.fac_id = o.institute_id

    JOIN fac_department d
    ON d.fac_dept_id = o.dept_id

    WHERE

    DATE_FORMAT(
        STR_TO_DATE(
            CONCAT(
                REPLACE(o.month_in,'/','-'),
                '-01'
            ),
            '%Y-%m-%d'
        ),
        '%Y-%m'
    ) = '$currentMonth'

    GROUP BY f.fac_id

) x

";

$res3 = mysqli_query($con, $sql3);

if (!$res3) {
    die(mysqli_error($con));
}

$row3 = mysqli_fetch_assoc($res3);

$totalOutcome =
    $row3['total_outcome'] ?? 0;

/* =====================================================
   ACTION PLAN FACILITY COUNTS
===================================================== */

$sql4 = "

SELECT

    COUNT(
        CASE
            WHEN total_action_plan > 0
            THEN fac_name
        END
    ) AS Total_facility,

    COUNT(
        CASE
            WHEN worked_on_action_plan > 0
             AND action_plan_left > 0
            THEN fac_name
        END
    ) AS Started_updated_facility,

    COUNT(
        CASE
            WHEN action_plan_left = 0
            THEN fac_name
        END
    ) AS completed_facility,

    COUNT(
        CASE
            WHEN action_plan_left = total_action_plan
             AND total_action_plan > 0
            THEN fac_name
        END
    ) AS not_started

FROM action_plan_chk ap

INNER JOIN
(
    SELECT
        fac_id,
        MAX(assessment_id) AS latest_assessment
    FROM action_plan_chk
    GROUP BY fac_id
) latest

ON ap.fac_id = latest.fac_id
AND ap.assessment_id = latest.latest_assessment;

";

$res4 = mysqli_query($con, $sql4);

if (!$res4) {
    die(mysqli_error($con));
}

$row4 = mysqli_fetch_assoc($res4);

$initiatedFacility =
    $row4['Total_facility'] ?? 0;

$updatedFacility =
    $row4['Started_updated_facility'] ?? 0;

$completedFacility =
    $row4['completed_facility'] ?? 0;
$notstartedFacility =
    $row4['not_started'] ?? 0;
/* =====================================================
   ASSESSMENT STATUS COUNT
===================================================== */

$sqlStatus = "

SELECT

    COUNT(*) AS total_assessment,

    SUM(
        CASE
            WHEN obt < tot
            THEN 1
            ELSE 0
        END
    ) AS in_progress,

    SUM(
        CASE
            WHEN obt = tot
            THEN 1
            ELSE 0
        END
    ) AS completed

FROM state_dash_view

";

$resStatus =
    mysqli_query($con, $sqlStatus);

if (!$resStatus) {
    die(mysqli_error($con));
}

$rowStatus =
    mysqli_fetch_assoc($resStatus);

$totalAssessmentStatus =
    $rowStatus['total_assessment'] ?? 0;

$inProgressAssessment =
    $rowStatus['in_progress'] ?? 0;

$completedAssessmentStatus =
    $rowStatus['completed'] ?? 0;
?>

<style>
    .dashboard-card {
        border: none;
        border-radius: 18px;
        overflow: hidden;
        transition: 0.3s;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        height: 100%;
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .dashboard-card .card-body {
        padding: 25px;
    }

    .dashboard-icon {
        font-size: 40px;
        opacity: 0.2;
        position: absolute;
        right: 20px;
        top: 15px;
    }

    .metric-value {
        font-size: 34px;
        font-weight: 700;
        margin-top: 10px;
    }

    .metric-title {
        font-size: 15px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .metric-sub {
        font-size: 12px;
        opacity: 0.8;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #4e73df, #224abe);
        color: #fff;
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #1cc88a, #13855c);
        color: #fff;
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #f6c23e, #dda20a);
        color: #fff;
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #36b9cc, #258391);
        color: #fff;
    }

    .bg-gradient-danger {
        background: linear-gradient(135deg, #e74a3b, #be2617);
        color: #fff;
    }

    .bg-gradient-dark {
        background: linear-gradient(135deg, #5a5c69, #2e2f37);
        color: #fff;
    }

    .progress {
        height: 8px;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.2);
        margin-top: 15px;
    }

    .progress-bar {
        background: #fff;
    }

    .table thead th {
        background: #4e73df;
        color: #fff;
        border: none;
        font-size: 13px;
    }

    .table tbody tr:hover {
        background: #f5f7ff;
    }

    .card-header {
        background: #fff;
        border-bottom: 1px solid #eee;
        font-weight: 600;
    }

    .section-title {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 20px;
    }
</style>


<div class="pcoded-main-container">

    <div class="pcoded-content">

        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">

                        <h3 class="section-title">
                            📊 Current Month Dashboard Status
                        </h3>

                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            <!-- Assessment -->

            <div class="col-xl-3 col-md-6 mb-4">

                <div class="card dashboard-card bg-gradient-primary">

                    <div class="card-body position-relative">

                        <div class="dashboard-icon">
                            <i class="feather icon-check-circle"></i>
                        </div>

                        <div class="metric-title">
                            Assessments Completed
                        </div>

                        <div class="metric-value">
                            <?= e($totalAssessments); ?>
                        </div>

                        <div class="metric-sub">
                            Current Month
                        </div>

                        <div class="progress">
                            <div class="progress-bar" style="width:90%"></div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Outcome -->

            <div class="col-xl-3 col-md-6 mb-4">

                <div class="card dashboard-card bg-gradient-success">

                    <div class="card-body position-relative">

                        <div class="dashboard-icon">
                            <i class="feather icon-bar-chart-2"></i>
                        </div>

                        <div class="metric-title">
                            Outcome Reports
                        </div>

                        <div class="metric-value">
                            <?= e($totalOutcome); ?>
                        </div>

                        <div class="metric-sub">
                            Current Month
                        </div>

                        <div class="progress">
                            <div class="progress-bar" style="width:85%"></div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Total AP -->

            <div class="col-xl-3 col-md-6 mb-4">

                <div class="card dashboard-card bg-gradient-warning">

                    <div class="card-body position-relative">

                        <div class="dashboard-icon">
                            <i class="feather icon-clipboard"></i>
                        </div>

                        <div class="metric-title">
                            Facilities Assessment for Action Plan
                        </div>

                        <div class="metric-value">
                            <?= e($initiatedFacility); ?>
                        </div>

                        <div class="metric-sub">
                            Action Plan Initiated
                        </div>

                        <div class="progress">
                            <div class="progress-bar" style="width:75%"></div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Started -->

            <div class="col-xl-3 col-md-6 mb-4">

                <div class="card dashboard-card bg-gradient-info">

                    <div class="card-body position-relative">

                        <div class="dashboard-icon">
                            <i class="feather icon-edit"></i>
                        </div>

                        <div class="metric-title">
                            Started Updating action plan
                        </div>

                        <div class="metric-value">
                            <?= e($updatedFacility); ?>
                        </div>

                        <div class="metric-sub">
                            Action plan in Progress
                        </div>

                        <div class="progress">
                            <div class="progress-bar" style="width:60%"></div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Completed -->

            <div class="col-xl-6 col-md-6 mb-4">

                <div class="card dashboard-card bg-gradient-success">

                    <div class="card-body position-relative">

                        <div class="dashboard-icon">
                            <i class="feather icon-award"></i>
                        </div>

                        <div class="metric-title">
                            Completed Action Plans
                        </div>

                        <div class="metric-value">
                            <?= e($completedFacility); ?>
                        </div>

                        <div class="metric-sub">
                            Completed action plan
                        </div>

                        <div class="progress">
                            <div class="progress-bar" style="width:100%"></div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Not Started -->

            <div class="col-xl-6 col-md-6 mb-4">

                <div class="card dashboard-card bg-gradient-dark">

                    <div class="card-body position-relative">

                        <div class="dashboard-icon">
                            <i class="feather icon-alert-circle"></i>
                        </div>

                        <div class="metric-title">
                            Not Started action plan
                        </div>

                        <div class="metric-value">
                            <?= e($notstartedFacility); ?>
                        </div>

                        <div class="metric-sub">
                            Pending Action Plan Work
                        </div>

                        <div class="progress">
                            <div class="progress-bar" style="width:40%"></div>
                        </div>

                    </div>
                </div>
            </div>

        
       

            <!-- TOTAL ASSESSMENT -->

    <div class="col-xl-4 col-md-4 mb-4">

        <div class="card dashboard-card bg-gradient-primary">

            <div class="card-body position-relative">

                <div class="dashboard-icon">
                    <i class="feather icon-layers"></i>
                </div>

                <div class="metric-title">
                    Total Assessments
                </div>

                <div class="metric-value">
                    <?= e($totalAssessmentStatus); ?>
                </div>

                <div class="metric-sub">
                    Total Assessments Available
                </div>

                <div class="progress">
                    <div class="progress-bar"
                         style="width:100%">
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- IN PROGRESS -->

    <div class="col-xl-4 col-md-4 mb-4">

        <div class="card dashboard-card bg-gradient-warning">

            <div class="card-body position-relative">

                <div class="dashboard-icon">
                    <i class="feather icon-refresh-cw"></i>
                </div>

                <div class="metric-title">
                    In Progress Assessments
                </div>

                <div class="metric-value">
                    <?= e($inProgressAssessment); ?>
                </div>

                <div class="metric-sub">
                    Assessment Work Ongoing
                </div>

                <div class="progress">
                    <div class="progress-bar"
                         style="width:60%">
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- COMPLETED -->

    <div class="col-xl-4 col-md-4 mb-4">

        <div class="card dashboard-card bg-gradient-success">

            <div class="card-body position-relative">

                <div class="dashboard-icon">
                    <i class="feather icon-check-circle"></i>
                </div>

                <div class="metric-title">
                    Completed Assessments
                </div>

                <div class="metric-value">
                    <?= e($completedAssessmentStatus); ?>
                </div>

                <div class="metric-sub">
                    Successfully Completed
                </div>

                <div class="progress">
                    <div class="progress-bar"
                         style="width:85%">
                    </div>
                </div>

            </div>

        </div>

    </div>





            <!-- TABLE -->

            <div class="card mt-3 shadow-sm">

                <div class="card-header">
                    <h5 class="mb-0">
                        🏥 Newly Completed Assessments
                    </h5>
                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-hover table-bordered">

                            <thead>

                                <tr>
                                    <th>Sl No</th>
                                    <th>District</th>
                                    <th>Block</th>
                                    <th>Facility</th>
                                    <th>Facility Type</th>
                                    <th>Assessment</th>
                                    <th>Completed Date</th>
                                </tr>

                            </thead>

                            <tbody>

                                <?php

                                $sl = 0;

                                while ($row = mysqli_fetch_assoc($res2)) {

                                    $sl++;

                                ?>

                                    <tr>

                                        <td><?= $sl; ?></td>

                                        <td><?= e($row['Dist_Name']); ?></td>

                                        <td><?= e($row['Block_Name']); ?></td>

                                        <td><?= e($row['fac_name']); ?></td>

                                        <td><?= e($row['facilities_type']); ?></td>

                                        <td><?= e($row['ass_name']); ?></td>

                                        <td>
                                            <?= date('d M Y', strtotime($row['ass_completed'])); ?>
                                        </td>

                                    </tr>

                                <?php } ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>
    </div>
    <?php include("assets/head/f.php"); ?>