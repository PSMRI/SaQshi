<?php
//session_start();
include("assets/conn/db.php"); // mysqli $conn
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Activity Audit Log</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f9;
            font-size: 0.875rem;
        }

        .page-title {
            font-weight: 600;
            letter-spacing: .2px;
        }

        .card {
            border-radius: 8px;
        }

        .table thead th {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .table td {
            vertical-align: middle;
        }

        .audit-desc {
            max-width: 360px;
            white-space: normal;
            color: #495057;
        }

        .badge-action {
            font-size: 0.7rem;
        }

        .header-meta {
            font-size: 0.75rem;
        }
    </style>
</head>
<body>

<div class="container-fluid px-4 mt-4">

    <!-- Page Header -->
    <div class="mb-3">
        <h4 class="page-title mb-1">User Activity Audit Log</h4>
        <div class="text-muted header-meta">
            Live system audit feed · Auto refresh every 10 seconds · Read-only
        </div>
    </div>

    <!-- Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Audit Records</strong><br>
                    <small class="text-muted">
                        System generated, non-editable activity trail
                    </small>
                </div>
                <a href="assets/get/export_user_activity_log.php"
                   class="btn btn-outline-primary btn-sm">
                    ⬇ Export to Excel
                </a>
            </div>
        </div>

        <div class="card-body pt-3">

            <div class="table-responsive">
                <table id="auditTable" class="table table-bordered table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Description</th>
                            <th>Reference</th>
                            <th>IP Address</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                </table>
            </div>

        </div>
    </div>

</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function () {

    $('#auditTable').DataTable({
        ajax: {
            url: 'assets/get/watch_1_get.php',
            dataSrc: 'data'
        },
        processing: true,
        serverSide: false,
        pageLength: 20,
        lengthMenu: [10, 20, 50, 100],
        order: [[7, 'desc']],
        columnDefs: [
            { targets: [0,7], className: 'text-center' },
            { targets: 4, className: 'audit-desc' }
        ],
        language: {
            processing: "Loading audit records…",
            emptyTable: "No audit activity found"
        }
    });

});
</script>

</body>
</html>
