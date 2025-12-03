<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['department_id']) && !empty($_POST['department_id'])) {
   session_start();
   $_SESSION['dept_id1'] = $_POST['department_id'];
   $_SESSION['dept_name1'] = $_POST['department_name'];  // Save department name
   header("Location: " . $_SERVER['PHP_SELF']);
   exit();
}

include("assets/head/h.php");
$showDeptModal = empty($_SESSION['dept_id1']) || $_SESSION['dept_id1'] == 0;
$dept_name = $_SESSION['dept_name1'] ?? '';
$fname = $_SESSION['facname'];
$Fa = $_SESSION['u_facilityid'];
$call_q111 = "SELECT COUNT(id) AS id FROM assessment_desc WHERE fac_id_fk = $Fa";
$result12 = mysqli_query($con, $call_q111);
$row = mysqli_fetch_array($result12, MYSQLI_ASSOC);
$count = $row['id'];
$_SESSION['count'] = $count;
?>

   <div class="pcoded-main-container">
      <div class="pcoded-content">
         <div class="pagetitle mb-2">
            <h5 class="fw-bold text-primary mb-1">
               <i class="bi bi-person-badge-fill me-2"></i>Dashboard – Status of Overall Progress for <?php echo  $fname; ?> as of <?php echo date("d M Y h:i A"); ?> Department <?php echo htmlspecialchars($dept_name); ?>

               <button type="button" class="btn btn-sm btn-link text-warning ms-2" data-toggle="modal" data-target="#departmentModal">
                  Change Department
               </button>
            </h5>
         </div>

         <div class="text-end mb-2 exclude-from-pdf">
            <button class="btn btn-danger" onclick="downloadDashboardAsPDF()">Download Dashboard as PDF</button>
         </div>


         <div class="row">
            <!-- Score Card -->
            <?php include("partials/score_card.php"); ?>

            <!-- Checkpoints Count -->



            <!-- Checkpoints Count -->

            <div class="col-lg-4">
               <div class="card flat-card widget-primary-card">
                  <div class="row-table">
                     <div class="col-sm-3 card-body d-flex align-items-center justify-content-center">
                        <i class="feather icon-list text-primary"></i>
                     </div>
                     <div class="col-sm-9 py-3">
                        <!-- Spinner and heading separate -->
                        <div class="d-flex align-items-center">
                           <h4 id="checkpoints-container" class="mb-0 me-2">Checkpoints</h4>
                        </div>
                        <h6>CheckPoints</h6>
                        <small>
                           <i class="bi bi-arrow-down-circle-fill me-1"></i>
                           <a href="assets/export/export_deprt_indicators.php">Download Data</a>
                        </small>
                     </div>
                  </div>
               </div>
            </div>


            <!-- Assessment Count -->
            <div class="col-lg-4">
               <div class="card flat-card widget-primary-card">
                  <div class="row-table">
                     <div class="col-sm-3 card-body d-flex align-items-center justify-content-center">
                        <i class="feather icon-clipboard text-warning"></i>
                     </div>
                     <div class="col-sm-9 py-3">
                        <h4 class="text-warning"><?= htmlspecialchars($count) ?></h4>
                        <h6>Assessment No.</h6>
                     </div>
                  </div>
               </div>
            </div>

         </div>

         <!-- Compliance Breakdown -->
         <div class="row">
            <?php include("partials/compliance_cards.php"); ?>
         </div>

         <!-- Pie Charts for Score -->
         <div class="row">
            <div class="col-lg-6">
               <div class="card">
                  <div class="card-header bg-secondary  fw-bold">
                     <h5 class="card-title">Baseline Score</h5>
                  </div>
                  <div class="card-body">

                     <div id="pieChart">
                        <div class="spinner-grow text-danger" role="status">
                           <span class="sr-only">Loading...</span>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="col-lg-6">
               <div class="card">
                  <div class="card-header bg-secondary  fw-bold">
                     <h5 class="card-title">Current Score</h5>
                  </div>
                  <div class="card-body">

                     <div id="pieChart1">
                        <div class="spinner-grow text-danger" role="status">
                           <span class="sr-only">Loading...</span>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         <!-- Score % and Checkpoint Assessment -->
         <div class="row">
            <div class="col-lg-6">
               <div class="card">
                  <div class="card-header bg-secondary  fw-bold">
                     <h5 class="card-title">Score %</h5>
                  </div>
                  <div class="card-body">

                     <div id="progressBarsContainer">
                        <div class="spinner-grow text-danger" role="status">
                           <span class="sr-only">Loading...</span>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="col-lg-6">
               <div class="card">
                  <div class="card-header bg-secondary  fw-bold">
                     <h5 class="card-title">Score %</h5>
                  </div>
                  <div class="card-body">

                     <div id="checkpointProgressBarsContainer">
                        <div class="spinner-grow text-danger" role="status">
                           <span class="sr-only">Loading...</span>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>

         <!-- Overall Summary and Trend Chart -->
         <div class="row">
            <div class="col-lg-6">
               <div class="card">
                  <div class="card-header bg-secondary  fw-bold">
                     <h5 class="card-title">Overall Progress</h5>
                  </div>
                  <div class="card-body">

                     <div id="overallProgressContainer">
                        <div class="spinner-grow text-danger" role="status">
                           <span class="sr-only">Loading...</span>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="col-lg-6">
               <div class="card">
                  <div class="card-header bg-secondary  fw-bold">
                     <h5 class="card-title">Overall Progress</h5>
                  </div>
                  <div class="card-body">

                     <div id="radialBarChart1">
                        <div class="spinner-grow text-danger" role="status">
                           <span class="sr-only">Loading...</span>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>

      <div id="pdf-header" class="d-none">
         <h5 style="text-align: center;">
            SaQshi Dashboard Report - <?php echo $fname; ?><br>
            Generated on: <?php echo date("d M Y h:i A"); ?>
         </h5>
      </div>

   </div>
