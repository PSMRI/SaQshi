<?php
// resources_by_facility.php
// Tabs by facility (includes "All") + search + per-tab pagination + secure downloads

// --- 0) DB CONNECT (NO OUTPUT BEFORE DOWNLOAD LOGIC!) ---
include("assets/conn/db.php"); // ensure this does NOT echo anything

if (!$con || !($con instanceof mysqli)) {
    header("HTTP/1.1 500 Internal Server Error");
    exit("Database connection missing.");
}

/* -------------------------------------------------------
   1) DOWNLOAD HANDLER  (must run before any output)
   ------------------------------------------------------- */
if (isset($_GET['download_id'])) {
    $file_id = (int) $_GET['download_id'];

    if (headers_sent($file, $line)) {
        error_log("Headers already sent at $file:$line; cannot download id=$file_id");
        header("HTTP/1.1 500 Internal Server Error");
        exit("Cannot start file download (headers already sent).");
    }

    // Fetch file info
    $stmt = $con->prepare("SELECT file_path, file_name FROM files WHERE id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        header("HTTP/1.1 404 Not Found");
        exit("File not found.");
    }
    $row = $res->fetch_assoc();
    $stmt->close();

    // Resolve path safely
    // If your uploads live in a subfolder, e.g. /uploads, use: realpath(__DIR__ . '/uploads')
    $uploadsRoot = realpath(__DIR__);
    if ($uploadsRoot === false) {
        header("HTTP/1.1 500 Internal Server Error");
        exit("Server path error.");
    }

    $relativePath = ltrim($row['file_path'] ?? '', "/\\");
    $filePathCandidate = realpath($uploadsRoot . DIRECTORY_SEPARATOR . $relativePath);

    if ($filePathCandidate === false || strpos($filePathCandidate, $uploadsRoot) !== 0) {
        header("HTTP/1.1 400 Bad Request");
        exit("Invalid file path.");
    }
    if (!is_file($filePathCandidate) || !is_readable($filePathCandidate)) {
        header("HTTP/1.1 404 Not Found");
        exit("File missing.");
    }

    // Update download count BEFORE streaming
    $update = $con->prepare("UPDATE files SET download_count = download_count + 1 WHERE id = ?");
    $update->bind_param("i", $file_id);
    $update->execute();
    $update->close();

    // Stream file
    if (function_exists('ini_get') && ini_get('zlib.output_compression')) {
        @ini_set('zlib.output_compression', 'Off');
    }
    while (ob_get_level()) { ob_end_clean(); }

    // Determine download name (auto-append extension if missing)
    $downloadName = $row['file_name'] ?: basename($filePathCandidate);
    if (!pathinfo($downloadName, PATHINFO_EXTENSION)) {
        $actualExt = pathinfo($filePathCandidate, PATHINFO_EXTENSION);
        if ($actualExt) {
            $downloadName .= '.' . $actualExt;
        }
    }

    $filesize = (string) filesize($filePathCandidate);

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'. basename($downloadName) .'"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . $filesize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');

    set_time_limit(0);
    $chunk = 8192;
    $fh = fopen($filePathCandidate, 'rb');
    if ($fh === false) { header("HTTP/1.1 500 Internal Server Error"); exit("Unable to open file."); }
    while (!feof($fh)) {
        echo fread($fh, $chunk);
        flush();
    }
    fclose($fh);
    exit;
}

/* -------------------------------------------------------
   2) INPUTS (facility / search / pagination)
   ------------------------------------------------------- */
$limit = 10;
$page  = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$q_raw = isset($_GET['q']) ? (string)$_GET['q'] : '';
$q = trim($q_raw);
$like = '%' . $q . '%';

$selectedFacility = isset($_GET['facility']) ? trim((string)$_GET['facility']) : 'All';

/* -------------------------------------------------------
   3) FACILITY TABS + COUNTS (RESPECT SEARCH)
   ------------------------------------------------------- */

// Build counts per facility considering search term
// We union 'All' artificially in PHP.
$counts = [];  // facility => count
$totalAll = 0;

// If there is a search, add WHERE (file_name LIKE ? OR file_path LIKE ?)
if ($q !== '') {
    // per-facility counts
    $sql = "SELECT facility_type, COUNT(*) AS ct
            FROM files
            WHERE (file_name LIKE ? OR file_path LIKE ?)
            GROUP BY facility_type
            ORDER BY facility_type";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $rs = $stmt->get_result();
    while ($r = $rs->fetch_assoc()) {
        $ft = (string)$r['facility_type'];
        $counts[$ft] = (int)$r['ct'];
        $totalAll += (int)$r['ct'];
    }
    $stmt->close();
} else {
    // no search filter
    $sql = "SELECT facility_type, COUNT(*) AS ct
            FROM files
            GROUP BY facility_type
            ORDER BY facility_type";
    $rs = $con->query($sql);
    if ($rs) {
        while ($r = $rs->fetch_assoc()) {
            $ft = (string)$r['facility_type'];
            $counts[$ft] = (int)$r['ct'];
            $totalAll += (int)$r['ct'];
        }
        $rs->close();
    }
}

