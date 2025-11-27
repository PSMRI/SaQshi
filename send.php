<?php
include("assets/conn/db.php");


// Handle new message send (Admin → Facility / All)
if (isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_facility_id'];
    $message_text = mysqli_real_escape_string($con, $_POST['message_text']);

    $query = "INSERT INTO facility_chat_messages (sender_facility_id, receiver_facility_id, message_text, is_active) 
              VALUES (0, $receiver_id, '$message_text', 1)";
    mysqli_query($con, $query);

    // Redirect back to chat with same facility
    header("Location: send.php?facility_id=$receiver_id");
    exit();
}

// Get selected facility_id for conversation
$selected_facility_id = $_GET['facility_id'] ?? 0;
$selected_facility_id = (int)$selected_facility_id;

// Build Facility List (all facilities having chat messages)
$fac_list_query = "SELECT DISTINCT fac_id, fac_name 
                   FROM facilities 
                   WHERE fac_id IN (
                       SELECT DISTINCT receiver_facility_id FROM facility_chat_messages WHERE sender_facility_id = 0 AND receiver_facility_id != 0
                       UNION
                       SELECT DISTINCT sender_facility_id FROM facility_chat_messages WHERE sender_facility_id != 0 AND receiver_facility_id = 0
                   )
                   ORDER BY fac_name ASC";

$fac_list_result = mysqli_query($con, $fac_list_query);

// Build chat query
if ($selected_facility_id > 0) {
    $chat_query = "SELECT m.*, 
                          s.fac_name AS sender_name, 
                          r.fac_name AS receiver_name 
                   FROM facility_chat_messages m
                   LEFT JOIN facilities s ON m.sender_facility_id = s.fac_id
                   LEFT JOIN facilities r ON m.receiver_facility_id = r.fac_id
                   WHERE (m.sender_facility_id = 0 AND m.receiver_facility_id = $selected_facility_id)
                      OR (m.sender_facility_id = $selected_facility_id AND m.receiver_facility_id = 0)
                   ORDER BY m.message_date ASC";
} else {
    // No facility selected → show nothing initially
    $chat_query = "SELECT m.*, 
                          s.fac_name AS sender_name, 
                          r.fac_name AS receiver_name 
                   FROM facility_chat_messages m
                   LEFT JOIN facilities s ON m.sender_facility_id = s.fac_id
                   LEFT JOIN facilities r ON m.receiver_facility_id = r.fac_id
                   WHERE 1=0";
}

$chat_result = mysqli_query($con, $chat_query);
?>

<?php include("assets/head/h.php"); ?>

