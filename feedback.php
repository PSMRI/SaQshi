<?php
include("assets/head/h.php");

$isAdmin = (isset($_SESSION['userrole']) && $_SESSION['userrole'] == 9);
$userId  = $_SESSION['userid'] ?? 0;
if (!$isAdmin && $userId) {
    $stmt = $con->prepare("
        UPDATE review_replies rr
        JOIN review_table rt ON rt.review_id = rr.review_id
        SET rr.is_read = 1
        WHERE rt.user_id = ?
          AND rr.reply_by = 'admin'
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}
/* ======================================================
   1) GLOBAL OVERALL RATING (VISIBLE TO ALL)
====================================================== */
$avg = 0;
$totalFeedback = 0;

$qr = $con->query("
    SELECT 
        COUNT(*) AS total,
        ROUND(AVG(user_rating), 1) AS avg_rating
    FROM review_table
");

if ($qr && $row = $qr->fetch_assoc()) {
    $avg = $row['avg_rating'] ?? 0;
    $totalFeedback = $row['total'] ?? 0;
}

/* ======================================================
   2) FEEDBACK LIST (ROLE BASED)
====================================================== */
$reviews = [];

if ($isAdmin) {
    $q = $con->query("
        SELECT * 
        FROM review_table 
        ORDER BY datetime DESC
    ");
} else {
    $q = $con->query("
        SELECT * 
        FROM review_table 
        WHERE user_id = $userId
        ORDER BY datetime DESC
    ");
}

if ($q && $q->num_rows) {

    while ($r = $q->fetch_assoc()) {

        $rid = (int)$r['review_id'];

        $r['replies'] = [];
        $r['hasAdminReply'] = false;

        $rq = $con->query("
            SELECT * 
            FROM review_replies
            WHERE review_id = $rid
            ORDER BY replied_on ASC
        ");

        while ($rp = $rq->fetch_assoc()) {

            if ($rp['reply_by'] === 'admin') {
                $r['hasAdminReply'] = true;
            }

            $r['replies'][] = $rp;
        }

        $reviews[] = $r;
    }
}
?>


<style>
/* ================= LAYOUT ================= */
/* Dancing emoji animation */
.rating-emoji {
    font-size: 2rem;
    line-height: 1;
    margin-bottom: 4px;
    display: inline-block;
    animation: emoji-dance 1.4s ease-in-out infinite;
}

@keyframes emoji-dance {
    0%   { transform: translateY(0) rotate(0deg); }
    25%  { transform: translateY(-4px) rotate(-5deg); }
    50%  { transform: translateY(0) rotate(5deg); }
    75%  { transform: translateY(-2px) rotate(-3deg); }
    100% { transform: translateY(0) rotate(0deg); }
}

.feedback-grid{
    display:grid;
    grid-template-columns: 2fr 1fr;
    gap:14px; /* smaller gap */
}
@media(max-width:768px){
    .feedback-grid{
        grid-template-columns:1fr;
        gap:12px;
    }
}

/* ================= CARDS ================= */
.card-modern{
    border-radius:10px; /* smaller radius */
    box-shadow:0 6px 16px rgba(0,0,0,.06);
    border:0;
    background:#fff;
}

/* ================= OVERALL RATING ================= */
.rating-card{
    background:linear-gradient(135deg,#1abc9c,#159a80);
    color:#fff;
    text-align:center;
    padding:14px 12px; /* compact */
}
.rating-value{
    font-size:1.9rem;
    font-weight:800;
    line-height:1.1;
}
.rating-stars i{
    color:#ffd54f;
    font-size:.9rem;
    margin:0 1px;
}
.rating-sub{
    font-size:11px;
    opacity:.9;
    margin-top:4px;
    line-height:1.3;
}

/* ================= FEEDBACK FORM ================= */
.form-header{
    background:#1abc9c;
    color:#fff;
    padding:8px 12px;
    border-radius:10px 10px 0 0;
    font-weight:600;
    font-size:13px;
}
.feedback-compact input,
.feedback-compact textarea{
    font-size:12px;
    padding:6px 8px;
}
.feedback-compact textarea{
    min-height:70px;
}

/* Stars */
.submit_star{
    font-size:1.3rem;
    color:#e0e0e0;
    cursor:pointer;
    transition:all .2s ease;
}
.submit_star.selected,
.submit_star:hover{
    color:#ffc107;
    transform:scale(1.15);
}

/* Submit button */
.feedback-compact button{
    font-size:12px;
    padding:4px 14px;
}

/* ================= FEEDBACK LIST ================= */
.feedback-item{
    border-radius:10px;
    box-shadow:0 4px 12px rgba(0,0,0,.06);
    margin-bottom:10px;
    overflow:hidden;
    background:#fff;
}
.feedback-header{
    padding:10px 12px;
    cursor:pointer;
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#fff;
}
.feedback-header:hover{
    background:#f8f9fa;
}
.feedback-user{
    font-weight:600;
    font-size:13px;
}
.toggle-icon{
    font-size:18px;
    font-weight:700;
    color:#1abc9c;
}
.feedback-body{
    display:none;
    padding:12px;
    border-top:1px solid #eee;
    background:#fafafa;
    font-size:12px;
    line-height:1.45;
}

/* ================= CHAT ================= */
.chat-bubble{
    max-width:75%;
    padding:6px 10px;
    border-radius:10px;
    font-size:12px;
    margin-bottom:6px;
    line-height:1.4;
}
.chat-admin{
    background:#eef5ff;
    border-left:3px solid #0d6efd;
}
.chat-user{
    background:#e9f9f0;
    border-right:3px solid #198754;
    margin-left:auto;
}
.chat-label{
    font-size:9.5px;
    font-weight:600;
    margin-bottom:2px;
}
.chat-time{
    font-size:9px;
    color:#6c757d;
    text-align:right;
    margin-top:2px;
}
</style>


<div class="pcoded-main-container">
    <div class="pcoded-content">

        <!-- ================= TITLE ================= -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold text-success">
                <i class="bi bi-chat-heart-fill me-2"></i>Feedback
            </h5>
        </div>

        <!-- ================= FORM + RATING ================= -->
        <div class="feedback-grid mb-5">

            <!-- FEEDBACK FORM -->
            <div class="card-modern bg-white feedback-compact">
                <div class="form-header py-2 px-3">Leave Your Feedback</div>

                <div class="p-3">
                    <form method="POST" action="assets/responce/submit_rating.php" id="feedbackForm">

                        <input type="hidden" name="user_id" value="<?= $userId ?>">

                        <input type="text" name="user_name"
                            class="form-control form-control-sm mb-2"
                            placeholder="Your Name" required>

                        <textarea name="user_review"
                            class="form-control form-control-sm mb-2"
                            placeholder="Your Feedback"
                            rows="3" required></textarea>

                        <div class="mb-2 text-center">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star submit_star me-1"
                                    style="font-size:1.6rem"
                                    data-rating="<?= $i ?>"></i>
                            <?php endfor; ?>
                        </div>

                        <input type="hidden" name="rating_data" id="rating_data" value="0">

                        <div id="rating_error" class="text-danger small mb-2 text-center" style="display:none">
                            Please select a rating ⭐
                        </div>

                        <div class="text-center">
                            <button class="btn btn-success btn-sm px-4">Submit</button>
                        </div>
                    </form>

                </div>
            </div>
<?php
$ratingEmoji = '🙂'; // default

if ($avg >= 4.5) {
    $ratingEmoji = '😍';
} elseif ($avg >= 3.5) {
    $ratingEmoji = '😊';
} elseif ($avg >= 2.5) {
    $ratingEmoji = '🙂';
} elseif ($avg >= 1.5) {
    $ratingEmoji = '😐';
} else {
    $ratingEmoji = '😞';
}
?>


            <!-- OVERALL RATING -->
           <?php if ($totalFeedback > 0): ?>
    <div class="card-modern rating-card rating-compact">

        <div class="rating-emoji"><?= $ratingEmoji ?></div>

        <div class="rating-value"><?= $avg ?></div>

        <div class="rating-stars mb-1">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="bi bi-star-fill"></i>
            <?php endfor; ?>
        </div>

        <div class="rating-sub small">
            Overall Rating<br>
            <span class="opacity-75">
                <?= $totalFeedback ?> feedbacks
            </span>
        </div>

    </div>
<?php endif; ?>



        </div>

        <!-- ================= FEEDBACK LIST ================= -->
        <?php foreach ($reviews as $r): ?>
            <div class="feedback-item">

                <div class="feedback-header" data-target="#fb<?= $r['review_id'] ?>">
                    <div>
                        <div class="feedback-user">
                            <?= htmlspecialchars($r['user_name']) ?>
                        </div>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star-fill <?= $i <= $r['user_rating'] ? 'text-warning' : 'text-secondary' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="toggle-icon">+</div>
                </div>

                <div class="feedback-body" id="fb<?= $r['review_id'] ?>">

                    <p><?= nl2br(htmlspecialchars($r['user_review'])) ?></p>

                    <!-- ✅ MESSAGE CONTAINER -->
                    <div class="chat-messages">
                        <?php foreach ($r['replies'] as $rp): ?>
                            <div class="chat-bubble <?= $rp['reply_by'] == 'admin' ? 'chat-admin' : 'chat-user' ?>">
                                <div class="chat-label <?= $rp['reply_by'] == 'admin' ? 'text-primary' : 'text-success' ?>">
                                    <?= ucfirst($rp['reply_by']) ?>
                                </div>
                                <?= nl2br(htmlspecialchars($rp['reply_text'])) ?>
                                <div class="chat-time">
                                    <?= date('d M Y h:i A', strtotime($rp['replied_on'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- ✅ REPLY FORM ALWAYS AT BOTTOM -->
                    <?php if ($isAdmin || ($r['hasAdminReply'] && $r['user_id'] == $userId)): ?>
                        <form class="replyForm mt-3"
                            data-id="<?= $r['review_id'] ?>"
                            data-by="<?= $isAdmin ? 'admin' : 'user' ?>">
                            <textarea class="form-control mb-2 replyText"
                                rows="2"
                                placeholder="<?= $isAdmin ? 'Admin reply...' : 'Your reply...' ?>"
                                required></textarea>
                            <button class="btn btn-sm btn-outline-<?= $isAdmin ? 'primary' : 'success' ?>">
                                Reply
                            </button>
                        </form>
                    <?php endif; ?>

                </div>

            </div>
        <?php endforeach; ?>



    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let rating = 0;

    /* STAR */
    $('.submit_star').on('click', function() {
        rating = $(this).data('rating');
        $('#rating_data').val(rating);
        $('#rating_error').hide();
        $('.submit_star').each(function() {
            $(this).toggleClass('selected', $(this).data('rating') <= rating);
        });
    });

    /* SUBMIT VALIDATION */
    $('#feedbackForm').on('submit', function(e) {
        if ($('#rating_data').val() == 0) {
            e.preventDefault();
            $('#rating_error').show();
        }
    });

    /* TOGGLE */
    $('.feedback-header').on('click', function() {
        const t = $($(this).data('target'));
        const i = $(this).find('.toggle-icon');
        $('.feedback-body').not(t).slideUp();
        $('.toggle-icon').text('+');
        if (t.is(':visible')) {
            t.slideUp();
            i.text('+');
        } else {
            t.slideDown();
            i.text('−');
        }
    });

    /* AJAX REPLY */
    $('.replyForm').on('submit', function(e) {
        e.preventDefault();

        const f = $(this);
        const body = f.closest('.feedback-body');
        const chatBox = body.find('.chat-messages');
        const text = f.find('.replyText').val().trim();

        if (!text) return;

        f.find('button').prop('disabled', true).text('Sending...');

        $.post(
            "assets/get/reply_feedback.php", {
                review_id: f.data('id'),
                reply_by: f.data('by'),
                reply_text: text
            },
            function() {

                const bubble = `
                <div class="chat-bubble ${f.data('by') === 'admin' ? 'chat-admin' : 'chat-user'}">
                    <div class="chat-label ${f.data('by') === 'admin' ? 'text-primary' : 'text-success'}">
                        ${f.data('by').charAt(0).toUpperCase() + f.data('by').slice(1)}
                    </div>
                    ${$('<div>').text(text).html()}
                    <div class="chat-time">Just now</div>
                </div>
            `;

                // ✅ append ONLY to message container
                chatBox.append(bubble);

                // reset form
                f.find('.replyText').val('');
                f.find('button').prop('disabled', false).text('Reply');

                // optional auto-scroll
                chatBox.scrollTop(chatBox[0].scrollHeight);
            }
        );
    });
</script>

<?php include("assets/head/f.php"); ?>