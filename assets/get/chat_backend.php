<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$myId = intval($_SESSION['u_facilityid'] ?? 0); // 0 = admin

/* ============================================================
   1) LOAD FACILITY LIST WITH UNREAD COUNTS
============================================================ */
if (isset($_GET['load_facilities'])) {

    /* unread map */
    $unreadMap = [];
    $uq = mysqli_query($con, "
        SELECT sender_facility_id, COUNT(*) AS unread
        FROM facility_chat_messages
        WHERE receiver_facility_id = {$myId}
          AND is_read = 0
        GROUP BY sender_facility_id
    ");

    while ($u = mysqli_fetch_assoc($uq)) {
        $unreadMap[intval($u['sender_facility_id'])] = intval($u['unread']);
    }

    /* ---------------------------
       ADMIN MODE
    --------------------------- */
    if ($myId == 0) {

        $q = mysqli_query($con, "
            SELECT fac_id, fac_name
            FROM facilities
            ORDER BY fac_name
        ");

        while ($r = mysqli_fetch_assoc($q)) {

            $fid  = intval($r['fac_id']);
            $name = htmlspecialchars($r['fac_name']);

            $first = strtoupper(substr(trim($name), 0, 1));
            if ($first == "") $first = "?";

            $unread = $unreadMap[$fid] ?? 0;
            $badge  = ($unread > 0) ? "<span class='unread-badge'>{$unread}</span>" : "";

            echo "
            <div class='chat-list-item' id='fac_{$fid}' onclick=\"openChat({$fid}, '{$name}')\">
                <div class='fac-avatar'>{$first}</div>
                <div class='fac-info'><strong>{$name}</strong></div>
                {$badge}
            </div>";
        }
        exit;
    }

    /* ---------------------------
       FACILITY MODE
    --------------------------- */

    /* Admin row */
    $adminUnread = $unreadMap[0] ?? 0;
    $adminBadge  = $adminUnread ? "<span class='unread-badge'>{$adminUnread}</span>" : "";

    echo "
    <div class='chat-list-item' id='fac_0' onclick=\"openChat(0,'Admin')\">
        <div class='fac-avatar'>A</div>
        <div class='fac-info'><strong>Admin</strong></div>
        {$adminBadge}
    </div>";

    /* Other facilities */
    $q = mysqli_query($con, "
        SELECT fac_id, fac_name 
        FROM facilities 
        WHERE fac_id != {$myId}
        ORDER BY fac_name
    ");

    while ($r = mysqli_fetch_assoc($q)) {

        $fid  = intval($r['fac_id']);
        $name = htmlspecialchars($r['fac_name']);

        $first = strtoupper(substr(trim($name), 0, 1));
        if ($first == "") $first = "?";

        $unread = $unreadMap[$fid] ?? 0;
        $badge  = ($unread > 0) ? "<span class='unread-badge'>{$unread}</span>" : "";

        echo "
        <div class='chat-list-item' id='fac_{$fid}' onclick=\"openChat({$fid}, '{$name}')\">
            <div class='fac-avatar'>{$first}</div>
            <div class='fac-info'><strong>{$name}</strong></div>
            {$badge}
        </div>";
    }

    exit;
}



/* ============================================================
   2) LOAD NEW MESSAGES (INCREMENTAL)
============================================================ */
if (isset($_GET['load_messages'])) {

    $sender   = intval($_GET['sender']);
    $receiver = intval($_GET['receiver']);
    $lastID   = intval($_GET['last_id'] ?? 0);

    /* Mark incoming messages as read */
    mysqli_query($con, "
        UPDATE facility_chat_messages
        SET is_read = 1
        WHERE receiver_facility_id = {$sender}
          AND sender_facility_id   = {$receiver}
          AND is_read = 0
    ");

    /* Fetch NEW messages */
    $q = mysqli_query($con, "
        SELECT *
        FROM facility_chat_messages
        WHERE (
                (sender_facility_id = {$sender} AND receiver_facility_id = {$receiver})
                OR
                (sender_facility_id = {$receiver} AND receiver_facility_id = {$sender})
              )
          AND message_id > {$lastID}
        ORDER BY message_id ASC
    ");

    if (!$q) exit;

    $lastDate = "";

    while ($m = mysqli_fetch_assoc($q)) {

        $mid      = $m['message_id'];
        $rawText  = $m['message_text'];
        $isMine   = ($m['sender_facility_id'] == $sender);
        $datetime = $m['message_date'];
        $time     = date("h:i A", strtotime($datetime));

        /* ----- Date group separator ----- */
        $dateLabel = formatChatDate($datetime);
        if ($dateLabel != $lastDate) {
            echo "<div class='date-separator'><span>{$dateLabel}</span></div>";
            $lastDate = $dateLabel;
        }

        /* ----- Ticks ----- */
        if ($isMine) {
            $ticks = ($m['is_read'])
                ? "<span class='msg-ticks' style='color:#FFFFFF'>✓✓</span>"
                : "<span class='msg-ticks'>✓</span>";
        } else {
            $ticks = "";
        }

        /* ----- Render text or file ----- */
        if (is_file_message($rawText)) {
            $content = render_file_message($rawText);
        } else {
            $content = nl2br(htmlspecialchars($rawText));
        }

        $class = $isMine ? "sent" : "received";

        echo "
            <div class='message {$class} message-item' data-id='{$mid}'>
                {$content}
                <div class='message-time'>{$time} {$ticks}</div>
            </div>
        ";
    }

    exit;
}



/* ============================================================
   3) SEND TEXT MESSAGE
============================================================ */
if (isset($_POST['send_message'])) {

    $sender   = intval($_POST['sender']);
    $receiver = intval($_POST['receiver']);
    $msg      = trim($_POST['message']);

    if ($msg === "") exit("EMPTY");

    $msg = mysqli_real_escape_string($con, $msg);

    mysqli_query($con, "
        INSERT INTO facility_chat_messages
        (sender_facility_id, receiver_facility_id, message_text, is_active, is_read)
        VALUES ({$sender}, {$receiver}, '{$msg}', 1, 0)
    ");

    echo "OK";
    exit;
}



/* ============================================================
   4) TYPING INDICATOR
============================================================ */
if (isset($_POST['typing'])) {

    $sender   = intval($_POST['sender']);
    $receiver = intval($_POST['receiver']);

    mysqli_query($con, "
        REPLACE INTO chat_typing_status (sender_id, receiver_id, typing_time)
        VALUES ({$sender}, {$receiver}, NOW())
    ");

    exit("OK");
}

if (isset($_GET['get_typing'])) {

    $sender   = intval($_GET['sender']);
    $receiver = intval($_GET['receiver']);

    $q = mysqli_query($con, "
        SELECT TIMESTAMPDIFF(SECOND, typing_time, NOW()) AS sec
        FROM chat_typing_status
        WHERE sender_id = {$receiver}
          AND receiver_id = {$sender}
        LIMIT 1
    ");

    if ($row = mysqli_fetch_assoc($q)) {
        if ($row['sec'] <= 2) {
            echo "<span style='font-size:12px;color:#007bff;'>typing...</span>";
        }
    }

    exit;
}



/* ============================================================
   HELPER FUNCTIONS
============================================================ */
function formatChatDate($date) {
    $msg  = date("Y-m-d", strtotime($date));
    $today = date("Y-m-d");
    $yest  = date("Y-m-d", strtotime("-1 day"));

    if ($msg == $today) return "Today";
    if ($msg == $yest)  return "Yesterday";

    return date("d M Y", strtotime($date));
}

function startsWithStr($str, $prefix) {
    return substr($str, 0, strlen($prefix)) === $prefix;
}

function is_file_message($msg) {
    $msg = html_entity_decode($msg);
    return startsWithStr($msg, "/assets/chat_files/");
}


/* ============================================================
   FILE PREVIEW WITH ICONS (PDF, EXCEL, WORD, ZIP, VIDEO)
============================================================ */
function render_file_message($path, $isMine = false) {

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $filename = basename($path);

    // Choose icon based on file type
    $icon = "<span style='font-size:20px;'>📄</span>";

    if (in_array($ext, ["jpg","jpeg","png","gif"])) {
        return "<img src='{$path}' style='max-width:200px;border-radius:8px;'>";
    }
    if (in_array($ext, ["pdf"])) {
        $icon = "<span style='color:#d9534f;font-size:20px;'>📕</span>";
    }
    if (in_array($ext, ["xls","xlsx","csv"])) {
        $icon = "<span style='color:#28a745;font-size:20px;'>📗</span>";
    }
    if (in_array($ext, ["doc","docx"])) {
        $icon = "<span style='color:#0275d8;font-size:20px;'>📘</span>";
    }

    // Color for text based on bubble type
    $textColor = $isMine ? "#fff" : "#333";

    return "
        <div style='display:flex;align-items:center;gap:8px;'>
            {$icon}
            <a href='{$path}' target='_blank' style='color:{$textColor}; text-decoration:underline; font-size:15px;'>
                {$filename}
            </a>
        </div>
    ";
}



?>
