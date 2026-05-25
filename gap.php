<?php include("assets/head/h.php"); ?>

<style>
/* --- Global Styles --- */
.page-header h5 {
  font-size: 1.3rem;
  letter-spacing: 0.3px;
}

/* --- Card Enhancements --- */
.card {
  border-radius: 10px !important;
  overflow: hidden;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
}

.card-header {
  padding: 0.75rem 1.2rem;
  font-weight: 600;
  letter-spacing: 0.3px;
}

.card-body {
  background-color: #fafafa;
}

/* --- Table Styling --- */
.table {
  font-size: 0.88rem;
}
.table thead th {
  text-align: center;
  vertical-align: middle;
  white-space: nowrap;
}

/* Fix for invisible table headers: make them bright and readable */
.table thead.table-dark th,
.table thead th {
  background-color: #0d6efd !important; /* bright blue header for default tables */
  color: #fff !important;
  font-weight: 600;
  border-bottom: 2px solid #e9ecef !important;
}

/* Zone-specific header colors (override default blue) */
#red_zone_table thead th { background-color: #dc3545 !important; }   /* Red Zone */
#yellow_zone_table thead th { background-color: #ffc107 !important; color: #212529 !important; } /* Yellow */
#orange_zone_table thead th { background-color: #fd7e14 !important; } /* Orange */
#green_zone_table thead th { background-color: #198754 !important; }  /* Green */

/* Hover & row effects */
.table tbody tr:hover {
  background-color: #f1f8ff !important;
  transition: 0.2s ease;
}

/* Notes style */
p.text-primary.small {
  border-left: 3px solid #0d6efd;
  padding-left: 8px;
  margin-top: 10px;
  background-color: #eef6ff;
  border-radius: 4px;
}

/* Custom Colors */
.badge.bg-orange {
  background-color: #fd7e14;
  color: white;
}
.text-orange {
  color: #fd7e14 !important;
}

/* Datatable Buttons */
.dt-button.buttons-excel {
  background-color: #28a745 !important;
  color: white !important;
  border: none;
  border-radius: 6px;
  padding: 6px 12px;
  font-size: 14px;
}
.dt-button.buttons-excel:hover {
  background-color: #1e7e34 !important;
}

/* small responsive tweak to ensure header icons wrap nicely */
.card-header .btn {
  white-space: nowrap;
}
</style>

<div class="pcoded-main-container">
  <div class="pcoded-content">
    <div class="page-header mb-3">
      <h5 class="text-primary fw-bold"><i class="bi bi-graph-up-arrow me-1"></i> Gap Analysis Report</h5>
    </div>
<div class="card shadow-sm border mb-3">

    <div class="card-header bg-light d-flex justify-content-between align-items-center"
         style="cursor:pointer;"
         onclick="toggleGapInfo()">

        <div>
            <i class="bi bi-info-circle-fill me-2"></i>
          <strong>
        About Gap Analysis (Click to Read)
</strong>
        </div>

        <div id="gapIcon"
             style="font-size:22px;font-weight:bold;">
            +
        </div>

    </div>

    <div id="gapInfoBox" style="display:none;">

        <div class="card-body">

            <p>
                The Gap Analysis report identifies measurable elements and
                indicators where healthcare facilities are non-compliant.
                Indicators are categorized into different zones based on the
                number of facilities showing non-compliance for a particular
                indicator.
            </p>

            <p>
                This analysis helps administrators, quality teams, and
                decision-makers identify priority areas requiring immediate
                attention, develop corrective action plans, allocate resources
                effectively, and formulate targeted improvement strategies.
                It also highlights indicators where facilities are performing
                well and maintaining better compliance levels.
            </p>

            <p>
                The report sections
                <strong>
                    “Top Non-Compliant Area of Concern and Standard
                    (By Facility Type)”
                </strong>
                and
                <strong>
                    “Top Non-Compliant Area of Concern
                    (By Facility Type)”
                </strong>
                provide a summarized view of the most frequently
                non-compliant areas across different facility types.
            </p>

            <ul class="mb-0">

                <li>
                    <span class="badge bg-danger">
                        Red Zone
                    </span>
                    :
                    More than <strong>150</strong> facilities are
                    non-compliant on a particular indicator.
                </li>

                <li class="mt-2">
                    <span class="badge bg-warning text-dark">
                        Yellow Zone
                    </span>
                    :
                    Between <strong>100 and 149</strong> facilities are
                    non-compliant on a particular indicator.
                </li>

                <li class="mt-2">
                    <span class="badge text-dark"
                          style="background:#ffcc80;">
                        Orange Zone
                    </span>
                    :
                    Between <strong>50 and 99</strong> facilities are
                    non-compliant on a particular indicator.
                </li>

                <li class="mt-2">
                    <span class="badge bg-success">
                        Green Zone
                    </span>
                    :
                    Less than <strong>50</strong> facilities are
                    non-compliant on a particular indicator.
                </li>

            </ul>

        </div>

    </div>