<div class="pcoded-main-container">
    <div class="pcoded-content">

        <div class="pagetitle mb-2 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-primary">Admin Chat with Facilities</h5>
        </div>

        <!-- New Chat -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6>Start New Chat</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-row align-items-center">
                        <div class="col-md-5 mb-2">
                            <select name="receiver_facility_id" class="form-control" required>
                                <option value="">Select Facility</option>
                                <?php
                                // Load all facilities
                                $all_fac_query = "SELECT fac_id, fac_name FROM facilities where fac_name is not null ORDER BY fac_name ASC";
                                $all_fac_result = mysqli_query($con, $all_fac_query);
                                while ($fac = mysqli_fetch_assoc($all_fac_result)) {
                                    echo "<option value='{$fac['fac_id']}'>" . htmlspecialchars($fac['fac_name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-5 mb-2">
                            <input type="text" name="message_text" class="form-control" placeholder="Type your message" required>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" name="send_message" class="btn btn-success btn-block">
                                <i class="feather icon-send"></i> Send
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Facility List -->
        <div class="card mb-3">
            <div class="card-header"><h6>Select Facility to Chat</h6></div>
            <div class="card-body">
                <?php
                while ($fac = mysqli_fetch_assoc($fac_list_result)) {
                    $fid = $fac['fac_id'];
                    $fname = htmlspecialchars($fac['fac_name']);
                    $active_class = ($selected_facility_id == $fid) ? 'btn-primary' : 'btn-outline-primary';
                    echo "<a href='send.php?facility_id=$fid' class='btn $active_class btn-sm mb-1'>$fname</a> ";
                }
                ?>
            </div>
        </div>

        <!-- Chat Box -->
        <div class="card chat-card mb-4">
            <div class="card-header">
                <h5>Chat</h5>
                <div class="card-header-right">
                    <div class="btn-group card-option">
                        <button type="button" class="btn dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="feather icon-more-horizontal"></i>
                        </button>
                        <ul class="list-unstyled card-option dropdown-menu dropdown-menu-right">
                            <li class="dropdown-item full-card"><a href="#!"><span><i class="feather icon-maximize"></i> maximize</span><span style="display:none"><i class="feather icon-minimize"></i> Restore</span></a></li>
                            <li class="dropdown-item minimize-card"><a href="#!"><span><i class="feather icon-minus"></i> collapse</span><span style="display:none"><i class="feather icon-plus"></i> expand</span></a></li>
                            <li class="dropdown-item reload-card"><a href="send.php?facility_id=<?php echo $selected_facility_id; ?>"><i class="feather icon-refresh-cw"></i> reload</a></li>
                            <li class="dropdown-item close-card"><a href="#!"><i class="feather icon-trash"></i> remove</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                <?php
                if ($chat_result && mysqli_num_rows($chat_result) > 0) {
                    while ($row = mysqli_fetch_assoc($chat_result)) {
                        $is_admin_sender = ($row['sender_facility_id'] == 0);

                        if ($is_admin_sender) {
                            // Admin sent message (Received chat style)
                            ?>
                            <div class="row m-b-20 received-chat">
                                <div class="col-auto p-r-0">
                                    <img src="assets/images/user/admin-avatar.jpg" alt="admin image" class="img-radius wid-40">
                                </div>
                                <div class="col">
                                    <div class="msg">
                                        <p class="m-b-0"><?php echo nl2br(htmlspecialchars($row['message_text'])); ?></p>
                                    </div>
                                    <p class="text-muted m-b-0">
                                        <i class="fa fa-clock-o m-r-10"></i><?php echo date("d M Y H:i", strtotime($row['message_date'])); ?>
                                        → To: <?php echo htmlspecialchars($row['receiver_name']); ?>
                                    </p>
                                </div>
                            </div>
                            <?php
                        } else {
                            // Facility sent message (Send chat style)
                            ?>
                            <div class="row m-b-20 send-chat">
                                <div class="col">
                                    <div class="msg">
                                        <p class="m-b-0"><?php echo nl2br(htmlspecialchars($row['message_text'])); ?></p>
                                    </div>
                                    <p class="text-muted m-b-0">
                                        <i class="fa fa-clock-o m-r-10"></i><?php echo date("d M Y H:i", strtotime($row['message_date'])); ?>
                                        ← From: <strong><?php echo htmlspecialchars($row['sender_name']); ?></strong>
                                    </p>
                                </div>
                                <div class="col-auto p-l-0">
                                    <img src="assets/images/user/facility-avatar.jpg" alt="facility image" class="img-radius wid-40">
                                </div>
                            </div>
                            <?php
                        }
                    }
                } else {
                    echo "<p class='text-muted'>No chat messages yet.</p>";
                }
                ?>
            </div>

            <!-- Message Send Box -->
            <div class="card-body border-top">
                <?php if ($selected_facility_id > 0) { ?>
                <form method="POST" action="">
                    <div class="input-group m-t-15">
                        <input type="hidden" name="receiver_facility_id" value="<?php echo $selected_facility_id; ?>">
                        <input type="text" name="message_text" class="form-control" placeholder="Send message" required>
                        <div class="input-group-append">
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="feather icon-message-circle"></i>
                            </button>
                        </div>
                    </div>
                </form>
                <?php } else { ?>
                    <p class="text-center text-muted">Select a facility to start chat.</p>
                <?php } ?>
            </div>
        </div>

    </div>
</div>

<?php include("assets/head/f.php"); ?>
