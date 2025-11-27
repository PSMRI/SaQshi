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
                <th>Facility Count</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>";
    }
    ?>
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
        { data: "facility_count" }
      ],
      dom: 'Bfrtip',
      buttons: [{ extend: 'excelHtml5', title: zone + ' Compliance Report', text: '<i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel' }],
      pageLength: 5
    });
  });
});
</script>

<?php include("assets/head/f.php"); ?>
