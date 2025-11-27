<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

if (isset($_SESSION['export_data']) && is_array($_SESSION['export_data'])) {

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=bulk_user_creation.xls");

    echo "Facility ID\tFacility Name\tFacility Type\tUsername\tUser ID\tStatus\n";

    foreach ($_SESSION['export_data'] as $row) {
        echo implode("\t", $row) . "\n";
    }

    unset($_SESSION['export_data']); // Clear data after download
} else {
    echo "No data available for export.";
}
?>
