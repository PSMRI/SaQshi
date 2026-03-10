<?php

/***************************************************
 OUTCOME ENTRY – FINAL STABLE VERSION (RESUME + FILE VIEW)
 ***************************************************/
include("assets/conn/db.php");


/***************************************************
 1) DEPARTMENT SELECTION
 ***************************************************/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['department_id'])) {
    session_start();
    $_SESSION['dept_id1']   = $_POST['department_id'];
    $_SESSION['dept_name1'] = $_POST['department_name'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* -----------------------------------------
   SESSION VARIABLES
------------------------------------------ */
$dept_id   = $_SESSION['dept_id1'] ?? 0;
$f_type_id = $_SESSION['f_type_id'] ?? 0;
$fid       = $_SESSION['u_facilityid'] ?? 0;
$dept_name = $_SESSION['dept_name1'] ?? '0';
/***************************************************
 2) AJAX SAVE OUTCOME
 ***************************************************/
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === "save_outcome") {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header("Content-Type: application/json");

    try {

        $required = ['u_facilityid', 'dept_id1', 'new_date1', 'assperiod', 'f_type_id'];
        foreach ($required as $r) {
            if (empty($_SESSION[$r])) {
                echo json_encode(["status" => "error", "msg" => "Session expired"]);
                exit;
            }
        }

        $fac   = $_SESSION['u_facilityid'];
        $dept  = $_SESSION['dept_id1'];
        $month = $_SESSION['new_date1'];
        $per   = $_SESSION['assperiod'];

        $ind = (int)$_POST['indicator_id'];

        $num = ($_POST['numerator'] !== "") ? (float)$_POST['numerator'] : null;
        $den = ($_POST['denominator'] !== "") ? (float)$_POST['denominator'] : null;
        $res = ($_POST['result_value'] !== "") ? (float)str_replace('%', '', $_POST['result_value']) : null;

        $opt = (!empty($_POST['selected_option'])) ? trim($_POST['selected_option']) : null;

        if ($res === null && $num !== null && $den !== null && $den != 0) {
            $res = round($num / $den, 2);
        }

        if (!is_numeric($res)) {
            echo json_encode(["status" => "error", "msg" => "Invalid result"]);
            exit;
        }

        /* CHECK EXISTING */
        $chk = $con->prepare("
            SELECT outcome_id_values,
            (SELECT file_path FROM outcome_value_files 
             WHERE outcome_value_id=outcome_id_values LIMIT 1) old_file
            FROM outcome_values_in
            WHERE month_in=? AND institute_id=? AND dept_id=? AND id_out_hwc=?
        ");
        $chk->bind_param("siii", $month, $fac, $dept, $ind);
        $chk->execute();
        $exist = $chk->get_result()->fetch_assoc();
        $chk->close();

        if ($exist) {

            $oid = $exist['outcome_id_values'];
            $oldFile = $exist['old_file'];

            $u = $con->prepare("
                UPDATE outcome_values_in
                SET values_in=?, neu_val=?, deno_val=?
                WHERE outcome_id_values=?
            ");
            $u->bind_param("dddi", $res, $num, $den, $oid);
            $u->execute();
            $u->close();

        } else {

            $i = $con->prepare("CALL insert_outcome_values(?,?,?,?,?,?,?,?)");
            $i->bind_param("idsiiidd", $ind, $res, $month, $fac, $dept, $per, $den, $num);
            $i->execute();
            $i->close();

            /* 🔥 VERY IMPORTANT FOR STORED PROCEDURE */
            while ($con->more_results() && $con->next_result()) {}

            $g = $con->prepare("
                SELECT outcome_id_values 
                FROM outcome_values_in
                WHERE month_in=? AND institute_id=? AND dept_id=? AND id_out_hwc=?
                ORDER BY outcome_id_values DESC LIMIT 1
            ");
            $g->bind_param("siii", $month, $fac, $dept, $ind);
            $g->execute();
            $oid = $g->get_result()->fetch_assoc()['outcome_id_values'];
            $g->close();

            $oldFile = null;
        }

        /* FILE UPLOAD (Optional) */
        $path = $oldFile;

        if (!empty($_FILES['evidence_file']['name'])) {

            $ext = strtolower(pathinfo($_FILES['evidence_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

            if (!in_array($ext, $allowed)) {
                echo json_encode(["status" => "error", "msg" => "Invalid file"]);
                exit;
            }

            $dir = "uploads/outcome/$fac/$month/";
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            if ($oldFile && file_exists($oldFile)) unlink($oldFile);

            $path = $dir . time() . "_" . $ind . "." . $ext;

            if (!move_uploaded_file($_FILES['evidence_file']['tmp_name'], $path)) {
                echo json_encode(["status" => "error", "msg" => "File upload failed"]);
                exit;
            }
        }

        /* FILE TABLE (Optional) */
        if ($opt !== null || !empty($path)) {

            $fchk = $con->prepare("SELECT id FROM outcome_value_files WHERE outcome_value_id=?");
            $fchk->bind_param("i", $oid);
            $fchk->execute();
            $have = $fchk->get_result()->fetch_assoc();
            $fchk->close();

            if ($have) {

                $fu = $con->prepare("
                    UPDATE outcome_value_files
                    SET selected_option=?, file_path=?
                    WHERE outcome_value_id=?
                ");
                $fu->bind_param("ssi", $opt, $path, $oid);
                $fu->execute();
                $fu->close();

            } else {

                $fi = $con->prepare("
                    INSERT INTO outcome_value_files(outcome_value_id,selected_option,file_path)
                    VALUES (?,?,?)
                ");
                $fi->bind_param("iss", $oid, $opt, $path);
                $fi->execute();
                $fi->close();
            }
        }

        echo json_encode([
            "status" => "success",
            "file"   => $path ?? null
        ]);

        exit;

    } catch (Exception $e) {

        echo json_encode([
            "status" => "error",
            "msg" => $e->getMessage()
        ]);
        exit;
    }
}
include("assets/head/h.php");
$showDeptModal = empty($_SESSION['dept_id1']) || $_SESSION['dept_id1'] == 0;
$dept_name = $_SESSION['dept_name1'] ?? '';  // For showing in header

/***************************************************
 UI START
 ***************************************************/

?>

<style>
    .progress-mini {
        height: 6px
    }

    .loader-box {
        display: none;
        text-align: center;
        padding: 30px
    }

    .saved-border {
        border: 2px solid #28a745
    }
</style>

<div class="pcoded-main-container">
    <div class="pcoded-content">

        <h5 class="fw-bold text-primary mb-2">
            <i class="bi bi-bar-chart-fill me-2"></i>
            Outcome Indicators – <?= $_SESSION['dept_name1'] ?? '' ?>
            <button type="button" class="btn btn-sm btn-link text-warning ms-2" data-toggle="modal" data-target="#departmentModal">
                <?= ($_SESSION['facilty_type'] == 8)
                    ? "Change Checklist"
                    : "Change Department"; ?>
            </button>
        </h5>

        <!-- MONTH -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="post" onsubmit="showLoader()">
                    <label class="fw-bold">Month</label>
                    <input type="month" name="date1" class="form-control"
                        value="<?= $_POST['date1'] ?? date('Y-m') ?>"
                        min="<?= date('Y-m', strtotime('-6 months')) ?>"
                        max="<?= date('Y-m') ?>" required>
                    <button class="btn btn-primary mt-2" name="submit1">Fill Data</button>
                </form>
            </div>
        </div>

        <!-- LOADER -->
        <div class="card loader-box" id="loaderBox">
            <div class="card-body">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2 mb-0">
                    Checking filled indicators…<br>
                    Preparing pending list…
                </p>
            </div>
        </div>

        <?php
        /***************************************************
 LOAD INDICATORS + RESUME
         ***************************************************/
        if (isset($_POST['submit1'])) {

            $_SESSION['new_date1'] = $_POST['date1'];
            $fac = $_SESSION['u_facilityid'];
            $did = $_SESSION['dept_id1'] ?? 0;
            $ft  = $_SESSION['f_type_id'];

            /* FETCH FILLED DATA */
            $filled = [];
            $fq = $con->prepare("
    SELECT v.id_out_hwc,v.neu_val,v.deno_val,v.values_in,f.file_path
    FROM outcome_values_in v
    LEFT JOIN outcome_value_files f ON f.outcome_value_id=v.outcome_id_values
    WHERE v.month_in=? AND v.institute_id=? AND v.dept_id=?
");
            $fq->bind_param("sii", $_POST['date1'], $fac, $did);
            $fq->execute();
            $fr = $fq->get_result();
            while ($x = $fr->fetch_assoc()) $filled[$x['id_out_hwc']] = $x;

            /* ALL INDICATORS */
            $q = $con->prepare("
SELECT id_out_hwc,out_come_hwcindi,num,deno,out_come_source
FROM out_come_dh
WHERE out_come_hwc_factype=? AND out_come_dept=?
");
            $q->bind_param("ii", $ft, $did);
            $q->execute();
            $rs = $q->get_result();

            $total = $rs->num_rows;
            $inds = [];
            while ($r = $rs->fetch_assoc()) $inds[] = $r;

            /* FIND RESUME INDEX */
            $resume = 1;
            $i = 0;
            foreach ($inds as $r) {
                $i++;
                if (!isset($filled[$r['id_out_hwc']])) {
                    $resume = $i;
                    break;
                }
            }

            echo "<form>";

            $i = 0;
            foreach ($inds as $r) {
                $i++;
                $isFilled = isset($filled[$r['id_out_hwc']]);
                $show = ($i == $resume);
                $readonly = ($r['deno'] === 'N/A') ? 'readonly' : '';

                $numVal = $filled[$r['id_out_hwc']]['neu_val'] ?? '';
                $denVal = $filled[$r['id_out_hwc']]['deno_val'] ?? '';
                $resVal = $filled[$r['id_out_hwc']]['values_in'] ?? '';
                $filePath = $filled[$r['id_out_hwc']]['file_path'] ?? '';
        ?>

                <div class="card mb-3 <?= $isFilled ? 'saved-border' : '' ?>"
                    id="card<?= $i ?>" <?= $show ? '' : 'style="display:none"' ?>>
                    <div class="card-body">

                        <div class="d-flex justify-content-between mb-1">
                            <small><?= $i ?>/<?= $total ?></small>
                            <small><?= round(($i / $total) * 100) ?>%</small>
                        </div>
                        <div class="progress progress-mini mb-2">
                            <div class="progress-bar" style="width:<?= ($i / $total) * 100 ?>%"></div>
                        </div>

                        <h6 class="text-primary"><?= htmlspecialchars($r['out_come_hwcindi']) ?></h6>

                        <div class="alert alert-info small">
                            Expected: Num <b><?= $r['num'] ?></b>,
                            Den <b><?= $r['deno'] ?></b>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <input type="number" class="form-control"
                                    id="input<?= $i * 2 - 1 ?>" value="<?= $numVal ?>"
                                    oninput="calculateResult(<?= $i ?>)">
                            </div>
                            <div class="col-md-4">
                                <input type="number" class="form-control"
                                    id="input<?= $i * 2 ?>" value="<?= $denVal ?>"
                                    <?= $readonly ?> oninput="calculateResult(<?= $i ?>)">
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control"
                                    id="result<?= $i ?>" value="<?= $resVal ?>" readonly>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="fw-bold">Source of Verification(Optional)</label>
                                <select id="opt<?= $i ?>" class="form-control">
                                    <option value="">-- Optional --</option>
                                    <option value="<?= htmlspecialchars($r['out_come_source']) ?>">
                                        <?= htmlspecialchars($r['out_come_source']) ?>
                                    </option>
                                </select>

                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold">Upload Evidence (Optional)</label>
                                <input type="file"
                                    id="file<?= $i ?>"
                                    class="form-control"
                                    accept="image/*,.pdf"
                                    onchange="handleFileSelect(<?= $i ?>)">


                                <!-- 🔥 UPLOAD PROGRESS BAR (INSERT HERE) -->
                                <div class="progress mt-1"
                                    style="height:8px; display:none"
                                    id="progressBox<?= $i ?>">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                        id="progressBar<?= $i ?>"
                                        style="width:0%">
                                    </div>
                                </div>

                                <div id="fileStatus<?= $i ?>"
                                    class="small text-muted mt-1"
                                    style="display:none"></div>
                                <div id="fileView<?= $i ?>" class="mt-1">
                                    <?php if ($filePath): ?>
                                        <small>
                                            <a href="<?= $filePath ?>" target="_blank">View uploaded file</a>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>

                        <input type="hidden" id="ind<?= $i ?>" value="<?= $r['id_out_hwc'] ?>">

                        <div class="d-flex justify-content-between mt-3">
                            <?php if ($i > 1): ?>
                                <button type="button" class="btn btn-info" onclick="back(<?= $i ?>,event)"><i class="feather mr-2 icon-info"></i>
                                    Back</button>
                            <?php else: ?><div></div><?php endif; ?>


                            <div>
                                <button type="button" class="btn btn-danger" onclick="skipCard(<?= $i ?>,<?= $total ?>,event)"><i class="feather mr-2 icon-slash"></i>
                                    Skip</button>
                                <button type="button" class="btn btn-success" onclick="saveNext(<?= $i ?>,<?= $total ?>,event)"><i class="feather mr-2 icon-check-circle"></i>
                                    Save & Next
                                </button>
                            </div>
                        </div>


                    </div>
                </div>

        <?php
            }
            echo "</form>";

            /* CALCULATION JS */
            if ($ft == 3)
                echo "<script src='assets/calculationjs/departmentphc{$did}.js?v=" . time() . "'></script>";
            elseif ($ft == 9)
                echo "<script src='assets/calculationjs/departmentaphc{$did}.js?v=" . time() . "'></script>";
            elseif ($ft == 8 || $ft == 4)
                echo "<script src='assets/calculationjs/departmenthwc2{$did}.js?v=" . time() . "'></script>";
            elseif ($ft == 1)
                echo "<script src='assets/calculationjs/chc{$did}.js?v=" . time() . "'></script>";
            else
                echo "<script src='assets/calculationjs/department{$did}.js?v=" . time() . "'></script>";
        }
        ?>

        <div id="actionMsg"
            class="alert alert-info py-1 px-2 small"
            style="display:none"></div>
    </div>
</div>
<!-- Department Modal -->
<!-- Department Modal -->
<div id="departmentModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="departmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="departmentModalLabel">
                        <?php echo ($_SESSION['facilty_type'] == 8)
                            ? "Select Checklist"
                            : "Select Department"; ?>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden input to store department_name -->
                    <input type="hidden" name="department_name" id="department_name_input" value="">

                    <label for="departmentSelect" class="form-label">
                        <?php echo ($_SESSION['facilty_type'] == 8)
                            ? "Checklist"
                            : "Department"; ?>
                    </label>
                    <select class="mb-3 form-control form-control-sm" id="departmentSelect" name="department_id" required>
                        <option value=""> <?php echo ($_SESSION['facilty_type'] == 8)
                                                ? "--Select Checklist--"
                                                : "--Select Department--"; ?></option>
                        <?php
                        $factype = $_SESSION['f_type_id'];
                        $facid = $_SESSION['u_facilityid'];
                        $assid = $_SESSION['assperiod'];
                        $query = "SELECT DISTINCT a.fac_dept_id_fk, b.dept_name 
                                  FROM concern_subtype_chklist AS a 
                                  JOIN fac_department AS b ON a.fac_dept_id_fk = b.fac_dept_id 
                                  WHERE a.fac_type_id_fk = ? AND a.fac_dept_id_fk IN (
                                      SELECT fac_dept_id FROM fac_dept_map 
                                      WHERE fac_id = ? AND acc_id = ?
                                  )";
                        $stmt = $con->prepare($query);
                        $stmt->bind_param("iii", $factype, $facid, $assid);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['fac_dept_id_fk']}' data-name='{$row['dept_name']}'>{$row['dept_name']}</option>";
                        }
                        $stmt->close();
                        ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Continue</button>
                </div>
            </div>
        </form>
    </div>
    <script>
        $('#departmentSelect').change(function() {
            var deptName = $('#departmentSelect option:selected').data('name');
            $('#department_name_input').val(deptName);
        });
    </script>
    <script>
        function showLoader() {
            document.getElementById('loaderBox').style.display = 'block';
        }

        function skipCard(i, t) {
            event.preventDefault();
            if (i < t) {
                card(i).style.display = 'none';
                card(i + 1).style.display = 'block';
            }
        }

        function back(i) {
            event.preventDefault();
            card(i).style.display = 'none';
            card(i - 1).style.display = 'block';
        }

        function card(i) {
            return document.getElementById('card' + i);
        }
    </script>
    <script>
        /* ===============================
   HELPERS
================================ */
        let skippedAny = false;

        function card(i) {
            return document.getElementById('card' + i);
        }

        function showCard(i) {
            document.querySelectorAll('[id^="card"]').forEach(c => {
                c.style.display = 'none';
            });
            if (card(i)) card(i).style.display = 'block';
        }

        function showMsg(text, type = "info") {
            const m = document.getElementById("actionMsg");
            m.className = "alert alert-" + type + " py-2 px-3 fs-6 fw-bold mb-2 text-center";
            m.innerHTML = text;
            m.style.display = "block";

            setTimeout(() => {
                m.style.display = "none";
            }, 2500);
        }


        /* ===============================
           NAVIGATION
        ================================ */
        function skipCard(i, total, e) {
            e.preventDefault();
            skippedAny = true;

            showMsg(
                "⏭ <b>Indicator skipped.</b>",
                "warning"
            );
            if (i < total) {
                showCard(i + 1);
            } else {
                // If skip happens on LAST card
                showMsg(
                    i,
                    "⚠️ <b>You reached the end, but some indicators were skipped.</b><br>" +
                    "🔁 Please go back and complete them.",
                    "warning"
                );
            }
        }


        function back(i, e) {
            e.preventDefault();
            showMsg(
                "⬅ <b>Moved to previous indicator</b>",
                "secondary"
            );
            if (i > 1) showCard(i - 1);
        }

        /* ===============================
           SAVE + NEXT
        ================================ */
    </script>
    <script>
        /* ===============================
   IMAGE COMPRESSION (CLIENT SIDE)
   Compress only images > 10MB
================================ */
        function compressImage(file, callback) {

            if (!file || !file.type.startsWith("image/")) {
                callback(file);
                return;
            }

            if (file.size <= 10 * 1024 * 1024) {
                callback(file);
                return;
            }

            const img = new Image();
            const reader = new FileReader();

            reader.onload = e => img.src = e.target.result;

            img.onload = () => {
                const canvas = document.createElement("canvas");
                const ctx = canvas.getContext("2d");

                const scale = Math.sqrt((10 * 1024 * 1024) / file.size);
                canvas.width = img.width * scale;
                canvas.height = img.height * scale;

                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                canvas.toBlob(blob => {
                    const compressed = new File([blob], file.name, {
                        type: file.type || "image/jpeg",
                        lastModified: Date.now()
                    });
                    callback(compressed);
                }, "image/jpeg", 0.7);
            };

            reader.readAsDataURL(file);
        }

        /* ===============================
           SAVE + NEXT (FINAL)
        ================================ */
        function saveNext(i, total, e) {
            e.preventDefault();

            const progressBox = document.getElementById("progressBox" + i);
            const progressBar = document.getElementById("progressBar" + i);

            progressBox.style.display = "block";
            progressBar.style.width = "5%";

            const fd = new FormData();
            fd.append("action", "save_outcome");
            fd.append("indicator_id", document.getElementById("ind" + i).value);
            fd.append("numerator", document.getElementById("input" + (i * 2 - 1)).value);
            fd.append("denominator", document.getElementById("input" + (i * 2)).value);
            fd.append("result_value", document.getElementById("result" + i).value);
            let selectedOption = document.getElementById("opt" + i).value || "";
            fd.append("selected_option", selectedOption);


            if (selectedFiles[i]) {
                fd.append("evidence_file", selectedFiles[i]);
            }

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);

            xhr.upload.onprogress = e => {
                if (e.lengthComputable) {
                    progressBar.style.width =
                        Math.round((e.loaded / e.total) * 100) + "%";
                }
            };

            xhr.onload = () => {
                progressBar.style.width = "100%";
                setTimeout(() => progressBox.style.display = "none", 400);

                try {
                    const res = JSON.parse(xhr.responseText);

                    if (res.status === "success") {
                        card(i).classList.add("saved-border");

                        if (res.file) {
                            document.getElementById("fileView" + i).innerHTML =
                                `<small><a href="${res.file}" target="_blank">View uploaded file</a></small>`;
                        }

                        showMsg("✅ <b>Indicator saved successfully</b>", "success");

                        if (i < total) {
                            showCard(i + 1);
                        } else {
                            showMsg(
                                skippedAny ?
                                "⚠️ Some indicators were skipped. Please review." :
                                "🎉 You have reached the end!",
                                skippedAny ? "warning" : "success"
                            );
                        }
                    } else {
                        showMsg(res.msg || "Save failed", "danger");
                    }

                } catch {
    console.log("RAW RESPONSE:");
    console.log(xhr.responseText);
    showMsg("Server response error", "danger");
}

            };

            xhr.onerror = () => {
                progressBox.style.display = "none";
                showMsg("Network error", "danger");
            };

            xhr.send(fd);
        }
    </script>
    <script>
        const selectedFiles = {};
        //let skippedAny = false;
    </script>
    <script>
        function handleFileSelect(i) {

            const input = document.getElementById("file" + i);
            const file = input.files[0];
            if (!file) return;

            const progressBox = document.getElementById("progressBox" + i);
            const progressBar = document.getElementById("progressBar" + i);
            const statusBox = document.getElementById("fileStatus" + i);

            progressBox.style.display = "block";
            progressBar.style.width = "20%";
            statusBox.style.display = "block";

            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            statusBox.innerHTML = `📂 <b>${file.name || "Selected file"}</b> (${sizeMB} MB)`;

            const isImage = file.type.startsWith("image") || file.type === "";

            if (isImage && file.size > 10 * 1024 * 1024) {

                statusBox.innerHTML += "<br>🗜 Compressing image…";
                progressBar.style.width = "50%";

                compressImage(file, compressed => {
                    selectedFiles[i] = compressed;
                    progressBar.style.width = "100%";
                    statusBox.innerHTML = "✅ Image ready for upload";
                    setTimeout(() => progressBox.style.display = "none", 600);
                });

            } else {
                selectedFiles[i] = file;
                progressBar.style.width = "100%";
                statusBox.innerHTML += "<br>✅ File ready";
                setTimeout(() => progressBox.style.display = "none", 400);
            }
        }
    </script>
    <script>
        $(document).ready(function() {
            <?php if ($showDeptModal): ?>
                $('#departmentModal').modal({
                    backdrop: 'static',
                    keyboard: false
                });
                $('#departmentModal').modal('show');
            <?php endif; ?>
        });
    </script>
    <?php include("assets/head/f.php"); ?>