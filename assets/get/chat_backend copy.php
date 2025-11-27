<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$myId = isset($_SESSION['u_facilityid']) ? intval($_SESSION['u_facilityid']) : 0;


/* =====================================================
   HELPER: FORMAT DATE (Today / Yesterday / dd M YYYY)
===================================================== */
function formatChatDate($date) {
    $msgDate = date("Y-m-d", strtotime($date));
    $today = date("Y-m-d");
    $yesterday = date("Y-m-d", strtotime("-1 day"));

    if ($msgDate == $today) return "Today";
    if ($msgDate == $yesterday) return "Yesterday";

    return date("d M Y", strtotime($date));
}


/* =====================================================
   UNREAD MESSAGE MAP
===================================================== */
$unreadMap = [];
$unreadResult = mysqli_query($con, "
    SELECT sender_facility_id, COUNT(*) AS unread
    FROM facility_chat_messages
    WHERE receiver_facility_id = {$myId}
      AND is_read = 0
    GROUP BY sender_facility_id
");
while ($u = mysqli_fetch_assoc($unreadResult)) {
    $unreadMap[intval($u['sender_facility_id'])] = intval($u['unread']);
}



/* =====================================================
   LOAD FACILITY LIST
===================================================== */
if (isset($_GET['load_facilities'])) {

    // ADMIN MODE
    if ($myId === 0) {
        $q = mysqli_query($con, "SELECT fac_id, fac_name FROM facilities ORDER BY fac_name");

        while ($row = mysqli_fetch_assoc($q)) {
            $fid = intval($row['fac_id']);
            $fname = htmlspecialchars($row['fac_name']);
            $first = strtoupper(substr($fname, 0, 1));
            $unread = $unreadMap[$fid] ?? 0;

            $badge = $unread > 0 ? "<span class='unread-badge'>{$unread}</span>" : "";

            echo "
            <div class='chat-list-item' id='fac_{$fid}' onclick=\"openChat({$fid}, '{$fname}')\">
                <div class='fac-avatar'>{$first}</div>
                <div class='fac-info'><strong>{$fname}</strong><br><small>Tap to chat</small></div>
                {$badge}
            </div>";
        }
        exit;
    }

    // FACILITY MODE: Add Admin first
    $adminUnread = $unreadMap[0] ?? 0;
    $adminBadge = $adminUnread > 0 ? "<span class='unread-badge'>{$adminUnread}</span>" : "";

    echo "
    <div class='chat-list-item' id='fac_0' onclick=\"openChat(0, 'Admin')\">
        <div class='fac-avatar'>A</div>
        <div class='fac-info'><strong>Admin</strong><br><small>Tap to chat</small></div>
        {$adminBadge}
    </div>";

    // Load other facilities
    $q = mysqli_query($con, "
        SELECT fac_id, fac_name FROM facilities
        WHERE fac_id != {$myId} ORDER BY fac_name
    ");

    while ($row = mysqli_fetch_assoc($q)) {
        $fid = intval($row['fac_id']);
        $fname = htmlspecialchars($row['fac_name']);
        $first = strtoupper(substr($fname, 0, 1));
        $unread = $unreadMap[$fid] ?? 0;
        $badge = $unread > 0 ? "<span class='unread-badge'>{$unread}</span>" : "";

        echo "
        <div class='chat-list-item' id='fac_{$fid}' onclick=\"openChat({$fid}, '{$fname}')\">
            <div class='fac-avatar'>{$first}</div>
            <div class='fac-info'><strong>{$fname}</strong><br><small>Tap to chat</small></div>
            {$badge}
        </div>";
    }

    exit;
}



/* =====================================================
   LOAD NEW MESSAGES (SILENT REFRESH)
===================================================== */
if (isset($_GET['load_messages'])) {

    if (ob_get_length()) ob_clean();

    $sender = intval($_GET['sender']);
    $receiver = intval($_GET['receiver']);
    $lastId = intval($_GET['last_id'] ?? 0);

    if ($sender < 0) $sender = 0;
    if ($receiver < 0) $receiver = 0;

    // Mark as read
    mysqli_query($con, "
        UPDATE facility_chat_messages
        SET is_read = 1
        WHERE receiver_facility_id = {$sender}
          AND sender_facility_id = {$receiver}
    ");

    // Only NEW messages (silent refresh)
    $extra = $lastId > 0 ? "AND message_id > $lastId" : "";

    $sql = "
        SELECT message_id, message_text, message_date, sender_facility_id
        FROM facility_chat_messages
        WHERE 
             ((sender_facility_id = {$sender} AND receiver_facility_id = {$receiver})
          OR  (sender_facility_id = {$receiver} AND receiver_facility_id = {$sender}))
          {$extra}
        ORDER BY message_id ASC
    ";

    $q = mysqli_query($con, $sql);
    if (!$q) { echo ""; exit; }

    $lastDate = "";

    while ($row = mysqli_fetch_assoc($q)) {

        $msgId = $row['message_id'];
        $msg = htmlspecialchars($row['message_text']);
        $time = $row['message_date'];

        $dateLabel = formatChatDate($time);

        // Print date separator only for NEW groups
        if ($dateLabel != $lastDate && $lastId == 0) {
            echo "
            <div class='date-separator'>
                <span>{$dateLabel}</span>
            </div>";
            $lastDate = $dateLabel;
        }

        $isMine = $row['sender_facility_id'] == $sender;
        $class = $isMine ? "sent" : "received";

        echo "
        <div class='message {$class} message-item' data-id='{$msgId}'>
            {$msg}
            <div class='message-time'>" . date("h:i A", strtotime($time)) . "</div>
        </div>";
    }

    exit;
}



/* =====================================================
   SEND MESSAGE
===================================================== */
if (isset($_POST['send_message'])) {

    $sender = intval($_POST['sender']);
    $receiver = intval($_POST['receiver']);
    $msg = trim($_POST['message']);

    if ($msg == "") { echo "EMPTY"; exit; }

    $msg = mysqli_real_escape_string($con, $msg);

    mysqli_query($con, "
        INSERT INTO facility_chat_messages
        (sender_facility_id, receiver_facility_id, message_text, is_active, is_read)
        VALUES ({$sender}, {$receiver}, '{$msg}', 1, 0)
    ");

    echo "OK";
    exit;
}

?>
