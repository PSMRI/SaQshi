<?php
//include('../db.php'); //

function process_form() {
    include('db.php'); //
    // Ensure session variables are set correctly
    $M = $_SESSION['M'] ?? '';
    $C = $_SESSION['C'] ?? '';
    $Means = $_SESSION['Means'] ?? '';
    $p = $_SESSION['assperiod'] ?? 0;
    $F = $_SESSION['dept_id1'] ?? 0;
    $Fa = $_SESSION['f_type_id'] ?? 0;
    $Co = $_SESSION['Cn'];
    $ca = $_SESSION['cy'];
    $fid = $_SESSION['u_facilityid'] ?? 0;
    $ASSm = $_SESSION['Assessment_Method'] ?? '';

    // Ensure all session variables are properly set
    if (empty($fid) || empty($Fa) || empty($Co) || empty($ca) || empty($ASSm) || empty($M) || empty($C) || empty($Means)) {
        echo 'Some session variables are missing!';
        exit;
    }

    // Call stored procedure to get assessment data
    $_SESSION['q1'] = "CALL get_assessment(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    if ($stmt = $con->prepare( $_SESSION['q1'])) {
        $stmt->bind_param("iiiiiissss", $fid, $Fa, $Co, $ca, $p, $F, $ASSm, $M, $C, $Means);
        $stmt->execute();
        $result = $stmt->get_result();

        // Prepare the checklist results for display
        $checklistData = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $checklistData[] = $row;
            }
        }

        mysqli_free_result($result);
        $con->next_result();

        // Get assessment count
        $getcountQuery = "CALL get_assessment_count(?, ?, ?, ?, ?, ?)";
        if ($stmtCount = $con->prepare($getcountQuery)) {
            $stmtCount->bind_param("iiiiii", $fid, $Fa, $Co, $ca, $F, $p);
            $stmtCount->execute();
            $countResult = $stmtCount->get_result();

            $assessmentCountData = [];
            if ($countResult->num_rows > 0) {
                while ($row = $countResult->fetch_assoc()) {
                    $assessmentCountData[] = $row;
                }
            }

            mysqli_free_result($countResult);
            $con->next_result();
        } else {
            echo 'Failed to retrieve assessment count!';
            exit;
        }

        return [
            'checklistData' => $checklistData,
            'assessmentCountData' => $assessmentCountData
        ];
    } else {
        echo 'Failed to prepare the assessment query!';
        exit;
    }
}
?>
