<?php include("assets/head/h.php"); ?>

<div class="pcoded-main-container">
    <div class="pcoded-content">
        <div class="row">
            <div class="col-sm-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-primary text-white text-center rounded-top-4">
                        <h5 class="mb-0">📊 Facility Department-wise Assessment Analysis</h5>
                    </div>

                    <div class="card-body bg-light">
                        <div class="mb-4">
                            <h6 class="text-dark mb-3">🔍 Select Facility Type & Department</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <select class="form-control" id="facilityTypeDropdown" name="facilityTypeDropdown" required>
                                        <option value="">-- Select Facility Type --</option>
                                        <?php
                                        $facTypeQuery = "SELECT fac_type_id, facilities_type FROM facilities_type ORDER BY facilities_type";
                                        $facTypeResult = mysqli_query($con, $facTypeQuery);
                                        while ($row = mysqli_fetch_assoc($facTypeResult)) {
                                            echo '<option value="' . $row['fac_type_id'] . '">' . $row['facilities_type'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <select class="form-control" id="departmentDropdown" name="departmentDropdown" required>
                                        <option value="">-- Select Department --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="dataContainer" class="pt-3">
                            <div class="alert alert-info text-center" role="alert">
                                Please select Facility Type & Department !
                            </div>
                        </div>

                        <div id="topIndicatorsContainer" class="pt-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        // Load departments on facility type change
        $('#facilityTypeDropdown').on('change', function () {
            var facTypeId = $(this).val();
            if (facTypeId !== '') {
                $.post('assets/get/load_departments.php', {
                    fac_type_id: facTypeId
                }, function (data) {
                    $('#departmentDropdown').html(data);
                    $('#dataContainer').html('<div class="alert alert-warning text-center">Select Department !</div>');
                    $('#topIndicatorsContainer').html('');
                });
            } else {
                $('#departmentDropdown').html('<option value="">-- Select Department --</option>');
                $('#dataContainer').html('<div class="alert alert-info text-center">Please select Facility Type & Department !</div>');
                $('#topIndicatorsContainer').html('');
            }
        });

        // Load data and top indicators when department is selected
        $('#departmentDropdown').on('change', function () {
            var deptId = $(this).val();
            var facTypeId = $('#facilityTypeDropdown').val();

            if (deptId && facTypeId) {
                // Load main table
                $.post('assets/get/load_facility_data.php', {
                    dept_id: deptId,
                    fac_type_id: facTypeId
                }, function (data) {
                    $('#dataContainer').html(data);

                    // Load top indicators
                    $.post('assets/get/load_top_indicators.php', {
                        dept_id: deptId,
                        fac_type_id: facTypeId
                    }, function (cardHtml) {
                        $('#topIndicatorsContainer').html(cardHtml);
                    });
                });
            } else {
                $('#dataContainer').html('<div class="alert alert-warning text-center">Please select Department </div>');
                $('#topIndicatorsContainer').html('');
            }
        });
    });
</script>

<?php include("assets/head/f.php"); ?>
