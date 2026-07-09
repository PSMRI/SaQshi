<?php
/* ----------------------------------------------------
   REQUIRED DEPENDENCIES
---------------------------------------------------- */
include_once(__DIR__ . "/../../assets/conn/db.php");
include_once(__DIR__ . "/../../assets/conn/session.php");

/* ----------------------------------------------------
   SAFELY LOAD SESSION LIST
---------------------------------------------------- */
$list = $_SESSION['LIST'] ?? [];
$idx  = intval($_SESSION['IDX'] ?? 0);

/* Prevent crash */
if (empty($list)) {
    echo "<div class='alert alert-danger text-center'>No checklist loaded.</div>";
    exit;
}

/* Prevent overflow */
if ($idx < 0) $idx = 0;
if ($idx >= count($list)) $idx = count($list) - 1;

$row = $list[$idx];

/* ----------------------------------------------------
   SESSION VARIABLES
---------------------------------------------------- */
$fid  = intval($_SESSION['u_facilityid'] ?? 0);
$dep  = intval($_SESSION['dept_id1'] ?? 0);
$peri = intval($_SESSION['assperiod'] ?? 0);
$uid  = intval($_SESSION['userid'] ?? 0);

$csqa_id = intval($row['csqa_id']);

/* ----------------------------------------------------
   FETCH EXISTING COMPLIANCE
---------------------------------------------------- */
$sql = "
    SELECT ass_compliance
    FROM chk_list_assessment
    WHERE fac_id_fk      = $fid
      AND fac_dept_id_fk = $dep
      AND ass_period_id  = $peri
      AND user_id        = $uid
      AND csqa_id_fk     = $csqa_id
";

$q = mysqli_query($con, $sql);
$exists = ($q && mysqli_num_rows($q) > 0) ? intval(mysqli_fetch_assoc($q)['ass_compliance']) : null;

/* ----------------------------------------------------
   PROGRESS BAR
---------------------------------------------------- */
$total    = count($list);
$current  = $idx + 1;
$progress = round(($current / $total) * 100);

$pbar = ($progress < 34) ? "bg-danger" : (($progress < 67) ? "bg-warning" : "bg-success");
?>

<style>
    .falling-emoji {
        position: fixed;
        top: -20px;
        font-size: 40px;
        animation: fall 2.2s linear forwards;
        z-index: 5000;
        pointer-events: none;
    }

    @keyframes fall {
        from {
            transform: translateY(0);
            opacity: 1;
        }

        to {
            transform: translateY(100vh);
            opacity: 0;
        }
    }
</style>