// Build facility list incl. All
$facTypes = array_keys($counts);
sort($facTypes, SORT_NATURAL | SORT_FLAG_CASE);
array_unshift($facTypes, 'All'); // put 'All' first

// If there were absolutely no files (rare), handle
if ($totalAll === 0 && empty($facTypes)) {
    include("assets/head/h.php");
    echo "<div class='container py-4'><div class='alert alert-warning'>No resources uploaded yet.</div></div>";
    include("assets/head/f.php");
    exit;
}

// Normalize selected facility
if (!in_array($selectedFacility, $facTypes, true)) {
    $selectedFacility = 'All';
}

/* -------------------------------------------------------
   4) TOTAL COUNT FOR SELECTED TAB (RESPECT SEARCH)
   ------------------------------------------------------- */
if ($selectedFacility === 'All') {
    if ($q !== '') {
        $csql = "SELECT COUNT(*) AS total FROM files WHERE (file_name LIKE ? OR file_path LIKE ?)";
        $cstmt = $con->prepare($csql);
        $cstmt->bind_param("ss", $like, $like);
    } else {
        $csql = "SELECT COUNT(*) AS total FROM files";
        $cstmt = $con->prepare($csql);
    }
} else {
    if ($q !== '') {
        $csql = "SELECT COUNT(*) AS total
                 FROM files
                 WHERE facility_type = ?
                   AND (file_name LIKE ? OR file_path LIKE ?)";
        $cstmt = $con->prepare($csql);
        $cstmt->bind_param("sss", $selectedFacility, $like, $like);
    } else {
        $csql = "SELECT COUNT(*) AS total FROM files WHERE facility_type = ?";
        $cstmt = $con->prepare($csql);
        $cstmt->bind_param("s", $selectedFacility);
    }
}
$cstmt->execute();
$cres = $cstmt->get_result();
$total_records = (int) (($cres->fetch_assoc())['total'] ?? 0);
$cstmt->close();

$total_pages = max(1, (int)ceil($total_records / $limit));

/* -------------------------------------------------------
   5) FETCH ROWS (RESPECT SEARCH + FACILITY + PAGINATION)
   ------------------------------------------------------- */
if ($selectedFacility === 'All') {
    if ($q !== '') {
        $lsql = "SELECT id, file_name, facility_type, file_type,file_path, uploaded_at, download_count
                 FROM files
                 WHERE (file_name LIKE ? OR file_path LIKE ?)
                 ORDER BY uploaded_at DESC
                 LIMIT ? OFFSET ?";
        $listStmt = $con->prepare($lsql);
        $listStmt->bind_param("ssii", $like, $like, $limit, $offset);
    } else {
        $lsql = "SELECT id, file_name, facility_type, file_type,file_path, uploaded_at, download_count
                 FROM files
                 ORDER BY uploaded_at DESC
                 LIMIT ? OFFSET ?";
        $listStmt = $con->prepare($lsql);
        $listStmt->bind_param("ii", $limit, $offset);
    }
} else {
    if ($q !== '') {
        $lsql = "SELECT id, file_name, facility_type,file_type, file_path, uploaded_at, download_count
                 FROM files
                 WHERE facility_type = ?
                   AND (file_name LIKE ? OR file_path LIKE ?)
                 ORDER BY uploaded_at DESC
                 LIMIT ? OFFSET ?";
        $listStmt = $con->prepare($lsql);
        $listStmt->bind_param("sssii", $selectedFacility, $like, $like, $limit, $offset);
    } else {
        $lsql = "SELECT id, file_name, facility_type,file_type, file_path, uploaded_at, download_count
                 FROM files
                 WHERE facility_type = ?
                 ORDER BY uploaded_at DESC
                 LIMIT ? OFFSET ?";
        $listStmt = $con->prepare($lsql);
        $listStmt->bind_param("sii", $selectedFacility, $limit, $offset);
    }
}
$listStmt->execute();
$listRes = $listStmt->get_result();

/* -------------------------------------------------------
   6) RENDER (now it's safe to include header/footer)
   ------------------------------------------------------- */
include("assets/head/h.php");