</div>


    <!-- 🔹 Top Non-Compliant Concern + Standard Table -->
    <div class="card mb-4 shadow-sm border">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Top Non-Compliant Area of Concern and Standard (By Facility Type)</h6>
       </div>
      <div class="card-body table-responsive">
        <table id="withStandardTable" class="table table-bordered table-striped table-hover table-sm align-middle">
          <thead class="table-dark">
            <tr>
              <th>Facility Type</th>
              <th>Area of Concern</th>
              <th>Standard</th>
              <th>Non-Compliant Count</th>
            </tr>
          </thead>
        </table>
        <p class="text-primary small fst-italic mt-3">
          * Highlights the standards under each facility type where the highest number of non-compliances were recorded, pinpointing key improvement areas.
        </p>
      </div>
    </div>

    <!-- 🔹 Top Non-Compliant Concern Table -->
    <div class="card mb-4 shadow-sm border">
      <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-activity me-2"></i>Top Non-Compliant Area of Concern (By Facility Type)</h6>
            </div>
      <div class="card-body table-responsive">
        <table id="withoutStandardTable" class="table table-bordered table-striped table-hover table-sm align-middle">
          <thead class="table-dark">
            <tr>
              <th>Facility Type</th>
              <th>Area of Concern</th>
              <th>Non-Compliant Count</th>
            </tr>
          </thead>
        </table>
        <p class="text-primary small fst-italic mt-3">
          * Displays the areas of concern with the most non-compliances for each facility type — helping prioritize quality improvement efforts.
        </p>
      </div>
    </div>

    <!-- 🔹 Zone-wise Tables -->
    <?php
    $zones = [
      'Red Zone' => 'danger',
      'Yellow Zone' => 'warning',
      'Orange Zone' => 'orange',
      'Green Zone' => 'success'
    ];

    foreach ($zones as $zoneName => $color) {
      $tableId = strtolower(str_replace(' ', '_', $zoneName)) . '_table';
      echo "
      <div class='card mb-4 shadow-sm border'>
        <div class='card-header bg-light'>
          <h6 class='fw-bold text-$color mb-0'><i class=\"bi bi-bar-chart-line-fill me-2\"></i>$zoneName – Top Indicators</h6>
        </div>
        <div class='card-body table-responsive'>
          <table id='$tableId' class='table table-bordered table-striped table-hover table-sm align-middle'>
            <thead class='table-dark'>
              <tr>
			  	<th>Facility Type</th>                
                <th>Compliance</th>
                <th>Concern</th>
                <th>Sub Type</th>
                <th>Standard</th>
                <th>Reference ID</th>
                <th>Measurable Element</th>
                <th>Checkpoints</th>
                <th>Facility Count</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>";
    }
    ?>
    <div class="card shadow-sm border mb-3">

    <div class="card-header bg-light">

        <h6 class="mb-0">
            <i class="bi bi-download me-2"></i>
            Download Reports
        </h6>

    </div>

    <div class="card-body">

        <p class="text-muted mb-3">
            Download detailed indicator-wise performance
            reports for further review, monitoring, planning, and strategic
            decision-making.
        </p>

        <div class="row g-2">

            <div class="col-md-4">

                <a href="assets/get/download_gap_analysis.php"
                   class="btn btn-warning w-100 text-dark">

                    <i class="bi bi-file-earmark-excel"></i>

                    Download Complete Gap Analysis

                </a>

            </div>

            <div class="col-md-4">

                <a href="assets/get/download_all_compliant.php"
                   class="btn btn-warning w-100 text-dark">

                    <i class="bi bi-bar-chart-line"></i>

                    Download Indicator Performance

                </a>

            </div>

           

        </div>

    </div>

