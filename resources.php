<?php
include("assets/conn/db.php");
//session_start();

/* =========================
   🔹 DOWNLOAD (UNCHANGED)
========================= */
if (isset($_GET['download_id'])) {

    $id = (int)$_GET['download_id'];

    $stmt = $con->prepare("SELECT file_path, file_name FROM files WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {

        $file = $row['file_path'];

        if (file_exists($file)) {

            $con->query("UPDATE files SET download_count=download_count+1 WHERE id=$id");

            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"" . basename($file) . "\"");
            readfile($file);
            exit;
        }
    }
}

/* =========================
   🔹 DELETE (ADDED)
========================= */
if (isset($_POST['delete_id'])) {

    $id = (int)$_POST['delete_id'];

    $res = $con->query("SELECT file_path FROM files WHERE id=$id");
    if ($row = $res->fetch_assoc()) {

        if (file_exists($row['file_path'])) unlink($row['file_path']);
        $con->query("DELETE FROM files WHERE id=$id");
    }

    echo "deleted";
    exit;
}

/* =========================
   🔹 UPLOAD (ADDED FIXED)
========================= */
$upload_dir = "assets/doc/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 🔒 prevent HTML output
    ob_clean();

    if (!isset($_FILES['file'])) {
        echo "error:No file received";
        exit;
    }

    if ($_FILES['file']['error'] != 0) {
        echo "error:Upload error code " . $_FILES['file']['error'];
        exit;
    }

    $file_name = $_POST['file_name'];
    $facility_type = $_POST['facility_type'];
$facility_filetype=$_POST['file_type'];
   $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];

$originalName = $_FILES['file']['name'];
$tmpName      = $_FILES['file']['tmp_name'];

$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions)) {
    echo "error:Invalid file type. Only pdf,doc,docx,xls,xlsx,jpg,jpeg,png files type are allowed.";
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $tmpName);
finfo_close($finfo);

$allowedMimeTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'image/jpeg',
    'image/png'
];

if (!in_array($mime, $allowedMimeTypes)) {
    echo "error:Invalid file content type.";
    exit;
}

$new_name = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($originalName));
$target = $upload_dir . $new_name;

if (!move_uploaded_file($tmpName, $target)) {
    echo "error:File move failed";
    exit;
}

    $stmt = $con->prepare("INSERT INTO files (file_name,file_path,facility_type,file_type) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $file_name, $target, $facility_type,$facility_filetype);

    if (!$stmt->execute()) {
        echo "error:File upload failed";
        exit;
    }

    echo "success";
    exit;
}

/* =========================
   🔹 SAME ORIGINAL LOGIC (NO CHANGE)
========================= */

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$q = $_GET['q'] ?? '';
$like = "%$q%";

$selectedFacility = $_GET['facility'] ?? 'All';

/* COUNTS */
$counts = [];
$totalAll = 0;

$sql = "SELECT facility_type, COUNT(*) ct FROM files GROUP BY facility_type";
$res = $con->query($sql);

while ($r = $res->fetch_assoc()) {
    $counts[$r['facility_type']] = $r['ct'];
    $totalAll += $r['ct'];
}

$facTypes = array_keys($counts);
sort($facTypes);
array_unshift($facTypes, 'All');

/* TOTAL */
if ($selectedFacility == 'All') {
    $stmt = $con->prepare("SELECT COUNT(*) total FROM files WHERE file_name LIKE ?");
    $stmt->bind_param("s", $like);
} else {
    $stmt = $con->prepare("SELECT COUNT(*) total FROM files WHERE facility_type=? AND file_name LIKE ?");
    $stmt->bind_param("ss", $selectedFacility, $like);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];

$total_pages = ceil($total / $limit);

