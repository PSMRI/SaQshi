<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$dept_id = $_SESSION["dept_id"] ?? 0;
$fat     = $_SESSION["f_type_id"] ?? 0;
$p       = $_SESSION["period"] ?? 0;
$Fa      = $_SESSION['u_facilityid'] ?? 0;
?>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <?php
        $tablequery1 = "CALL concern_type_report($fat, $Fa, $dept_id, $p)";
        $q2 = mysqli_query($con, $tablequery1);

        if ($q2 && $row = mysqli_fetch_array($q2)) {
        ?>
          <input type="button" class="btn btn-success mb-3" value="Download Report" onclick="exportStyledTableAsExcel()" />
          <div class="table-responsive small">
            <table id="table2" class="table table-bordered w-100 text-center align-middle">
              <tr>
                <th style="border:none;"></th>
                <th colspan="6" style="background-color:#33334d; color:white; font-weight:bold; text-align:center;">
                  <?= htmlspecialchars($_SESSION['facname'] ?? 'Facility') ?> Overall Score & Area of Concern wise Scores
                </th>
              </tr>
              <tr>
               <td style="border:none;"></td>
                <td style="background-color:#ff9933; color:white; font-weight:bold;">Service Provision</td>
                <td style="background-color:#ff9933; color:white; font-weight:bold;">Patients' Rights</td>
                <td colspan="2" rowspan="2" style="background-color:#fff4dc; font-weight:bold; text-align:center; font-size:16px;">Overall Facility Score</td>
                <td style="background-color:#ff9933; color:white; font-weight:bold;">Inputs</td>
                <td style="background-color:#ff9933; color:white; font-weight:bold;">Support Services</td>
              </tr>
              <tr>
                <td style="border:none;"></td>
                <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc1'] ? round($row['d1'] / $row['totalc1'] * 100, 2) . '%' : '0%' ?> </td>
                <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc2'] ? round($row['d2'] / $row['totalc2'] * 100, 2) . '%' : '0%' ?> </td>
                <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc3'] ? round($row['d3'] / $row['totalc3'] * 100, 2) . '%' : '0%' ?> </td>
                <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc4'] ? round($row['d4'] / $row['totalc4'] * 100, 2) . '%' : '0%' ?> </td>
              </tr>
              <tr>
               <td style="border:none;"></td>
                <td style="background-color:#ff9933; color:white; font-weight:bold;">Clinical Services</td>
                <td style="background-color:#ff9933; color:white; font-weight:bold;">Infection Control</td>
                <td colspan="2" rowspan="2" style="background-color:#33334d; color:white; font-weight:bold; font-size:20px;"> <?= $_SESSION['p1'] ?? '0%' ?> </td>
                <td style="background-color:#ff9933; color:white; font-weight:bold;">Quality Management</td>
                <td style="background-color:#ff9933; color:white; font-weight:bold;">Outcomes</td>
              </tr>
              <tr>
               <td style="border:none;"></td>
                <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc5'] ? round($row['d5'] / $row['totalc5'] * 100, 2) . '%' : '0%' ?> </td>
                <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc6'] ? round($row['d6'] / $row['totalc6'] * 100, 2) . '%' : '0%' ?> </td>
                <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc7'] ? round($row['d7'] / $row['totalc7'] * 100, 2) . '%' : '0%' ?> </td>
                <td style="background-color:#fff4dc; font-weight:bold;"> <?= $row['totalc8'] ? round($row['d8'] / $row['totalc8'] * 100, 2) . '%' : '0%' ?> </td>
              </tr>
            </table>

            <table id="table3" class="table table-bordered mt-4 text-center align-middle">
              <tr>
              <th style="border:none;"></th> 
              <th style="background-color:#33334d; color:white; font-weight:bold;">Reference No.</th>
                <th colspan="2" style="background-color:#33334d; color:white; font-weight:bold;">Area of Concern / Standards</th>
                <th style="background-color:#33334d; color:white; font-weight:bold;">Score Obtained</th>
                <th style="background-color:#33334d; color:white; font-weight:bold;">Maximum Score</th>
                <th style="background-color:#33334d; color:white; font-weight:bold;">Percentage</th>
              </tr>
              <tbody>
                <?php
                mysqli_free_result($q2);
                $con->next_result();

                $aocQuery = "SELECT concern_name, concern_des, concern_id FROM area_of_concern";
                $q2 = mysqli_query($con, $aocQuery);
                while ($row = mysqli_fetch_assoc($q2)) {
                  $conid = $row['concern_id'];
                  echo "<tr>
                  <td style='border:none;'></td>
                          <td colspan='6' style='background-color:#33334d; color:white; font-weight:bold; text-align:center;'>" . htmlspecialchars($row['concern_des'] . ' - ' . $row['concern_name']) . "</td>
                        </tr>";

                  $scoreQuery = "CALL get_Area_of_Concern_Standards_wise_Score_card($fat, $Fa, $dept_id, $p, $conid)";
                  $q4 = mysqli_query($con, $scoreQuery);

                  if ($q4) {
                    while ($sc = mysqli_fetch_assoc($q4)) {
                      $obtained = $sc['obtained'] ?? 0;
                      $total = $sc['total'] ?? 1;
                      $percent = $obtained ? round(($obtained / $total) * 100, 2) . "%" : "0%";

                      echo "<tr>
                      <td style='border:none;'></td>
                              <td style='text-align:left;'>{$sc['id1']}</td>
                              <td colspan='2' style='text-align:left;'>{$sc['area_of_con_subtypedeatils']}</td>
                              <td style='text-align:left;'>{$obtained}</td>
                              <td style='text-align:left;'>{$total}</td>
                              <td style='text-align:left;'>{$percent}</td>
                            </tr>";
                    }
                    mysqli_free_result($q4);
                    $con->next_result();
                  }
                }

                mysqli_free_result($q2);
                $con->next_result();
                ?>
              </tbody>
            </table>
          </div>
        <?php
        } else {
          echo "<div class='alert alert-warning'>No data found for the selected Score Card.</div>";
        }
        ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script>
  function exportStyledTableAsExcel() {
    const table1 = document.getElementById("table2").cloneNode(true);
    const table2 = document.getElementById("table3").cloneNode(true);

    const html = `
      <html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:excel"
            xmlns="http://www.w3.org/TR/REC-html40">
      <head>
        <meta charset="utf-8">
        <style>
          body { font-family: Arial, sans-serif; margin: 20px; }
          table { border-collapse: collapse; width: 100%; margin-bottom: 40px; }
          th, td { border: 1px solid #444; padding: 8px; text-align: center; font-size: 13px; }
        </style>
      </head>
      <body>
        ${table1.outerHTML}<br><br>${table2.outerHTML}
      </body>
      </html>
    `;

    const blob = new Blob([html], {
      type: 'application/vnd.ms-excel'
    });
    const url = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = url;
    a.download = 'facility_scorecard.xls';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  }
</script>
