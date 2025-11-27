<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$dept_id = $_POST['dept_id'] ?? '';
$fac_type_id = $_POST['fac_type_id'] ?? '';
$page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if ($dept_id && $fac_type_id) {
    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM department_indicators_gap 
        WHERE fac_type_id = ? AND fac_dept_id_fk = ?";
    $stmtCount = $con->prepare($countQuery);
    $stmtCount->bind_param("ii", $fac_type_id, $dept_id);
    $stmtCount->execute();
    $totalRows = $stmtCount->get_result()->fetch_assoc()['total'];
    $stmtCount->close();

    // Main query with pagination
    $query = "
        SELECT csqa_id_fk, csqa_reference_id,Measurable_Element, c_subtype_Reference_No_fk,concern_name, area_of_con_subtypedeatils, compliance_percent
        FROM department_indicators_gap
        WHERE fac_type_id = ? AND fac_dept_id_fk = ?
        ORDER BY csqa_id_fk ASC
        LIMIT ? OFFSET ?
    ";
    $stmt = $con->prepare($query);
    $stmt->bind_param("iiii", $fac_type_id, $dept_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    // Group rows by zone
    $zones = ['green' => [], 'yellow' => [], 'red' => []];
    while ($row = $result->fetch_assoc()) {
        $percent = floatval($row['compliance_percent']);
        if ($percent > 60) {
            $zones['green'][] = $row;
        } elseif ($percent >= 40 && $percent <= 60) {
            $zones['yellow'][] = $row;
        } else {
            $zones['red'][] = $row;
        }
    }

    // Display Zone Tables
    $zoneLabels = [
        'green' => ['label' => 'Green Zone ✅ (>60%)', 'class' => 'success'],
        'yellow' => ['label' => 'Yellow Zone ⚠️ (40%-60%)', 'class' => 'warning text-dark'],
        'red' => ['label' => 'Red Zone ❌ (<40%)', 'class' => 'danger']
    ];

    foreach ($zones as $zoneKey => $rows) {
        echo "<div class='card mb-4'>";
       echo "<div class='card-header d-flex justify-content-between align-items-center bg-{$zoneLabels[$zoneKey]['class']}'>";
echo "<span class='fw-bold'>{$zoneLabels[$zoneKey]['label']}</span>";
echo "<button class='btn btn-light btn-sm' onclick=\"downloadZoneExcel('$zoneKey')\">Download Excel</button>";
echo "</div>";

        echo "<div class='card-body p-2'>";
        echo "<table class='table table-bordered table-hover small'>";
        echo "<thead class='table-light'><tr>
                <th>#</th>
                <th>Sta.</th>
                <th>Ref.</th>
                <th>Mea. Element</th>
                <th>Concern</th>
                <th>Details</th>
                <th>Comp%</th>
            </tr></thead><tbody>";

        $count = 1;
        foreach ($rows as $row) {
            echo "<tr>
                    <td>{$count}</td>
                      <td>" . htmlspecialchars($row['c_subtype_Reference_No_fk']) . "</td>
                    
                     <td>" . htmlspecialchars($row['csqa_reference_id']) . "</td>
                    <td>" . htmlspecialchars($row['Measurable_Element']) . "</td>
                    <td>" . htmlspecialchars($row['concern_name']) . "</td>
                    <td>" . htmlspecialchars($row['area_of_con_subtypedeatils']) . "</td>
                    <td>" . floatval($row['compliance_percent']) . "%</td>
                </tr>";
            $count++;
        }

        echo "</tbody></table>";
        echo "</div></div>";
    }

    // Pagination
    $totalPages = ceil($totalRows / $limit);
    echo '<div class="text-center mt-3">';
    if ($page > 1) {
        echo '<button class="btn btn-outline-secondary btn-sm mx-1" onclick="loadPage(1)">«</button>';
        echo '<button class="btn btn-outline-secondary btn-sm mx-1" onclick="loadPage(' . ($page - 1) . ')">‹</button>';
    }
    if ($page < $totalPages) {
        echo '<button class="btn btn-outline-secondary btn-sm mx-1" onclick="loadPage(' . ($page + 1) . ')">›</button>';
        echo '<button class="btn btn-outline-secondary btn-sm mx-1" onclick="loadPage(' . $totalPages . ')">»</button>';
    }
    echo '</div>';

    $stmt->close();
}
?>
<script>
function loadPage(pageNum) {
    const deptId = document.getElementById("dept_id").value;
    const facTypeId = document.getElementById("fac_type_id").value;

    $.post("your_php_file.php", {
        page: pageNum,
        dept_id: deptId,
        fac_type_id: facTypeId
    }, function(data) {
        $("#resultContainer").html(data);
    });
}
</script>
<script>
  const deptId = <?= json_encode($_POST['dept_id'] ?? '') ?>;
  const facTypeId = <?= json_encode($_POST['fac_type_id'] ?? '') ?>;

  function downloadZoneExcel(zone) {
    if (!deptId || !facTypeId) {
      alert("Missing department or facility ID.");
      return;
    }

    const url = `assets/get/download_zone_excel.php?zone=${zone}&dept_id=${deptId}&fac_type_id=${facTypeId}`;
    window.open(url, '_blank');
  }
</script>


