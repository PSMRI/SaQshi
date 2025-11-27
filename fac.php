<?php include("assets/head/h.php"); ?>
<div class="pcoded-main-container">
    <div class="pcoded-content">
        <div class="pagetitle">
            <h5 class="fw-bold text-primary">
                Facility Setup
            </h5>
        </div>
        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body pt-3">
                        <!-- Bordered Tabs -->
                        <ul class="nav nav-tabs nav-tabs-bordered">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#health-block">
                                    <img src="assets/img/fac.png" alt="icon" style="width: 20px; height: 20px; margin-right: 8px;">
                                    Add Facility</button>
                            </li>
                        </ul>

                        <div class="tab-content pt-2">
                            <div class="tab-pane fade show active health-block pt-3" id="health-block">
                                <!-- Facility Addition Form -->
                                <form enctype="multipart/form-data" method="POST" action="#">
                                    <!-- Select District -->
                                    <div class="row mb-3">
                                        <label for="District1" class="col-md-4 col-lg-3 col-form-label">Select District</label>
                                        <div class="col-md-8 col-lg-9">
                                            <select id="District1" name="District1" required class="form-control">
                                                <option value="">- Select District -</option>
                                                <?php
                                                $districtQuery = "SELECT dist_id, Dist_name FROM dist_master";
                                                $result = mysqli_query($con, $districtQuery);
                                                while ($row = mysqli_fetch_array($result)) {
                                                    echo "<option value='{$row['dist_id']}-{$row['Dist_name']}'>{$row['Dist_name']}</option>";
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
                                            <select id="block" name="block" required class="form-control">

                                                <option value="0">- Select Health Block -</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Select Facility Type -->
                                    <div class="row mb-3">
                                        <label for="Facilty" class="col-md-4 col-lg-3 col-form-label">Select Facility Type</label>
                                        <div class="col-md-8 col-lg-9">
                                            <select id="Facilty" name="Facilty" required class="form-control">

                                                <?php
                                                $facilityQuery = "SELECT fac_type_id, facilities_type FROM facilities_type where fac_type_id in (1,2,3,8,4,9,10)";
                                                $result = mysqli_query($con, $facilityQuery);
                                                while ($row = mysqli_fetch_array($result)) {
                                                    echo "<option value='{$row['fac_type_id']}'>{$row['facilities_type']}</option>";
                                                }
                                                mysqli_free_result($result);
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Facility Name -->
                                    <div class="row mb-3">
                                        <label for="facname" class="col-md-4 col-lg-3 col-form-label">Facility Name</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input type="text" pattern="[A-Za-z\s]+" class="form-control" id="facname" name="facname" placeholder="Enter Facility Name" required>
                                        </div>
                                    </div>

                                    <!-- NIN Number -->
                                    <div class="row mb-3">
                                        <label for="nin_no" class="col-md-4 col-lg-3 col-form-label">NIN Number</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input type="text" pattern="^[0-9]{10}$" class="form-control" id="nin_no" name="nin_no" placeholder="Enter 10-digit NIN Number" maxlength="10" minlength="10" required>
                                            <div id="nin-message" style="color: red;"></div> <!-- Message container for NIN -->
                                        </div>
                                    </div>
                                    <!-- Submit Button -->
                                    <div class="text-center">
                                        <button type="submit" id="postsubmit" name="postsubmit" class="btn btn-primary">Add Facility</button>
                                    </div>
                                </form>
                                <!-- End Facility Addition Form -->

                                <?php
                                if (isset($_POST['postsubmit'])) {
                                    // Check if the NIN is already used
                                    $nin = mysqli_real_escape_string($con, $_POST['nin_no']);
                                    $checkNinQuery = "SELECT COUNT(*) AS nin_count FROM facilities WHERE NIN_no = '$nin'";
                                    $ninCheckResult = mysqli_query($con, $checkNinQuery);
                                    $row = mysqli_fetch_assoc($ninCheckResult);


                                    if ($row['nin_count'] > 0) {
                                        // If NIN exists, show an error
                                        echo "<div class='alert alert-danger'>Error: The NIN number '$nin' already exists.</div>";
                                    } else {
                                        // Proceed with the rest of the form insertion
                                        $facname = mysqli_real_escape_string($con, $_POST["facname"]);
                                        $factype = mysqli_real_escape_string($con, $_POST["Facilty"]);
                                        $dist = mysqli_real_escape_string($con, $_POST["District1"]);
                                        $block = mysqli_real_escape_string($con, $_POST["block"]);

                                        // Extract District and Block details
                                        list($dist_id, $dist_name) = explode('-', $dist);
                                        list($block_id, $block_name) = explode('-', $block);
                                        $divount = "select distinct division_id from dist_master where Dist_id=$dist_id";
                                        $result = mysqli_query($con, $divount);
                                        $count = mysqli_num_rows($result);
                                        if ($count == 1) {
                                            $row = mysqli_fetch_assoc($result);
                                            $dividcount = $row['division_id'];
                                        }
                                        // Insert the new facility data into the database
                                        $queryInsert = "INSERT INTO `facilities`
            (`state_name`, `Dist_Name`, `Block_Name`, `fac_name`, `Health_facilty_type`, `block_id`, `dist_id`, `state_code`, `NIN_no`,`division_id`)
            VALUES
            ('Bihar', '$dist_name', '$block_name', '$facname', '$factype', $block_id, $dist_id, 10, '$nin',$dividcount)";

                                        $insertResult = $con->query($queryInsert);

                                        // Check if the insertion was successful
                                        if ($insertResult) {
                                            echo "<div class='alert alert-success'>Health Facility added successfully!</div>";
                                        } else {
                                            echo "<div class='alert alert-danger'>Error: Could not add Health Facility. Please try again.</div>";
                                        }
                                    }
                                }
                                ?>
                            </div><!-- End Tab Pane -->
                        </div><!-- End Tab Content -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $("#District1").on('change', function() {
            var districtId = $(this).val();
            $.ajax({
                method: "POST",
                url: "assets/get/response_dist_create2.php",
                data: {
                    cid: districtId
                },
                datatype: "html",
                success: function(data) {
                    $("#block").html(data);
                }
            });
        });
        $('#nin_no').on('keyup', function() {
            var ninNumber = $(this).val().trim();

            // Only proceed if the NIN number is not empty
            if (ninNumber !== '') {
                $.ajax({
                    method: "POST",
                    url: "assets/get/check_nin.php", // Check if NIN exists
                    data: {
                        nin_no: ninNumber
                    },
                    success: function(response) {
                        if (response === 'exists') {
                            // If NIN exists, show an error message
                            $('#nin-message').text('This NIN number is already in use. Please enter a different NIN.');
                            $('#nin_no').focus(); // Focus the NIN input
                        } else {
                            // If NIN does not exist, clear the message
                            $('#nin-message').text('');
                        }
                    },
                    error: function() {
                        alert('Error checking NIN number.');
                    }
                });
            } else {
                $('#nin-message').text(''); // Clear message if NIN is empty
            }
        });

        // Form submission validation
        $('form').on('submit', function(e) {
            var facname = $('#facname').val().trim();
            var nin_no = $('#nin_no').val().trim();

            // Check if both fields are not empty
            if (facname === '' || nin_no === '') {
                e.preventDefault(); // Prevent form submission
                if (facname === '') {
                    alert('Please enter a Facility Name.');
                }
                if (nin_no === '') {
                    alert('Please enter a NIN Number.');
                }
            }
        });
    });
</script>
<?php include("assets/head/f.php"); ?>