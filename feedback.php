<?php
include("assets/head/h.php");


// Fetch all reviews from table
$reviews = [];
$average_rating = 0;
$sql = "SELECT * FROM review_table ORDER BY datetime DESC";
$result = $con->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
        $average_rating += $row['user_rating'];
    }
    $average_rating = round($average_rating / count($reviews), 1);
}
?>

<div class="pcoded-main-container">
    <div class="pcoded-content">
        <div class="pagetitle mb-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-primary">
                <i class="bi bi-star-fill me-2"></i>Feedback
            </h5>
        </div>

        <?php if (!empty($reviews)): ?>
        <div class="card shadow mb-4 bg-light border-0" style="background: linear-gradient(145deg,rgb(250, 250, 250),rgb(136, 197, 45));">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted mb-1">Average Rating</h6>
                    <div>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?= $i <= round($average_rating) ? 'text-warning' : 'text-secondary' ?>"></i>
                        <?php endfor; ?>
                        <span class="ms-2 fw-bold text-dark" style="font-size: 1.2rem;">
                            <?= $average_rating ?> / 5
                        </span>
                    </div>
                </div>
                <i class="bi bi-bar-chart-fill text-primary" style="font-size: 2rem;"></i>
            </div>
        </div>
        <?php endif; ?>

        <!-- Feedback Form -->
        <div class="card mb-4">
             <div class="card-header bg-primary  fw-bold">
                     <h5 class="card-title">Leave Your Feedback</h5>
                  </div>
            
            <div class="card-body">
                <form method="POST" action="assets/responce/submit_rating.php">
                    <div class="mb-2">
                        <label>Your Name</label>
                        <input type="text" name="user_name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Your Feedback</label>
                        <textarea name="user_review" class="form-control" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Star Rating</label>
                        <div id="star_rating" class="mb-2 position-relative">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="me-2 star-wrapper">
                                    <i class="fas fa-star submit_star" data-rating="<?= $i ?>"></i>
                                </span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating_data" id="rating_data" value="0">
                        <div id="emoji_label" class="fw-bold mt-2 text-center" style="font-size: 1.5rem;"></div>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Feedback</button>
                </form>
            </div>
        </div>

        <!-- Review List -->
        <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="card mb-3">
                    <div class="card-header  fw-bold"><?= htmlspecialchars($review['user_name']) ?></div>
                    <div class="card-body">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?= $i <= $review['user_rating'] ? 'text-warning' : 'text-secondary' ?>"></i>
                        <?php endfor; ?>
                        <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($review['user_review'])) ?></p>
                    </div>
                    <div class="card-footer text-muted text-end">
                        <?= date('d M Y h:i A', strtotime($review['datetime'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">No feedback submitted yet.</div>
        <?php endif; ?>
    </div>
</div>

<style>
    .submit_star {
        color: #e9ecef;
        cursor: pointer;
        font-size: 1.9rem;
        transition: transform 0.2s ease-in-out, color 0.2s ease-in-out;
    }
    .submit_star:hover,
    .submit_star.selected {
        color: #ffc107 !important;
        transform: scale(1.4);
        animation: bounce 0.4s ease;
    }
    @keyframes bounce {
        0% { transform: scale(1.1) translateY(0); }
        50% { transform: scale(1.5) translateY(-10px); }
        100% { transform: scale(1.2) translateY(0); }
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let rating = 0;

    $(document).on('mouseenter', '.submit_star', function () {
        const index = $(this).data('rating');
        highlightStars(index);
    });

    $(document).on('mouseleave', '.submit_star', function () {
        highlightStars(rating);
    });

    $(document).on('click', '.submit_star', function () {
        rating = $(this).data('rating');
        $('#rating_data').val(rating);
        highlightStars(rating);

        const emoji = $('<div class="emoji-fall">⭐</div>');
        $('body').append(emoji);
        emoji.css({
            left: $(this).offset().left + 'px',
            top: $(this).offset().top + 'px'
        }).animate({
            top: '+=100px',
            opacity: 0
        }, 800, function() {
            $(this).remove();
        });
    });

    function highlightStars(count) {
        $('.submit_star').each(function () {
            const starIndex = $(this).data('rating');
            if (starIndex <= count) {
                $(this).addClass('selected').removeClass('star-light');
            } else {
                $(this).removeClass('selected').addClass('star-light');
            }
        });
    }
});
</script>

<style>
    .emoji-fall {
        position: absolute;
        font-size: 2rem;
        z-index: 9999;
        animation: fall 0.8s ease-out forwards;
    }

    @keyframes fall {
        0% { transform: translateY(0); opacity: 1; }
        100% { transform: translateY(100px); opacity: 0; }
    }
</style>

<?php include("assets/head/f.php"); ?>