// Helper to keep q param in links
function keep_q($q) {
    return $q !== '' ? '&q=' . urlencode($q) : '';
}

?>
<div class="pcoded-main-container">
    <div class="pcoded-content container-fluid py-3">

        <div class="pagetitle mb-3 d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div>
                <h5 class="fw-bold text-primary mb-1">
                    <i class="bi bi-folder2-open me-2"></i> Useful Resources
                </h5>
                <p class="small text-muted mb-0">Search by file name or path. Use tabs to filter by facility.</p>
            </div>

            <!-- Search Form -->
            <form class="d-flex gap-2" method="get" action="">
                <input type="hidden" name="facility" value="<?= htmlspecialchars($selectedFacility) ?>">
                <input type="text"
                       class="form-control form-control-sm"
                       name="q"
                       placeholder="Search files…"
                       value="<?= htmlspecialchars($q) ?>"
                       style="min-width:260px">
                <button class="btn btn-sm btn-primary" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
                <?php if ($q !== ''): ?>
                    <a class="btn btn-sm btn-outline-secondary"
                       href="?facility=<?= urlencode($selectedFacility) ?>">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <div class="card-body">

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3" id="facilityTabs" role="tablist">
                    <?php
                    // Build counts for tabs respecting search term
                    $tabList = [];
                    $tabList[] = ['All', $totalAll]; // All first
                    foreach ($counts as $ft => $ct) {
                        $tabList[] = [$ft, $ct];
                    }
                    foreach ($tabList as $tab) :
                        [$ft, $ct] = $tab;
                        $isActive = ($ft === $selectedFacility);
                    ?>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $isActive ? 'active' : '' ?>"
                               href="?facility=<?= urlencode($ft) . keep_q($q) ?>"
                               role="tab">
                               <?= htmlspecialchars($ft) ?>
                               <span class="badge bg-secondary ms-2"><?= (int)$ct ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Results -->
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle small">
                        <thead class="table-primary">
                        <tr>
                            <th style="width:42%;">Name</th>
                            <th style="width:22%;">File Type</th>
                            <th style="width:12%;">Download</th>
                            <th style="width:8%;">Downloads</th>
                            <th style="width:16%;">Uploaded At</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($listRes && $listRes->num_rows > 0): ?>
                            <?php while ($row = $listRes->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php
                                        // Optional: highlight search term in file_name
                                        $displayName = htmlspecialchars($row['file_name']);
                                        if ($q !== '' && $displayName !== '') {
                                            $displayName = preg_replace(
                                                '/' . preg_quote($q, '/') . '/i',
                                                '<mark>$0</mark>',
                                                $displayName
                                            );
                                        }
                                        echo $displayName ?: '(unnamed)';
                                        ?>
                                    </td>
                                    <td><?= e($row['file_type']) ?></td>
                                    <td class="text-center">
                                        <a href="?download_id=<?= (int)$row['id'] ?>"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Download <?= htmlspecialchars($row['file_name']) ?>">
                                           <i class="bi bi-download"></i>
                                        </a>
                                    </td>
                                    <td class="text-center"><?= (int)$row['download_count'] ?></td>
                                    <td><?= htmlspecialchars($row['uploaded_at']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    <?php if ($q !== ''): ?>
                                        No files found for "<strong><?= htmlspecialchars($q) ?></strong>"
                                        in <strong><?= htmlspecialchars($selectedFacility) ?></strong>.
                                    <?php else: ?>
                                        No files in "<strong><?= htmlspecialchars($selectedFacility) ?></strong>".
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php $listStmt->close(); ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-3 small">
                            <?php
                            $baseUrl = '?facility=' . urlencode($selectedFacility) . keep_q($q) . '&page=';
                            $cur = $page;
                            $start = max(1, $cur - 3);
                            $end   = min($total_pages, $cur + 3);
                            ?>
                            <?php if ($cur > 1): ?>
                                <li class="page-item"><a class="page-link" href="<?= $baseUrl . ($cur - 1) ?>">Previous</a></li>
                            <?php endif; ?>

                            <?php if ($start > 1): ?>
                                <li class="page-item"><a class="page-link" href="<?= $baseUrl . '1' ?>">1</a></li>
                                <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?= ($i == $cur) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= $baseUrl . $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($end < $total_pages): ?>
                                <?php if ($end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                                <li class="page-item"><a class="page-link" href="<?= $baseUrl . $total_pages ?>"><?= $total_pages ?></a></li>
                            <?php endif; ?>

                            <?php if ($cur < $total_pages): ?>
                                <li class="page-item"><a class="page-link" href="<?= $baseUrl . ($cur + 1) ?>">Next</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<?php include("assets/head/f.php"); ?>
