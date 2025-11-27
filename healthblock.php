<?php include("assets/head/h.php"); ?>
<div class="pcoded-main-container">
    <div class="pcoded-content">
        <div class="pagetitle">
            <h5 class="fw-bold text-primary">
                Health Block Setup
            </h5>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <form class="row align-items-center" enctype="multipart/form-data" method="post" action="">
                            <!-- Select District -->
                            <div class="col-auto">

                                <select id="District1" name="District1" required class="form-control">
                                    <option value="">-- Select District --</option>
                                    <?php
                                    $call_q1 = "SELECT dist_id, Dist_name FROM dist_master";
                                    $q22 = mysqli_query($con, $call_q1);
                                    while ($row = mysqli_fetch_array($q22)) {
                                        echo "<option value=\"{$row['dist_id']}-{$row['Dist_name']}\">{$row['Dist_name']}</option>";
                                    }
                                    mysqli_free_result($q22);
                                    $con->next_result();
                                    ?>
                                </select>
                            </div>

                            <!-- Health Block Input -->
                            <div class="col-auto">

                                <input type="text" class="form-control" id="healthblock1" name="healthblock1" placeholder="Write Health block name" required>
                                <div id="block-message" style="color: red; font-size: 0.9rem;"></div>
                            </div>

                            <!-- Submit Button -->
                            <div class="col-auto">
                                <button type="submit" id="postsubmit" name="postsubmit" class="btn btn-primary w-100">Add Health Block</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <h6>Block Rectification</h6>
                    <div class="card-body">
                    </div>
                </div>
            </div>
        </div>
        <?php
        if (isset($_POST['postsubmit'])) {

            // Get the posted values
            $dist = $_POST["District1"];
            $block = $_POST["healthblock1"];

            // Separate the district ID and name
            list($dist_id1, $dist_name1) = explode('-', $dist);
            $dist = $dist_id1;
            $disname = $dist_name1;

            // Check if the Health Block already exists
            $check_query = "SELECT COUNT(*) AS block_count FROM block_master WHERE block_name = '$block' and dist_id=$dist";
            $check_result = mysqli_query($con, $check_query);
            $row = mysqli_fetch_assoc($check_result);

            if ($row['block_count'] > 0) {
                // Block name already exists, show a message
                echo "<div class='alert alert-danger'>Error: The Health Block '$block' already exists.</div>";
            } else {
                // Block name doesn't exist, proceed with insertion
                $query_insert = "INSERT INTO block_master (block_id, block_name, dist_name, dist_id)
                                        SELECT IFNULL(MAX(block_id), 0) + 1, '$block', '$disname', $dist
                                        FROM block_master";
                $insertresult = $con->query($query_insert);

                if ($insertresult) {
                    echo "<div class='alert alert-success'>Health block added successfully!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error: Could not add Health Block. Please try again.</div>";
                }
            }
        }
        ?>



        <!-- AJAX check block name -->
        <script>
            $(document).ready(function() {
                $('#healthblock1').on('keyup', function() {
                    var blockName = $(this).val().trim();
                    var districtId = $('#District1').val().split('-')[0];

                    if (blockName !== '' && districtId !== '') {
                        $.ajax({
                            url: 'assets/get/check_block.php',
                            type: 'POST',
                            data: {
                                block_name: blockName,
                                district_id: districtId
                            },
                            success: function(response) {
                                if (response === 'exists') {
                                    $('#block-message').text('This health block name already exists in the selected district.');
                                } else {
                                    $('#block-message').text('');
                                }
                            },
                            error: function() {
                                alert('An error occurred while checking the health block name.');
                            }
                        });
                    } else {
                        $('#block-message').text('');
                    }
                });
            });
        </script>
    </div>
</div>

<?php include("assets/head/f.php"); ?>