/* FETCH */
if ($selectedFacility == 'All') {
    $stmt = $con->prepare("SELECT * FROM files WHERE file_name LIKE ? ORDER BY uploaded_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $like, $limit, $offset);
} else {
    $stmt = $con->prepare("SELECT * FROM files WHERE facility_type=? AND file_name LIKE ? ORDER BY uploaded_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ssii", $selectedFacility, $like, $limit, $offset);
}
$stmt->execute();
$listRes = $stmt->get_result();

include("assets/head/h.php");
?>

<div class="pcoded-main-container">
    <div class="pcoded-content container-fluid py-3">

        <h5 class="text-primary">📁 Resource Manager (Admin)</h5>

        <!-- SUCCESS -->
        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['msg']; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>



        <?php unset($_SESSION['msg']);
        endif; ?>

        <!-- UPLOAD -->
        <div class="card mb-3">
            <div class="card-body">
                <form id="uploadForm" method="POST" enctype="multipart/form-data" class="row">

                    <div class="col-md-3">
                        <input type="text" name="file_name" class="form-control" placeholder="File Name" required>
                    </div>
 <div class="col-md-2">

                        <select name="file_type" class="form-control" required>
                            <option value="">Select File Type</option>
                            <option value="Letter">Letter</option>
                            <option value="Forms">Forms</option>
                            <option value="Documents">Documents</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="facility_type" class="form-control">
                            <option value="">Applicable For</option>
                            <option>HWC</option>
                            <option>PHC</option>
                            <option>CHC</option>
                            <option>DH</option>
                            <option>LaQshya</option>
                            <option>MusQan</option>
                            <option>National</option>
                            <option>State</option>
                            <option>Regional</option>
                            <option>District & Block</option>
                            <option>Miscellaneous</option>
                        </select>
                    </div>
                   
                    <div class="col-md-3">
                        <input type="file" name="file" class="form-control" required>
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-success w-100">Upload</button>
                    </div>

                </form>
                <div class="progress mt-3" style="height:20px; display:none;" id="progressBox">
                    <div class="progress-bar bg-success" id="progressBar" style="width:0%">0%</div>
                </div>
            </div>
        </div>

        <!-- SEARCH -->
        <form method="get" class="d-flex mb-2">
            <input type="hidden" name="facility" value="<?= $selectedFacility ?>">
            <input type="text" id="liveSearch" name="q" value="<?= $q ?>" class="form-control" placeholder="Search files...">
            <button class="btn btn-primary">Search</button>
        </form>
        <div class="card">
            <div class="card-body">
                <!-- TABS -->
                <ul class="nav nav-tabs mb-3">
                    <?php foreach ($facTypes as $ft): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($ft == $selectedFacility ? 'active' : '') ?>"
                                href="?facility=<?= $ft ?>&q=<?= $q ?>">
                                <?= $ft ?> <span class="badge bg-secondary"><?= ($ft == 'All' ? $totalAll : ($counts[$ft] ?? 0)) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- TABLE (SAME STYLE) -->
                <table class="table table-bordered table-sm">
                    <thead class="table-primary">
                        <tr>
                            <th>Name</th>
                            <th>File Types</th>
                            <th>Download</th>
                            <th>Delete</th>
                            <th>Downloads</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $listRes->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['file_name'] ?></td>
                                <td><?= $row['file_type'] ?></td>

                                <td>
                                    <a href="?download_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">⬇</a>
                                </td>

                                <td>
                                    <button class="btn btn-sm btn-danger deleteBtn" data-id="<?= $row['id'] ?>">🗑</button>
                                </td>

                                <td><?= $row['download_count'] ?></td>
                                <td><?= $row['uploaded_at'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($i == $page ? 'active' : '') ?>">
                            <a class="page-link" href="?facility=<?= $selectedFacility ?>&q=<?= $q ?>&page=<?= $i ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <!-- PAGINATION -->


    </div>
</div>
<script>
    document.getElementById("uploadForm").addEventListener("submit", function(e) {

        e.preventDefault();

        let form = this;
        let formData = new FormData(form);

        let xhr = new XMLHttpRequest();
        xhr.open("POST", "", true);

        // ✅ DISABLE BUTTON HERE
        let btn = form.querySelector("button");
        btn.disabled = true;
        btn.innerHTML = "Uploading...";

        document.getElementById("progressBox").style.display = "block";

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                let percent = Math.round((e.loaded / e.total) * 100);
                let bar = document.getElementById("progressBar");

                bar.style.width = percent + "%";
                bar.innerHTML = percent + "%";
            }
        };

        xhr.onload = function() {

            if (xhr.responseText.trim() === "success") {

                let msg = document.createElement("div");
                msg.className = "alert alert-success alert-dismissible fade show";
                msg.innerHTML = `
                ✅ File uploaded successfully!
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            `;

                document.querySelector(".pcoded-content").prepend(msg);

                setTimeout(() => msg.remove(), 3000);
                setTimeout(() => location.reload(), 1500);

            } else {
                alert("Upload failed: " + xhr.responseText);

                // ❗ re-enable if failed
                btn.disabled = false;
                btn.innerHTML = "Upload";
            }
        };

        xhr.onerror = function() {
            alert("Upload failed!");
            btn.disabled = false;
            btn.innerHTML = "Upload";
        };

        xhr.send(formData);
    });
</script>
<script>
    document.querySelectorAll(".deleteBtn").forEach(btn => {

        btn.addEventListener("click", function() {

            if (!confirm("Delete this file?")) return;

            let id = this.getAttribute("data-id");

            let xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);

            let formData = new FormData();
            formData.append("delete_id", id);

            xhr.onload = function() {

                if (xhr.responseText.trim() === "deleted") {

                    // ✅ show message
                    let msg = document.createElement("div");
                    msg.className = "alert alert-danger alert-dismissible fade show";
                    msg.innerHTML = `
                    🗑 File deleted successfully!
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                `;

                    document.querySelector(".pcoded-content").prepend(msg);

                    setTimeout(() => msg.remove(), 3000);
                    setTimeout(() => location.reload(), 1000);

                } else {
                    alert("Delete failed!");
                }
            };

            xhr.send(formData);
        });

    });
</script>
<script>
    let timer;

    document.getElementById("liveSearch").addEventListener("keyup", function() {

        clearTimeout(timer);

        let query = this.value.trim();
        let facility = "<?= $selectedFacility ?>";

        timer = setTimeout(function() {

            // ✅ condition
            if (query.length >= 2 || query.length === 0) {

                let params = new URLSearchParams(window.location.search);

                params.set("q", query);
                params.set("facility", facility);
                params.delete("page"); // reset pagination

                let newUrl = "?" + params.toString();

                // ✅ avoid unnecessary reload
                if (window.location.search !== "?" + params.toString()) {
                    window.location.href = newUrl;
                }

            }

        }, 400); // slightly faster

    });
</script>
<?php include("assets/head/f.php"); ?>