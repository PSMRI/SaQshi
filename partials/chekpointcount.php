<style>
    .score-card-light {
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 3px 10px rgba(0,0,0,0.12);
        padding: 18px;
        transition: 0.2s;
    }
    .score-card-light:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.18);
    }
    .score-icon-box {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: #f4f6f9;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 32px;
        color: #555;
    }
    .download-btn {
        background: #e9ecef;
        padding: 6px 12px;
        border-radius: 6px;
        display: inline-block;
        margin-top: 10px;
        color: #333 !important;
        font-size: 14px;
        transition: 0.2s;
    }
    .download-btn:hover {
        background: #d7dce1;
        text-decoration: none;
    }
</style>
<div class="col-lg-6 col-xl-4 mb-3">
    <div class="score-card-light">

        <div class="d-flex align-items-center">
            
            <!-- Icon -->
            <div class="score-icon-box me-3">
                <i class="feather icon-list"></i>
            </div>

            <!-- Text -->
            <div>
                <h4 id="checkpoints-container" class="mb-1">Completed Checkpoints</h4>
                <div class="text-muted" style="font-size:14px;">Completed Checkpoints</div>

                <a href="assets/export/export_deprt_indicators.php" 
                   class="download-btn" download>
                    <i class="bi bi-arrow-down-circle me-1"></i> Download Indicators
                </a>
            </div>

        </div>

    </div>
</div>
<div class="col-lg-6 col-xl-4 mb-3">
    <div class="score-card-light">

        <div class="d-flex align-items-center">

            <!-- Icon -->
            <div class="score-icon-box me-3">
                <i class="feather icon-clipboard text-warning"></i>
            </div>

            <!-- Text -->
            <div>
                <h4 class="text-warning mb-1"><?= htmlspecialchars($count) ?></h4>
                <div class="text-muted" style="font-size:14px;">Assessment No.</div>
            </div>

        </div>

    </div>
</div>