</div>
<!-- Department Modal -->
<div id="departmentModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="departmentModalLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered" role="document">
      <form method="post">
         <div class="modal-content">
            <div class="modal-header">
               <h5 class="modal-title" id="departmentModalLabel">Select Department</h5>
               <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               <!-- Hidden input to store department_name -->
               <input type="hidden" name="department_name" id="department_name_input" value="">

               <label for="departmentSelect" class="form-label">Department</label>
               <select class="mb-3 form-control form-control-sm" id="departmentSelect" name="department_id" required>
                  <option value="">-- Select Department --</option>
                  <?php
                  $factype = $_SESSION['f_type_id'];
                  $facid = $_SESSION['u_facilityid'];
                  $assid = $_SESSION['assperiod'];
                  $query = "SELECT DISTINCT a.fac_dept_id_fk, b.dept_name 
                                  FROM concern_subtype_chklist AS a 
                                  JOIN fac_department AS b ON a.fac_dept_id_fk = b.fac_dept_id 
                                  WHERE a.fac_type_id_fk = ? AND a.fac_dept_id_fk IN (
                                      SELECT fac_dept_id FROM fac_dept_map 
                                      WHERE fac_id = ? AND acc_id = ?
                                  )";
                  $stmt = $con->prepare($query);
                  $stmt->bind_param("iii", $factype, $facid, $assid);
                  $stmt->execute();
                  $result = $stmt->get_result();
                  while ($row = $result->fetch_assoc()) {
                     echo "<option value='{$row['fac_dept_id_fk']}' data-name='{$row['dept_name']}'>{$row['dept_name']}</option>";
                  }
                  $stmt->close();
                  ?>
               </select>
            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
               <button type="submit" class="btn btn-primary">Continue</button>
            </div>
         </div>
      </form>
   </div>

