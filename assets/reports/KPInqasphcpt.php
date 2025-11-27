<?php
include('session.php');
include_once('db.php');
date_default_timezone_set('Asia/Kolkata');
?>

<!DOCTYPE html>
<html lang="en">
<?php include('h1.php'); ?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>NQAS KPI PHC</h1>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body">
        <br>
        <form enctype="multipart/form-data" method="post" action="#">

          <div class="row">
            <div class="col-auto">
              <button type="submit" name="submit1" class="btn btn-primary">View Data</button>
            </div>
            <div class="col-auto">
              <button type="submit" name="submit2" class="btn btn-primary">Data Visualization</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <?php
    if (isset($_POST['submit1'])) {

      $fac_id = $_SESSION['u_facilityid'];

      // Initialize query for the selected department

      $query = "CALL  phckpi_rpt(?);";

      // Execute the stored procedure and get results
      $stmt2 = $con->prepare($query);
      $stmt2->bind_param("i",  $fac_id);
      $stmt2->execute();
      $result = $stmt2->get_result();

      // Check if there are results, otherwise display a "No data" message
      if ($result->num_rows > 0) {
        // Initialize an empty array to store the results
        $data = [];

        while ($row = $result->fetch_assoc()) {
          $metric_name = $row['metric_name'];
          $month_in = $row['month_in'];
          $metric_value = $row['metric_value'];

          // Populate the $data array with the results
          $data[$metric_name][$month_in] = $metric_value;
        }

        // Display the result in a table format
        echo "<div class='card'>
                    <div class='card-body'>
                      <input type='button' value='Export to Excel' class='btn btn-success' onclick='exportToExcel(\"table3\")' />
                        <table class='table w-auto small table-bordered' id='table3'>
                          <thead>
                            <tr>
                              <th><b>Metric</b></th>"; // Make the first column header bold

        // Add the months as table headers
        $months = array_keys($data[array_keys($data)[0]]);
        foreach ($months as $month) {
          echo "<th><b>$month</b></th>"; // Make each month column header bold
        }

        echo "</tr></thead><tbody>";

        // Display each metric as a row
        foreach ($data as $metric => $months_data) {
          echo "<tr><td>$metric</td>";
          foreach ($months as $month) {
            $value = isset($months_data[$month]) ? $months_data[$month] : 0;
            echo "<td>$value</td>";
          }
          echo "</tr>";
        }

        echo "</tbody></table></div></div>";
      } else {
        // Display no data found message
        echo "<div class='alert alert-warning'>No data found..!</div>";
      }
    }
    ?>
   <?php
if (isset($_POST['submit2'])) {
?>
  <div class="row">
    <?php
    // Counter to track columns
    $counter = 0;

    // Loop over the range of KPI IDs (101 to 128 in this case)
    for ($i = 1; $i < 21; $i++) {
        // Fetch data for each KPI ID
        $query = "
        SELECT 
    phckpi.phc_kpitext,
    phc_kpi_in.phc_kpi_value, 
    phc_kpi_in.phc_kpi_date
FROM 
    phc_kpi_in
JOIN 
    phckpi ON phc_kpi_in.phc_kpi_id = phckpi.phc_kpi_id
WHERE 
    phc_kpi_in.phc_kpi_id =? 
       ORDER BY 
    phc_kpi_in.phc_kpi_date;
        ";

        $stmt = $con->prepare($query);
        $stmt->bind_param("i", $i); // Bind the current KPI ID
        $stmt->execute();
        $result = $stmt->get_result();

        $categories = [];
        $values = [];

        // Collect the data for the chart
        while ($row = $result->fetch_assoc()) {
            // Parse the date string stored in 'YYYY/MM' format
            $date = DateTime::createFromFormat('Y/m', $row['phc_kpi_date']);
            $kpitext=$row['phc_kpitext'];
            if ($date) {
                // Format the date as 'M Y' (e.g., Jan 2025)
                $formatted_date = $date->format('M Y');
            } else {
                $formatted_date = $row['phc_kpi_date']; // Fallback if the format is invalid
            }

            // Collect formatted date and values
            $categories[] = $formatted_date;
            $values[] = (int)$row['phc_kpi_value']; // Ensure value is an integer
            $values1[] = (int)$row['phc_kpitext'];
        }

        // Prepare the data for the chart dynamically
        if (!empty($categories) && !empty($values)) {
            $chart_id = "lineChart" . $i; // Unique chart ID for each KPI
            $counter++;

            // Check if counter is divisible by 3, if so, start a new row
            if ($counter % 3 == 1 && $counter > 1) {
                echo '</div><div class="row">'; // Close the current row and start a new one
            }
            ?>
            <div class="col-lg-4 col-md-6 col-sm-12"> <!-- 4 columns on large screens, 6 on medium, and full width on small screens -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title"><?php echo $kpitext; ?></h6>

                        <!-- Line Chart -->
                        <div id="<?php echo $chart_id; ?>"></div>

                        <script>
                        document.addEventListener("DOMContentLoaded", () => {
                            new ApexCharts(document.querySelector("#<?php echo $chart_id; ?>"), {
                                series: [{
                                    name: "KPI <?php echo $i; ?>",
                                    data: <?php echo json_encode($values); ?>
                                }],
                                chart: {
                                    height: 350,
                                    type: 'line',
                                    zoom: {
                                        enabled: false
                                    }
                                },
                                dataLabels: {
                                    enabled: false
                                },
                                stroke: {
                                    curve: 'straight'
                                },
                                grid: {
                                    row: {
                                        colors: ['#f3f3f3', 'transparent'], // Takes an array that will be repeated on rows
                                        opacity: 0.5
                                    },
                                },
                                xaxis: {
                                    categories: <?php echo json_encode($categories); ?>,  // Dynamic months (formatted dates)
                                }
                            }).render();
                        });
                        </script>
                        <!-- End Line Chart -->
                    </div>
                </div>
            </div>
            <?php
        }
    }
    ?>
  </div>
<?php
}
?>

  </section>
</main>

<?php include('f.php'); ?>

<!-- Include the xlsx library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>

<script>
  function exportToExcel(tableID) {
    var table = document.getElementById(tableID);
    var workbook = XLSX.utils.table_to_book(table, {
      sheet: "Sheet1"
    });

    // Create the month headers for the first row in the exported Excel
    var sheet = workbook.Sheets["Sheet1"];
    var range = XLSX.utils.decode_range(sheet['!ref']);
    var firstRow = sheet['A1'];

    // Get months from the table and set the first row as month values
    var months = Array.from(table.getElementsByTagName('th')).slice(1).map(function(cell) {
      return cell.innerText;
    });

    // Loop through and update Excel row headers
    for (var i = 0; i < months.length; i++) {
      var cellAddress = {
        r: 0,
        c: i + 1
      }; // Row 0 (first row), Column is dynamic
      var cell_ref = XLSX.utils.encode_cell(cellAddress);
      sheet[cell_ref] = {
        t: 's',
        v: months[i],
        s: {
          font: {
            bold: true
          }
        }
      }; // Make header bold
    }

    XLSX.writeFile(workbook, 'NQAS_phc_kpi_report.xlsx');
  }
</script>

</html>