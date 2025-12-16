<!-- ADD THIS at the top of your JS includes -->
<script src="assets/datatables/jquery-3.6.0.min.js"></script>

<script src="assets/js/vendor-all.min.js"></script>
<script src="assets/js/plugins/bootstrap.min.js"></script>
<script src="assets/js/pcoded.min.js"></script>

<!-- Apex Chart -->
<script src="assets/js/plugins/apexcharts.min.js"></script>

<!-- DataTable CSS + JS -->
<link rel="stylesheet" type="text/css" href="assets/datatables/jquery.dataTables.min.css" />
<script src="assets/datatables/jquery.dataTables.min.js"></script>

<!-- custom-chart js -->
<script src="assets/js/pages/dashboard-main.js"></script>
<link rel="stylesheet" href="assets/datatables/buttons.dataTables.min.css">
<script src="assets/datatables/jszip.min.js"></script>
<script src="assets/datatables/pdfmake.min.js"></script>
<script src="assets/datatables/vfs_fonts.js"></script>
<script src="assets/datatables/dataTables.buttons.min.js"></script>
<script src="assets/datatables/buttons.html5.min.js"></script>
<script src="assets/datatables/buttons.print.min.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
 </body>
<?php  /*
$__PAGE_END = microtime(true);
$pageExecMs = round(($__PAGE_END - $__PAGE_START) * 1000);

mysqli_query($con, "
    INSERT INTO app_perf_log
    (page_name, request_time, exec_time_ms, db_time_ms, query_count, ip_address)
    VALUES (
        '".basename($_SERVER['PHP_SELF'])."',
        NOW(),
        $pageExecMs,
        $__DB_TIME,
        $__QUERY_COUNT,
        '".$_SERVER['REMOTE_ADDR']."'
    )
");
*/
?>
</html>