</div>
  </div>
</div>

<script>
$(document).ready(function () {
  // 🔹 Load Top Concern Tables
  $.ajax({
    url: 'assets/get/get_top_concerns.php',
    method: 'GET',
    dataType: 'json',
    success: function (res) {
      // With Standard Table
      if (res.with_standard) {
        $('#withStandardTable').DataTable({
          data: res.with_standard,
          columns: [
            { data: 'facilities_type' },
            { data: 'concern_name' },
            { data: 'standard' },
            {
              data: 'non_compliant_count',
              render: function (data, type, row) {
                const url = `assets/get/export_noncompliant_details.php?facility=${encodeURIComponent(row.facilities_type)}&concern=${encodeURIComponent(row.concern_name)}&standard=${encodeURIComponent(row.standard)}`;
                return `<a href="${url}" class="text-success fw-bold" title="Download detailed data for ${row.facilities_type}">
                          ${data} <i class="bi bi-download ms-1"></i>
                        </a>`;
              }
            }
          ],
          dom: 'Bfrtip',
          buttons: [{ extend: 'excelHtml5', title: 'Top Concern + Standard Report', text: '<i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel' }],
          pageLength: 5
        });
      }

      // Without Standard Table
      if (res.without_standard) {
        $('#withoutStandardTable').DataTable({
          data: res.without_standard,
          columns: [
            { data: 'facilities_type' },
            { data: 'concern_name' },
            {
              data: 'non_compliant_count',
              render: function (data, type, row) {
                const url = `assets/get/export_noncompliant_details1.php?facility=${encodeURIComponent(row.facilities_type)}&concern=${encodeURIComponent(row.concern_name)}`;
                return `<a href="${url}" class="text-success fw-bold" title="Download details for ${row.facilities_type} - ${row.concern_name}">
                          ${data} <i class="bi bi-download ms-1"></i>
                        </a>`;
              }
            }
          ],
          dom: 'Bfrtip',
          buttons: [{ extend: 'excelHtml5', title: 'Top Concern Report', text: '<i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel' }],
          pageLength: 5
        });
      }
    },
    error: function (err) {
      console.error('Error fetching top concerns:', err);
    }
  });

  // 🔹 Load Zone-wise Tables
  const zones = ['Red Zone', 'Yellow Zone', 'Orange Zone', 'Green Zone'];
  zones.forEach(zone => {
    let tableId = '#' + zone.toLowerCase().replaceAll(' ', '_') + '_table';
    $(tableId).DataTable({
      ajax: { url: 'assets/get/fetch_gap_data.php', type: 'POST', data: { zone: zone } },
      columns: [
		 { data: "facilities_type" },       
        { data: "compliance_label" },
        { data: "concern_name" },
        { data: "area_of_con_subtypedeatils" },
        { data: "standard" },
        { data: "csqa_reference_id" },
        { data: "Measurable_Element" },
        {data:"Checkpoint"},
        { data: "facility_count" }
      ],
      dom: 'Bfrtip',
      buttons: [{ extend: 'excelHtml5', title: zone + ' Compliance Report', text: '<i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel' }],
      pageLength: 5
    });
  });
});
</script>
<script>

function toggleGapInfo()
{
    let box =
        document.getElementById('gapInfoBox');

    let icon =
        document.getElementById('gapIcon');

    if(box.style.display==='none')
    {
        box.style.display='block';

        icon.innerHTML='−';
    }
    else
    {
        box.style.display='none';

        icon.innerHTML='+';
    }
}

</script>
<?php include("assets/head/f.php"); ?>
