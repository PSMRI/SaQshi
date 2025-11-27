<form method="POST" action="#">
  <?php
    $rand = rand() + 1;
    $_SESSION['rand'] = $rand;
  ?>
  <input type="hidden" name="randcheck" value="<?php echo $rand; ?>">

  <div class="card shadow-sm border rounded p-3 bg-light bg-gradient small">
    <h5 class="text-center mb-4 fw-bold text-success">
      <i class="bi bi-pencil-square me-2"></i>Edit Checklist Compliance
    </h5>

    <!-- Standard & Ref. No -->
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <div class="card bg-white shadow-sm h-100">
          <div class="card-body p-2">
            <div class="fw-semibold text-secondary mb-1">
              <i class="bi bi-list-check me-2 text-primary"></i>Standard
            </div>
            <div class="text-dark text-break"><?php echo $row['c_subtype_Reference_No_fk'] ?? ''; ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card bg-white shadow-sm h-100">
          <div class="card-body p-2">
            <div class="fw-semibold text-secondary mb-1">
              <i class="bi bi-hash me-2 text-primary"></i>Reference Number
            </div>
            <div class="text-dark text-break"><?php echo $row['csqa_reference_id'] ?? ''; ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Measurable Element -->
    <div class="card bg-white shadow-sm mb-3">
      <div class="card-body p-2">
        <div class="fw-semibold text-secondary mb-1">
          <i class="bi bi-rulers me-2 text-primary"></i>Measurable Element
        </div>
        <div class="text-dark text-break"><?php echo $row['Measurable_Element'] ?? ''; ?></div>
      </div>
    </div>

    <!-- Checkpoint & Assessment Method -->
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <div class="card bg-white shadow-sm h-100">
          <div class="card-body p-2">
            <div class="fw-semibold text-secondary mb-1">
              <i class="bi bi-check-circle me-2 text-primary"></i>Checkpoint
            </div>
            <div class="text-dark text-break"><?php echo $row['Checkpoint'] ?? ''; ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card bg-white shadow-sm h-100">
          <div class="card-body p-2">
            <div class="fw-semibold text-secondary mb-1">
              <i class="bi bi-clipboard-data me-2 text-primary"></i>Assessment Method
            </div>
            <div class="text-dark text-break"><?php echo $row['Assessment_Method'] ?? ''; ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Means of Verification -->
    <div class="card bg-white shadow-sm mb-3">
      <div class="card-body p-2">
        <div class="fw-semibold text-secondary mb-1">
          <i class="bi bi-search me-2 text-primary"></i>Means of Verification
        </div>
        <div class="text-dark text-break"><?php echo $row['Means_of_Verification'] ?? ''; ?></div>
      </div>
    </div>

    <!-- Compliance with colored cards -->
    <div class="card bg-white shadow-sm mb-3 border-top border-3 border-info">
      <div class="card-body p-2">
        <div class="fw-semibold text-secondary mb-3">
          <i class="bi bi-sliders me-2 text-primary"></i>Compliance
        </div>

        <div class="d-flex justify-content-between flex-wrap gap-3">

          <!-- 0 = Red Card -->
          <div class="card border-0 shadow-sm bg-danger" style="min-width: 90px;">
            <div class="card-body p-2 text-center text-white">
              <input class="form-check-input mb-2" type="radio" name="f" id="f0" value="0" onclick="dropEmoji(0)">
              <label for="f0" class="d-block fw-bold text-white mb-0">0</label>
            </div>
          </div>

          <!-- 1 = Yellow Card -->
          <div class="card border-0 shadow-sm bg-warning" style="min-width: 90px;">
            <div class="card-body p-2 text-center text-white">
              <input class="form-check-input mb-2" type="radio" name="f" id="f1" value="1" onclick="dropEmoji(1)">
              <label for="f1" class="d-block fw-bold text-white mb-0">1</label>
            </div>
          </div>

          <!-- 2 = Green Card -->
          <div class="card border-0 shadow-sm bg-success" style="min-width: 90px;">
            <div class="card-body p-2 text-center text-white">
              <input class="form-check-input mb-2" type="radio" name="f" id="f2" value="2" onclick="dropEmoji(2)" checked>
              <label for="f2" class="d-block fw-bold text-white mb-0">2</label>
            </div>
          </div>

        </div>
      </div>
    </div>
<script>
        function dropEmoji(value) {
          const emojiMap = {
            0: "😓", // Sad
            1: "🙂", // Okay
            2: "🎉🎈🎊" // Happy
          };

          const emoji = document.createElement("div");
          emoji.className = "falling-emoji";
          emoji.innerText = emojiMap[value];

          // Random horizontal start position
          emoji.style.left = Math.random() * 80 + 10 + "vw";

          document.body.appendChild(emoji);

          // Remove from DOM after animation
          setTimeout(() => {
            emoji.remove();
          }, 2200);
        }
      </script>
    <!-- Hidden Fields -->
    <input type="hidden" name="csqa_id1" value="<?php echo $_SESSION['q1'] ?? ''; ?>">
    <input type="hidden" name="csqa_id_u" value="<?php echo $row['csqa_id'] - 1; ?>">

    <!-- Button -->
    <div class="d-flex justify-content-center mt-4">
      <div class="card shadow-sm border-0">
        <div class="card-body p-2 text-center">
          <button type="submit" name="update" class="btn btn-success btn-sm px-4">
            <i class="bi bi-arrow-repeat me-1"></i>Update & Next
          </button>
        </div>
      </div>
    </div>

  </div>
</form>
