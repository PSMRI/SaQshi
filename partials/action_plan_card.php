<?php if (!isset($row)) return; ?>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-bottom-0">
    <h5 class="text-success fw-semibold mb-0">
      <i class="bi bi-clipboard-check-fill me-2"></i>Details for Compliance
    </h5>
  </div>

  <div class="card-body small">
    <form method="post" action="#" enctype="multipart/form-data">

      <!-- Top row: Standard | Reference Number -->
      <div class="row g-3 mb-2">
        <div class="col-md-6">
          <div class="card card-body border-top border-3 border-success">
            <div class="fw-semibold text-muted"><i class="bi bi-list-task me-1 text-success"></i>Standard</div>
            <div><?php echo $row['c_subtype_Reference_No_fk']; ?></div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card card-body border-top border-3 border-success">
            <div class="fw-semibold text-muted"><i class="bi bi-hash me-1 text-success"></i>Reference Number</div>
            <div><?php echo $row['csqa_reference_id'] ?? 'N/A'; ?></div>
          </div>
        </div>
      </div>

      <!-- Next row: Measurable | Checkpoint | Method -->
      <div class="row g-3 mb-2">
        <div class="col-md-4">
          <div class="card card-body border-top border-3 border-success">
            <div class="fw-semibold text-muted"><i class="bi bi-card-text me-1 text-success"></i>Measurable Element</div>
            <div><?php echo $row['Measurable_Element']; ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-body border-top border-3 border-success">
            <div class="fw-semibold text-muted"><i class="bi bi-check2-circle me-1 text-success"></i>Checkpoint</div>
            <div><?php echo $row['Checkpoint']; ?></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-body border-top border-3 border-success">
            <div class="fw-semibold text-muted"><i class="bi bi-journals me-1 text-success"></i>Assessment Method</div>
            <div><?php echo $row['Assessment_Method'] ?? 'N/A'; ?></div>
          </div>
        </div>
      </div>

      <!-- Bold block: 4 columns -->
      <div class="row g-3 mb-2">
        <div class="col-md-3">
          <div class="card card-body border-top border-3 border-success">
            <div class="fw-bold text-success"><i class="bi bi-search me-1"></i>Means of Verification</div>
            <div><?php echo $row['Means_of_Verification'] ?? 'N/A'; ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-body border-top border-3 border-success">
            <div class="fw-bold text-success"><i class="bi bi-award me-1"></i>Compliance</div>
            <div><?php echo $row['ass_compliance']; ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-body border-top border-3 border-success">
            <div class="fw-bold text-success"><i class="bi bi-chat-dots me-1"></i>Dept. Remarks</div>
            <div><?php echo $row['moic_remarcks']; ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card card-body border-top border-3 border-success">
            <div class="fw-bold text-success"><i class="bi bi-flag me-1"></i>Priority</div>
            <div>
              <span class="badge 
                <?php
                  echo ($row['Priority1'] == 'High') ? 'bg-danger' :
                       (($row['Priority1'] == 'Medium') ? 'bg-warning text-dark' : 'bg-success');
                ?>">
                <?php echo $row['Priority1']; ?>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Plan & Action Taken -->
      <div class="row g-3 mb-2">
        <div class="col-md-6">
          <div class="card card-body border-top border-3 border-success">
            <div class="fw-semibold text-muted"><i class="bi bi-pencil me-1 text-success"></i>Dept. Action Plan</div>
            <div><?php echo $row['dept_action_plan']; ?></div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card card-body border-top border-3 border-success">
            <div class="fw-semibold text-muted"><i class="bi bi-pencil-square me-1 text-success"></i>Action Taken</div>
            <textarea class="form-control form-control-sm mt-2" rows="3" name="Action_Taken" placeholder="Describe action taken..." required></textarea>
          </div>
        </div>
      </div>

      <!-- Final Row: Compliance + Submit -->
      <div class="row g-3">
        <div class="col-md-6">
          <div class="card card-body border-top border-3 border-success">
            <label class="form-label text-success small fw-semibold">Update Compliance</label>
            <select class="mb-1 form-control form-control-sm" name="f">

              <option value="3">Select</option>              
              <option value="1">1</option>
              <option value="2">2</option>
            </select>
          </div>
        </div>
        <div class="col-md-6 ">
         <div class="card card-body border-top border-3 border-success">
            <input type="hidden" name="csqa_id1" value="<?php echo $_SESSION['q1']; ?>">
            <input type="hidden" name="csqa_id" value="<?php echo $row['ass_id']; ?>">
            <button type="submit" name="postsubmit2" class="btn btn-primary btn-sm w-100">Save & Next</button>
          </div>
        </div>
      </div>

    </form>
  </div>
</div>
