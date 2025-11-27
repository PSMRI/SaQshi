<?php include("assets/head/h.php"); ?>
<div class="pcoded-main-container">
    <div class="pcoded-content">
        <div class="pagetitle">
            <h5 class="fw-bold text-primary">
                User creation
            </h5>
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

                            <input type="hidden" id="fac_id" name="fac_id">
                            <input type="hidden" id="dist_id" name="dist_id">
                            <input type="hidden" id="block_id" name="block_id">
                            <input type="hidden" id="nin_no2" name="nin_no2">
                            <input type="hidden" id="Health_facilty_type" name="Health_facilty_type">

                            <!-- Submit Button -->
                            <div class="text-center">
                                <button type="submit" id="postsubmit" name="postsubmit" class="btn btn-primary">Create User</button>
                            </div>
                        </form>
                        <!-- End Facility Addition Form -->

                        <?php
                        if (isset($_POST['postsubmit'])) {
                            // Sanitize and prepare form data
                            $facname = $_POST["facility"];
                            $nin1 = (int)$_POST["nin_no2"];
                            $fac_id = $_POST["fac_id"];

                            $block_id = $_POST["block_id"];
                            $Health_facilty_type = $_POST["Health_facilty_type"];
                            $dist_id = (int)$_POST["dist_id"];  // Safe casting
                            $divount = "SELECT DISTINCT division_id FROM dist_master WHERE Dist_id = $dist_id";
                            $result = mysqli_query($con, $divount);
                            $dividcount = 0;

                            if ($result && mysqli_num_rows($result) == 1) {
                                $row = mysqli_fetch_assoc($result);
                                $dividcount = (int)$row['division_id'];
                            }

                            // Now $dividcount is either correct division_id or 0 if not found


                            // Now handle different facility types
                            if ($Health_facilty_type == 8) {
                                $query = "CALL hwc_user($nin1, $fac_id, $dist_id, $block_id, $Health_facilty_type,$dividcount)";
                                $query1 = $con->query($query);

                                if ($query1) {
                                    $con->next_result();
                                    echo "<div class='alert alert-success'>User created successfully! User id is $nin1@hwc and Default password is 12345</div>";
                                } else {
                                    echo "<div class='alert alert-danger'>Error: Could not create User. Please try again.</div>";
                                }
                                displayUserTable($fac_id, $con);
                            } elseif ($Health_facilty_type == 2 || $Health_facilty_type == 10) {
                                $option1 = 0;
                                $query = "CALL dh_usertest($nin1, $fac_id, $dist_id, $block_id, $Health_facilty_type, $option1, $dividcount)";
                                $query2 = $con->query($query);
                                $con->next_result();

                                displayUserTable($fac_id, $con);
                            } elseif ($Health_facilty_type == 3) {
                                $query = "CALL phc_user($nin1, $fac_id, $dist_id, $block_id, $Health_facilty_type,$dividcount)";
                                $query3 = $con->query($query);
                                $con->next_result();

                                displayUserTable($fac_id, $con);
                            } elseif ($Health_facilty_type == 1) {
                                $option = 0;
                                $query = "CALL chc_user($nin1, $fac_id, $dist_id, $block_id, $Health_facilty_type, $option, $dividcount)";
                                $query4 = $con->query($query);
                                $con->next_result();
                                displayUserTable($fac_id, $con);
                            } elseif ($Health_facilty_type == 4) {
                                $query = "CALL hwc_user_4($nin1, $fac_id, $dist_id, $block_id, $Health_facilty_type,$dividcount)";
                                $query1 = $con->query($query);

                                if ($query1) {
                                    $con->next_result();
                                    echo "<div class='alert alert-success'>User created successfully! User id is $nin1@hwc and Default password is 12345</div>";
                                } else {
                                    echo "<div class='alert alert-danger'>Error: Could not create User. Please try again.</div>";
                                }
                                displayUserTable($fac_id, $con);
                            }
                        }

                        // Reusable function to display user table
                        function displayUserTable($fac_id, $con)
                        {
                            $qury_get_user = "SELECT u_name, f_name FROM s_user WHERE fac_id_fk = $fac_id";
                            $result_user = mysqli_query($con, $qury_get_user);

                            if ($result_user && mysqli_num_rows($result_user) > 0) {
                                echo "<div class='alert alert-info'>User created successfully! User list:</div>";
                                echo "<table class='table table-bordered'>";
                                echo "<thead><tr><th>Username</th><th>Facility</th></tr></thead>";
                                echo "<tbody>";

                                while ($row = mysqli_fetch_assoc($result_user)) {
                                    echo "<tr><td>" . htmlspecialchars($row['u_name']) . "</td><td>" . htmlspecialchars($row['f_name']) . "</td></tr>";
                                }

                                mysqli_free_result($result_user);
                                echo "</tbody></table>";
                            } else {
                                echo "<div class='alert alert-info'>No users found for the selected facility.</div>";
                            }
                        }
                        ?>

                    </div><!-- End Tab Pane -->
                </div><!-- End Tab Content -->

            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body pt-3">
                        <form method="POST" action="#">
                            <div class="row mb-3">
                                <label for="bulk_district" class="col-md-4 col-lg-3 col-form-label">Select District for Bulk User Creation</label>
                                <div class="col-md-8 col-lg-9">
                                    <select class="form-control" id="bulk_district" name="bulk_district" required>
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

                            <div class="text-center">
                                <button type="submit" name="bulksubmit" class="btn btn-success">Create Users for All Facilities</button>
                            </div>
                        </form>

                        <?php
                        if (isset($_POST['bulksubmit'])) {

                            $dist_id = (int)$_POST['bulk_district'];

                            $stmt = $con->prepare("SELECT DISTINCT division_id FROM dist_master WHERE Dist_id = ?");
                            $stmt->bind_param("i", $dist_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $dividcount = 0;

                            if ($result && $result->num_rows == 1) {
                                $row = $result->fetch_assoc();
                                $dividcount = (int)$row['division_id'];
                            }
                            $stmt->close();

                            $facilityQuery = "
    SELECT f.fac_id, f.block_id, f.nin_no, f.Health_facilty_type, f.fac_name
    FROM facilities f
    WHERE f.dist_id = $dist_id 
    AND f.fac_id NOT IN (
        SELECT distinct fac_id_fk 
        FROM s_user 
        WHERE dist_id = $dist_id);";

                            $result = mysqli_query($con, $facilityQuery);

                            if ($result && mysqli_num_rows($result) > 0) {

                                $exportData = []; // For Excel export

                                echo "<div class='alert alert-info'>Bulk User List</div>";
                                echo "<table class='table table-bordered mt-3' id='export_table'>";
                                echo "<thead><tr><th>Dist</th><th>Block</th><th>Facility Name</th><th>Facility Type</th><th>Username</th><th>Default Password</th><th>Status</th></tr></thead><tbody>";

                                while ($row = mysqli_fetch_assoc($result)) {
                                    $fac_id = $row['fac_id'];
                                    $block_id = $row['block_id'];
                                    $nin1 = (int)$row['nin_no'];
                                    $Health_facilty_type = $row['Health_facilty_type'];
                                    $facility_name = $row['fac_name'];

                                    $statusMsg = "";
                                    $username = "--";
                                    $user_id = "--";

                                    if (empty($nin1)) {
                                        $statusMsg = "Skipped - Missing NIN";
                                        echo "<tr><td>$fac_id</td><td>$facility_name</td><td>$Health_facilty_type</td><td>--</td><td>--</td><td>$statusMsg</td></tr>";
                                        $exportData[] = [$fac_id, $facility_name, $Health_facilty_type, '--', '--', $statusMsg];
                                        continue;
                                    }

                                    if ($Health_facilty_type == 8) {
                                        $query = "CALL hwc_user($nin1, $fac_id, $dist_id, $block_id, $Health_facilty_type, $dividcount)";
                                    } elseif ($Health_facilty_type == 2 || $Health_facilty_type == 10) {
                                        $query = "CALL dh_usertest($nin1, $fac_id, $dist_id, $block_id, $Health_facilty_type, 0, $dividcount)";
                                    } elseif ($Health_facilty_type == 3) {
                                        $query = "CALL phc_user($nin1, $fac_id, $dist_id, $block_id, $Health_facilty_type, $dividcount)";
                                    } elseif ($Health_facilty_type == 1) {
                                        $query = "CALL chc_user($nin1, $fac_id, $dist_id, $block_id, $Health_facilty_type, 0, $dividcount)";
                                    } elseif ($Health_facilty_type == 4) {
                                        $query = "CALL hwc_user_4($nin1, $fac_id, $dist_id, $block_id, $Health_facilty_type, $dividcount)";
                                    } else {
                                        $statusMsg = "Skipped - Unknown Type";
                                        echo "<tr><td>$fac_id</td><td>$facility_name</td><td>$Health_facilty_type</td><td>--</td><td>--</td><td>$statusMsg</td></tr>";
                                        $exportData[] = [$fac_id, $facility_name, $Health_facilty_type, '--', '--', $statusMsg];
                                        continue;
                                    }

                                    $queryRes = $con->query($query);

                                    if ($queryRes) {
                                        $con->next_result();

                                        $userResult = mysqli_query($con, "SELECT 
    s.u_id, 
    s.u_name, 
    f.fac_name, 
    f.Dist_Name, 
    f.Block_Name, 
    t.facilities_type
FROM 
    s_user s
JOIN 
    facilities f ON s.fac_id_fk = f.fac_id
JOIN 
    facilities_type t ON f.Health_facilty_type = t.fac_type_id
WHERE 
    s.fac_id_fk = $fac_id
ORDER BY 
    s.u_id DESC 
LIMIT 1;
;
");
                                        if ($userResult && mysqli_num_rows($userResult) > 0) {
                                            $userRow = mysqli_fetch_assoc($userResult);
                                            $user_id = $userRow['u_id'];
                                            $username = $userRow['u_name'];
                                            $userfacname = $userRow['fac_name'];
                                            $userdistname = $userRow['Dist_Name'];
                                            $userblockname = $userRow['Block_Name'];
                                            $userfactypename = $userRow['facilities_type'];
                                            $statusMsg = "User Created";
                                        } else {
                                            $statusMsg = "User Created but ID Fetch Failed";
                                        }
                                    } else {
                                        $statusMsg = "Error: " . mysqli_error($con);
                                    }

                                    echo "<tr><td>$userdistname</td><td>$userblockname</td><td> $userfacname</td><td>  $userfactypename</td><td> $username</td><td>12345</td><td>$statusMsg</td></tr>";
                                    $exportData[] = [$userdistname, $userblockname, $userfacname, $userfactypename, $username, 12345, $statusMsg];
                                }

                                echo "</tbody></table>";

                                // Store export data in session for Excel
                                $_SESSION['export_data'] = $exportData;

                                echo "<form method='POST' action='assets/get/export_bulk_user_excel.php'>
                <button type='submit' class='btn btn-primary mt-3'>Download Excel</button>
              </form>";
                            } else {
                                echo "<div class='alert alert-warning'>No eligible facilities found for the selected district.</div>";
                            }
                        }
                        ?>



                    </div>
                </div>
            </div>
        </div>

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
                            url: "assets/get/get_facilities_by_block.php",
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

    </div>
</div>

<?php include("assets/head/f.php"); ?>