<script>
   document.addEventListener("DOMContentLoaded", () => {
      fetch('assets/get/index_fetchoverallass.php')
         .then(response => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
         })
         .then(data => {
            let values = data.values.split(',').map(v => parseFloat(v));
            let labels = data.periods.split(',').map(l => l.replace(/'/g, '').trim());

            const container = document.querySelector("#radialBarChart1");
            container.innerHTML = ''; // Clear previous chart

            if (values.length === 1) {
               // Single Value: Radial Chart
               const radialOptions = {
                  series: [values[0]], // Already in percentage (e.g., 0.6)
                  chart: {
                     height: 300,
                     type: 'radialBar'
                  },
                  plotOptions: {
                     radialBar: {
                        hollow: {
                           size: '70%'
                        },
                        dataLabels: {
                           name: {
                              show: true,
                              fontSize: '16px',
                              offsetY: -10,
                              formatter: () => labels[0]
                           },
                           value: {
                              fontSize: '20px',
                              formatter: val => val.toFixed(1) + "%"
                           }
                        }
                     }
                  },
                  labels: [labels[0]]
               };
               new ApexCharts(container, radialOptions).render();

            } else {
               // Multiple values: Horizontal Bar
               const barOptions = {
                  series: [{
                     data: values.map(v => parseFloat(v)) // No scaling
                  }],
                  chart: {
                     type: 'bar',
                     height: values.length * 60
                  },
                  plotOptions: {
                     bar: {
                        borderRadius: 3,
                        horizontal: true,
                     }
                  },
                  dataLabels: {
                     enabled: true,
                     formatter: val => val.toFixed(1) + "%"
                  },
                  xaxis: {
                     categories: labels
                  }
               };
               new ApexCharts(container, barOptions).render();
            }
         })
         .catch(error => {
            console.error("Chart load error:", error);
            document.querySelector("#radialBarChart1").innerHTML = `<p class="text-danger">Failed to load chart</p>`;
         });
   });
</script>



<script>
   document.addEventListener("DOMContentLoaded", () => {
      // Fetch data from the PHP script
      fetch('assets/get/index_fetchoverall.php') // The new PHP script that returns the data
         .then(response => response.json()) // Parse JSON response
         .then(data => {
            // Ensure the data is valid
            if (data) {
               const overallProgressContainer = document.getElementById('overallProgressContainer');
               overallProgressContainer.innerHTML = ''; // Clear any existing content

               // Non-compliance (0 indicators)
               const nonCompliantHTML = `
                        <label>Non-compliance indicators (0):</label>
                        <span class="badge bg-white text-warning">
                            <h4>${data.non_compliant}</h4>
                        </span><br>
                    `;
               overallProgressContainer.innerHTML += nonCompliantHTML;

               // Partially compliance (1 indicators)
               const partiallyCompliantHTML = `
                        <label>Partially compliance indicators (1):</label>
                        <span class="badge bg-white text-info">
                            <h5>${data.partially_compliant}</h5>
                        </span><br>
                    `;
               overallProgressContainer.innerHTML += partiallyCompliantHTML;

               // Fully compliance (2 indicators)
               const fullyCompliantHTML = `
                        <label>Fully compliance indicators (2):</label>
                        <span class="badge bg-white text-success">
                            <h4>${data.fully_compliant}</h4>
                        </span><br>
                    `;
               overallProgressContainer.innerHTML += fullyCompliantHTML;

               // Number of indicators completed
               const completedIndicatorsHTML = `
                        <label>Number of indicators completed:</label>
                        <span class="badge bg-white text-primary">
                            <h5>${data.total_indicators}</h5>
                        </span><br>
                    `;
               overallProgressContainer.innerHTML += completedIndicatorsHTML;

               // Total Indicators
               const totalIndicatorsHTML = `
                        <label>Total Indicators:</label>
                        <span class="badge bg-white text-secondary">
                            <h4>${data.total_indicators_count}</h4>
                        </span><br>
                    `;
               overallProgressContainer.innerHTML += totalIndicatorsHTML;
            } else {
               console.error('Invalid data received:', data);
            }
         })
         .catch(error => {
            console.error('Error fetching data for overall progress:', error);
         });
   });
</script>
<script>
   document.addEventListener("DOMContentLoaded", () => {
      // Fetch data from the PHP script
      fetch('assets/get/index_fetchchk.php') // The new PHP script that returns the data
         .then(response => response.json()) // Parse JSON response
         .then(data => {
            // Ensure the data is valid and contains items
            if (data && Array.isArray(data)) {
               const checkpointProgressBarsContainer = document.getElementById('checkpointProgressBarsContainer');
               checkpointProgressBarsContainer.innerHTML = ''; // Clear any existing content

               data.forEach(item => {
                  const {
                     concern_name,
                     percentage,
                     obtained,
                     total
                  } = item;

                  // Determine the class based on the percentage
                  let progressClass = 'bg-danger'; // Default class
                  if (percentage >= 70) {
                     progressClass = 'bg-success';
                  } else if (percentage >= 65 && percentage < 70) {
                     progressClass = 'bg-warning';
                  }

                  // Create progress bar for each checkpoint concern
                  const progressBarHTML = `
                            <label>${concern_name}</label>
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped ${progressClass} progress-bar-animated"
                                     role="progressbar"
                                     style="width: ${percentage}%"
                                     aria-valuenow="${obtained}"
                                     aria-valuemin="0"
                                     aria-valuemax="${total}">
                                     ${obtained}/${total}
                                </div>
                            </div>
                        `;

                  // Append the progress bar HTML to the container
                  checkpointProgressBarsContainer.innerHTML += progressBarHTML;
               });
            } else {
               console.error('Invalid data received:', data);
            }
         })
         .catch(error => {
            console.error('Error fetching data for checkpoint progress bars:', error);
         });
   });
</script>
<script>
   document.addEventListener("DOMContentLoaded", () => {
      // Fetch data from the PHP script
      fetch('assets/get/index_fetchscore.php') // The new PHP script that returns the data
         .then(response => response.json()) // Parse JSON response
         .then(data => {
            // Ensure the data is valid and contains items
            if (data && Array.isArray(data)) {
               const progressBarsContainer = document.getElementById('progressBarsContainer');
               progressBarsContainer.innerHTML = ''; // Clear any existing content

               data.forEach(item => {
                  const {
                     concern_name,
                     percentage,
                     obtained,
                     total
                  } = item;

                  // Determine the class based on the percentage
                  let progressClass = 'bg-danger'; // Default class
                  if (percentage >= 70) {
                     progressClass = 'bg-success';
                  } else if (percentage >= 65 && percentage < 70) {
                     progressClass = 'bg-warning';
                  }

                  // Create progress bar for each concern
                  const progressBarHTML = `
                            <label>${concern_name}</label>
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped ${progressClass} progress-bar-animated"
                                     role="progressbar"
                                     style="width: ${percentage}%"
                                     aria-valuenow="${obtained}"
                                     aria-valuemin="0"
                                     aria-valuemax="${total}">
                                     ${percentage}%
                                </div>
                            </div>
                        `;

                  // Append the progress bar HTML to the container
                  progressBarsContainer.innerHTML += progressBarHTML;
               });
            } else {
               console.error('Invalid data received:', data);
            }
         })
         .catch(error => {
            console.error('Error fetching data for progress bars:', error);
         });
   });
</script>
<script>
   document.addEventListener("DOMContentLoaded", () => {
      // Fetch data from the PHP script
      fetch('assets/get/index_fetchpi2.php') // The PHP script that returns the data
         .then(response => {
            // Ensure the response is valid and parse JSON
            if (!response.ok) {
               throw new Error('Network response was not ok');
            }
            return response.json();
         })
         .then(data => {
            // Log the received data for debugging
            console.log('Fetched Current Score Data:', data);

            // Ensure that the data is valid and contains the necessary properties
            if (data && data.compliance_data1 && data.compliance_data1.fully_compliant !== undefined && data.compliance_data1.partially_compliant !== undefined && data.compliance_data1.non_compliant !== undefined) {
               // Convert string data to numbers
               const fullyCompliant = parseInt(data.compliance_data1.fully_compliant, 10) || 0;
               const partiallyCompliant = parseInt(data.compliance_data1.partially_compliant, 10) || 0;
               const nonCompliant = parseInt(data.compliance_data1.non_compliant, 10) || 0;

               // Access percentage data
               const perc1 = parseFloat(data.percentage_data1.percentage) || 0;

               // Render the pie chart
               new ApexCharts(document.querySelector("#pieChart1"), {
                  series: [0, fullyCompliant, partiallyCompliant, nonCompliant],
                  chart: {
                     height: 300,
                     type: 'pie',
                     toolbar: {
                        show: true
                     }
                  },
                  labels: [
                     `Total Score: <h2>${perc1}%</h2>`,
                     'Fully Compliance',
                     'Partially Compliance',
                     'Non Compliance'
                  ]
               }).render();
            } else {
               console.error('Invalid data received:', data); // Log if the data doesn't have the expected format
            }
         })
         .catch(error => {
            console.error('Error fetching data for pie chart:', error);
         });
   });
</script>
<script>
   document.addEventListener("DOMContentLoaded", () => {
      // Fetch data from the PHP script
      fetch('assets/get/index_fetchpi.php') // The PHP script that returns the data
         .then(response => {
            // Ensure the response is valid and parse JSON
            if (!response.ok) {
               throw new Error('Network response was not ok');
            }
            return response.json();
         })
         .then(data => {
            // Log the received data for debugging
            console.log('Fetched Data:', data);

            // Ensure that the data contains the necessary properties
            if (data.compliance_data && data.percentage_data) {
               const fullyCompliant = parseInt(data.compliance_data.fully_compliant, 10) || 0;
               const partiallyCompliant = parseInt(data.compliance_data.partially_compliant, 10) || 0;
               const nonCompliant = parseInt(data.compliance_data.non_compliant, 10) || 0;
               const perc = parseFloat(data.percentage_data.percentage) || 0;

               // Render the pie chart
               new ApexCharts(document.querySelector("#pieChart"), {
                  series: [0, fullyCompliant, partiallyCompliant, nonCompliant],
                  chart: {
                     height: 300,
                     type: 'pie',
                     toolbar: {
                        show: true
                     }
                  },
                  labels: [
                     `Total Score:<h2> ${perc}% <h2>`,
                     'Fully Compliance',
                     'Partially Compliance',
                     'Non Compliance'
                  ]
               }).render();
            } else {
               console.error('Invalid data received:', data); // Log if the data doesn't have the expected format
            }
         })
         .catch(error => {
            console.error('Error fetching data for pie chart:', error);
         });
   });
</script>
<script>
   // Fetch the PHP script that returns the data
   fetch('assets/get/index_fetch1.php')
      .then(response => response.json()) // Parse the JSON response
      .then(data => {
         // Access the percentage data from the response
         const percentage1 = data.data2.p1;
         const percentage1l = data.data1.p1l;
         const percentageClass = data.data2.percentage_class;

         // Find your HTML elements where you want to show the data
         const percentageElement = document.querySelector('.widget-numbers');
         const percentageText = document.createElement('span');
         percentageText.textContent = `${percentage1}%`; // or percentage1l based on your need

         // Apply the appropriate class based on the percentage
         percentageText.classList.add(percentageClass);
         percentageElement.appendChild(percentageText);

         // For p1l value, you can use similar code for displaying
         const percentageElement1l = document.querySelector('.widget-numbers1l');
         const percentageText1l = document.createElement('span');
         percentageText1l.textContent = `${percentage1l}%`;
         percentageElement1l.appendChild(percentageText1l);
      })
      .catch(error => console.error('Error fetching data:', error));
</script>
<script>
   function fetchCheckPoints() {
      fetch('assets/get/index_fetch2.php')
         .then(response => response.json())
         .then(data => {
            const checkpointsContainer = document.getElementById('checkpoints-container');
            const zeroContainer = document.getElementById('zero-container');
            const oneContainer = document.getElementById('one-container');
            const fullyContainer = document.getElementById('fully-container');

            if (data.length > 0) {
               const item = data[0];

               // Display obtained / total
               checkpointsContainer.innerHTML = `<strong class="${item.percentage_class}">${item.obtained} / ${item.total}</strong>`;

               // Display each compliance
               zeroContainer.innerHTML = `<div class="widget-numbers text-warning"><span>${item.zero}</span></div>`;
               oneContainer.innerHTML = `<div class="widget-numbers text-info"><span>${item.one}</span></div>`;
               fullyContainer.innerHTML = `<div class="widget-numbers text-success"><span>${item.fully}</span></div>`;
            } else {
               checkpointsContainer.innerHTML = `<span class="text-muted">N/A</span>`;
               zeroContainer.innerHTML = '';
               oneContainer.innerHTML = '';
               fullyContainer.innerHTML = '';
            }
         })
         .catch(error => {
            console.error('Error fetching data for CheckPoints:', error);
         });
   }

   window.onload = fetchCheckPoints;
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
   async function downloadDashboardAsPDF() {
      const {
         jsPDF
      } = window.jspdf;
      const dashboard = document.querySelector('.pcoded-main-container');
      const header = document.getElementById('pdf-header');

      // Temporarily show header
      header.classList.remove('d-none');

      window.scrollTo(0, 0);

      html2canvas(dashboard, {
         scale: 2,
         useCORS: true,
         ignoreElements: el => el.classList.contains('exclude-from-pdf')
      }).then(canvas => {
         const imgData = canvas.toDataURL('image/png');
         const pdf = new jsPDF('p', 'mm', 'a4');
         const pdfWidth = pdf.internal.pageSize.getWidth();
         const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

         pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
         pdf.save("SaQshi_Dashboard_Report.pdf");

         // Hide header again
         header.classList.add('d-none');
      }).catch(err => {
         console.error("PDF generation error:", err);
         header.classList.add('d-none');
      });
   }
</script>
<script>
   $('#departmentSelect').change(function() {
      var deptName = $('#departmentSelect option:selected').data('name');
      $('#department_name_input').val(deptName);
   });
</script>
<?php include("assets/head/f.php"); ?>