<div class="card shadow-lg border-0 mb-4" style="border-left: 4px solid #0d6efd;">
    <div class="card-body">

        <!-- PROGRESS BAR -->
        <div class="mb-4">
            <div class="d-flex justify-content-between mb-1">
                <h5 class="fw-bold text-primary">
                    <i class="bi bi-clipboard-check me-2"></i>
                    Checkpoint <?= $current ?> of <?= $total ?>
                </h5>
                <span class="fw-bold"><?= $current ?>/<?= $total ?> (<?= $progress ?>%)</span>
            </div>

            <div class="progress" style="height: 12px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated <?= $pbar ?>"
                    style="width: <?= $progress ?>%;">
                </div>
            </div>
        </div>


        <!-- QUESTION HEADER -->
        <div class="row g-3">

            <!-- Standard -->
            <div class="col-md-2">
                <div class="p-3 bg-light border rounded h-100">
                    <div class="fw-bold text-dark small mb-1">
                        <i class="bi bi-list-check text-primary me-1"></i> Standard
                    </div>
                    <?= htmlspecialchars($row['c_subtype_Reference_No_fk']) ?>
                </div>
            </div>

            <!-- Ref No -->
            <div class="col-md-2">
                <div class="p-3 bg-light border rounded h-100">
                    <div class="fw-bold text-dark small mb-1">
                        <i class="bi bi-hash text-primary me-1"></i> Ref. No.
                    </div>
                    <?= htmlspecialchars($row['csqa_reference_id']) ?>
                </div>
            </div>

            <!-- Method -->
            <div class="col-md-2">
                <div class="p-3 bg-light border rounded h-100">
                    <div class="fw-bold text-dark small mb-1">
                        <i class="bi bi-clipboard-data text-primary me-1"></i> Method
                    </div>
                    <?= htmlspecialchars($row['Assessment_Method']) ?>
                </div>
            </div>

            <!-- Means -->
            <div class="col-md-6">
                <div class="p-3 bg-light border rounded h-100">
                    <div class="fw-bold text-dark small mb-1">
                        <i class="bi bi-search text-primary me-1"></i> Means
                    </div>
                    <?= nl2br(htmlspecialchars($row['Means'])) ?>
                </div>
            </div>

        </div>


        <!-- ME & CHECKPOINT BLOCKS -->
        <div class="row g-3 mt-3">

            <div class="col-md-6">
                <div class="p-3 shadow-sm bg-white border rounded">
                    <div class="fw-bold small text-primary mb-1">
                        <i class="bi bi-rulers me-1"></i> Measurable Element
                    </div>
                    <?= nl2br(htmlspecialchars($row['M'])) ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 shadow-sm bg-white border rounded">
                    <div class="fw-bold small text-primary mb-1">
                        <i class="bi bi-check-circle me-1"></i> Checkpoint
                    </div>
                    <?= nl2br(htmlspecialchars($row['C'])) ?>
                </div>
            </div>

        </div>


        <!-- COMPLIANCE INPUT -->
        <form id="answerForm" class="mt-4">

            <input type="hidden" name="csqa_id" value="<?= $csqa_id ?>">

            <div class="d-flex gap-3 justify-content-center my-3">

                <!-- 0 -->
                <label class="card text-center bg-danger text-white p-2 shadow-sm"
                    onclick="dropEmoji(0)" style="width:90px; cursor:pointer;">
                    <input type="radio" name="f" value="0" <?= ($exists === 0) ? "checked" : "" ?>>
                    <div class="fw-bold">0</div>
                </label>

                <!-- 1 -->
                <label class="card text-center bg-warning text-white p-2 shadow-sm"
                    onclick="dropEmoji(1)" style="width:90px; cursor:pointer;">
                    <input type="radio" name="f" value="1" <?= ($exists === 1) ? "checked" : "" ?>>
                    <div class="fw-bold">1</div>
                </label>

                <!-- 2 -->
                <label class="card text-center bg-success text-white p-2 shadow-sm"
                    onclick="dropEmoji(2)" style="width:90px; cursor:pointer;">
                    <input type="radio" name="f" value="2" <?= ($exists === 2) ? "checked" : "" ?>>
                    <div class="fw-bold">2</div>
                </label>

            </div>


            <!-- MESSAGE -->
            <?php if (!empty($_SESSION['MSG'])): ?>
                <div class="alert alert-info fw-bold text-center my-3">
                    <?= $_SESSION['MSG']; ?>
                </div>
                <?php $_SESSION['MSG'] = ""; ?>
            <?php endif; ?>

            <!-- BUTTONS -->
            <?php
            $isLastCheckpoint = ($idx >= $total - 1);
           $assessmentCompleted =
    !empty($_SESSION['ASSESSMENT_COMPLETED']) &&
    $isLastCheckpoint &&
    isset($_SESSION['JUST_COMPLETED']);
            ?>

            <div class="d-flex justify-content-between mt-4">

                <?php if ($idx > 0): ?>
                    <button type="button" class="btn btn-secondary px-4" onclick="doAction('back')">
                        <i class="bi bi-arrow-left-circle me-1"></i> Back
                    </button>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

                <?php if (!$assessmentCompleted): ?>

                    <?php if (!$isLastCheckpoint): ?>
                        <button type="button" class="btn btn-warning px-4" onclick="doAction('skip')">
                            <i class="bi bi-skip-forward-fill me-1"></i> Skip
                        </button>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>

                    <button type="button" class="btn btn-success px-4" onclick="saveAction()">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        <?= $isLastCheckpoint ? 'Save' : 'Save & Next'; ?>
                    </button>

                <?php else: ?>

                    <div></div>

                    <div class="text-success fw-bold fs-5">
                        <i class="bi bi-check-circle-fill"></i>
                        Assessment Completed
                    </div>

                <?php endif; ?>

            </div>

        </form>

    </div>
</div>

<script>
    function dropEmoji(val) {
        const map = {
            0: "😞",
            1: "🙂",
            2: "🎉"
        };

        const emoji = document.createElement("div");
        emoji.className = "falling-emoji";
        emoji.innerText = map[val];

        emoji.style.left = (Math.random() * 80 + 10) + "vw";

        document.body.appendChild(emoji);

        setTimeout(() => emoji.remove(), 2200);
    }
</script>