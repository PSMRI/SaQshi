<?php
include("assets/head/h.php");
?>

<!-- [ Main Content ] start -->
<div class="pcoded-main-container">
    <div class="pcoded-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10">Record Operations </h5>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body pt-3">


                        <!-- Facility Addition Form -->
                        <form enctype="multipart/form-data" method="POST" action="#">
                            <!-- Select District -->
                            <div class="row mb-3">
                                <label for="District1" class="col-md-4 col-lg-3 col-form-label">Select District</label>
                                <div class="col-md-8 col-lg-9">

                                    <select class="form-control" id="District1" name="District1" required>
                                        <option value="">- Select District -</option>
                                        <?php
                                        $districtQuery = "SELECT dist_id, Dist_name FROM dist_master";
                                        $result = mysqli_query($con, $districtQuery);
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo "<option value='{$row['dist_id']}'>{$row['Dist_name']}</option>";
                                        }
                                        mysqli_free_result($result);
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Select Health Block -->
                            <div class="row mb-3">
                                <label for="block" class="col-md-4 col-lg-3 col-form-label">Select Health Block</label>
                                <div class="col-md-8 col-lg-9">
                                    <select class="form-control" id="block" name="block" required>
                                        <option value="0">- Select Health Block -</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Select Facility Type -->
                            <div class="row mb-3">
                                <label for="facility1234" class="col-md-4 col-lg-3 col-form-label">Select Facility</label>
                                <div class="col-md-8 col-lg-9">
                                    <select class="form-control" id="facility" name="facility" required>
                                        <option value="0">- Select Facility -</option>

                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="dataop" class="col-md-4 col-lg-3 col-form-label">Record Operations</label>
                                <div class="col-md-8 col-lg-9">
                                    <select class="form-control" id="dataop1" name="dataop1" required>                                       
                                        <option value="1">Delete Outcome</option>                                      
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="date1" class="col-md-4 col-lg-3 col-form-label">Select Month</label>
                                <div class="col-md-8 col-lg-9">
                                    <input type="month" class="form-control" name="date1" id="date1" required>
                                </div>
                            </div>


                            <input type="hidden" id="fac_id" name="fac_id">
                            <input type="hidden" id="dist_id" name="dist_id">
                            <input type="hidden" id="block_id" name="block_id">
                            <input type="hidden" id="nin_no2" name="nin_no2">
                            <input type="hidden" id="Health_facilty_type" name="Health_facilty_type">

                            <!-- Submit Button -->
                            <div class="text-center">
                                <button type="submit" id="postsubmit" name="postsubmit" class="btn btn-primary">Delete Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<?php
// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['postsubmit'])) {

    $district = $_POST['District1'] ?? '';
    $block = $_POST['block'] ?? '';
    $facility = $_POST['facility'] ?? '';
    $dataOp = $_POST['dataop1'] ?? '';
    $selectedMonth = $_POST['date1'] ?? '';

    if (empty($district) || empty($block) || empty($facility) || empty($dataOp) || empty($selectedMonth)) {
        echo "<script>alert('Please fill all required fields.');</script>";
        exit;
    }

  

    if ($dataOp == '2') {  // Delete Outcome for selected month
    $formattedMonth = date("Y/m", strtotime($selectedMonth));
mysqli_query($con, "SET SQL_SAFE_UPDATES = 0");
    $deleteQuery = "DELETE FROM outcome_values_in_old 
                    WHERE institute_id = '$facility' 
                    AND month_in = '$formattedMonth'";

    if (mysqli_query($con, $deleteQuery)) {
        echo "<script>alert('Outcome data deleted successfully for $formattedMonth.');</script>";
    } else {
        echo "<script>alert('Error deleting outcome data.');</script>";
    }
}
else {
        echo "<script>alert('Only Delete Outcome logic implemented currently.');</script>";
    }
}
?>
<script>
    $(document).ready(function() {

        // Fetch blocks based on selected district
        $("#District1").on('change', function() {
            var districtId = $(this).val();
            if (districtId) {
                $.ajax({
                    method: "POST",
                    url: "assets/get/response_dist_create.php",
                    data: {
                        cid: districtId
                    },
                    dataType: "html",
                    success: function(data) {
                        $("#block").html(data);
                        $("#facility").html('<option value="">Select Facility</option>');
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching blocks:", error);
                    }
                });
            } else {
                $("#block").html('<option value="">Select Block</option>');
                $("#facility").html('<option value="">Select Facility</option>');
            }
        });

        // Fetch facilities based on selected block
        $("#block").on('change', function() {
            var districtId = $("#District1").val();
            var blockId = $(this).val();
            if (districtId && blockId) {
                $.ajax({
                    method: "POST",
                    url: "assets/get/get_facilities_by_block1.php",
                    data: {
                        distid: districtId,
                        block_id: blockId
                    },
                    dataType: "html",
                    success: function(data) {
                        $("#facility").html(data);
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching facilities:", error);
                    }
                });
            } else {
                $("#facility").html('<option value="">Select Facility</option>');
            }
        });

        // Fetch facility details and populate hidden fields
        $("#facility").on('change', function() {
            var facilityId = $(this).val();
            if (facilityId) {
                $.ajax({
                    method: "POST",
                    url: "assets/get/get_facility_details.php",
                    data: {
                        facility_id: facilityId
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.error) {
                            console.error(data.error);
                            clearFacilityFields();
                        } else {
                            $("#fac_id").val(data.fac_id);
                            $("#dist_id").val(data.dist_id);
                            $("#block_id").val(data.block_id);
                            $("#nin_no2").val(data.nin_no);
                            $("#Health_facilty_type").val(data.Health_facilty_type);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching facility details:", error);
                        clearFacilityFields();
                    }
                });
            } else {
                clearFacilityFields();
            }
        });

        // Helper function to clear hidden fields
        function clearFacilityFields() {
            $("#fac_id").val('');
            $("#dist_id").val('');
            $("#block_id").val('');
            $("#nin_no2").val('');
            $("#Health_facilty_type").val('');
        }

    });
</script>

<?php include("assets/head/f.php"); ?>