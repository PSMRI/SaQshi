<?php
include(__DIR__ . "/../../assets/conn/db.php");
include(__DIR__ . "/../../assets/conn/session.php");

$myId    = (int)($_SESSION['u_facilityid'] ?? 0); // 0 = admin
$isAdmin = ($myId === 0);

/* ============================================================
   1) LOAD FACILITY LIST (WITH UNREAD COUNTS)
============================================================ */
if (isset($_GET['load_facilities'])) {

    $unreadMap = [];

    /* ---------- NORMAL MESSAGE UNREAD ---------- */
    if ($isAdmin) {
        $uq = mysqli_query($con, "
            SELECT sender_facility_id, COUNT(*) AS unread
            FROM facility_chat_messages
            WHERE receiver_facility_id = 0
              AND sender_facility_id != 0
              AND is_read = 0
            GROUP BY sender_facility_id
        ");
    } else {
        $uq = mysqli_query($con, "
            SELECT sender_facility_id, COUNT(*) AS unread
            FROM facility_chat_messages
            WHERE receiver_facility_id = {$myId}
              AND sender_facility_id != 0
              AND is_read = 0
            GROUP BY sender_facility_id
        ");
    }

    while ($u = mysqli_fetch_assoc($uq)) {
        $unreadMap[(int)$u['sender_facility_id']] = (int)$u['unread'];
    }

    /* ================= ADMIN MODE ================= */
    if ($isAdmin) {

        echo "
        <div class='chat-list-item all-fac' id='fac_0'
             onclick=\"openChat(0,'All Facilities')\">
            <div class='fac-avatar'>📢</div>
            <div class='fac-info'>
                <strong>All Facilities</strong><br>
                <small class='text-muted'>Broadcast</small>
            </div>
        </div>";

        $q = mysqli_query($con, "
            SELECT fac_id, fac_name
            FROM facilities
            ORDER BY fac_name
        ");

        while ($r = mysqli_fetch_assoc($q)) {

            $fid  = (int)$r['fac_id'];
            $name = htmlspecialchars($r['fac_name'], ENT_QUOTES);
            $first = strtoupper($name[0] ?? "?");

            $badge = isset($unreadMap[$fid])
                ? "<span class='unread-badge'>{$unreadMap[$fid]}</span>"
                : "";

            echo "
            <div class='chat-list-item' id='fac_{$fid}'
                 onclick=\"openChat({$fid}, '{$name}')\">
                <div class='fac-avatar'>{$first}</div>
                <div class='fac-info'><strong>{$name}</strong></div>
                {$badge}
            </div>";
        }
        exit;
    }

    /* ================= FACILITY MODE ================= */

    /* ----- ADMIN ROW ----- */
    echo "
    <div class='chat-list-item' id='fac_0'
         onclick=\"openChat(0,'Admin')\">
        <div class='fac-avatar'>A</div>
        <div class='fac-info'><strong>Admin</strong></div>
    </div>";

    /* ----- OTHER FACILITIES ----- */
    $q = mysqli_query($con, "
        SELECT fac_id, fac_name
        FROM facilities
        WHERE fac_id != {$myId}
        ORDER BY fac_name
    ");

    while ($r = mysqli_fetch_assoc($q)) {

        $fid  = (int)$r['fac_id'];
        $name = htmlspecialchars($r['fac_name'], ENT_QUOTES);
        $first = strtoupper($name[0] ?? "?");

        $badge = isset($unreadMap[$fid])
            ? "<span class='unread-badge'>{$unreadMap[$fid]}</span>"
            : "";

        echo "
        <div class='chat-list-item' id='fac_{$fid}'
             onclick=\"openChat({$fid}, '{$name}')\">
            <div class='fac-avatar'>{$first}</div>
            <div class='fac-info'><strong>{$name}</strong></div>
            {$badge}
        </div>";
    }
    exit;
}

/* ============================================================
   2) LOAD MESSAGES (NORMAL + BROADCAST)
============================================================ */
if (isset($_GET['load_messages'])) {

    $sender   = (int)$_GET['sender'];
    $receiver = (int)$_GET['receiver'];
    $lastID   = (int)($_GET['last_id'] ?? 0);

    /* ---- MARK NORMAL MESSAGES AS READ ---- */
    mysqli_query($con, "
        UPDATE facility_chat_messages
        SET is_read = 1
        WHERE receiver_facility_id = {$sender}
          AND sender_facility_id   = {$receiver}
          AND is_read = 0
    ");

    /* ---- MARK BROADCAST AS READ (PER FACILITY) ---- */
    if ($sender !== 0) {
        mysqli_query($con, "
            INSERT IGNORE INTO facility_broadcast_read (message_id, facility_id)
            SELECT message_id, {$sender}
            FROM facility_chat_messages
            WHERE sender_facility_id = 0
              AND receiver_facility_id = 0
        ");
    }

    $q = mysqli_query($con, "
        SELECT *
        FROM facility_chat_messages
        WHERE message_id > {$lastID}
          AND is_active = 1
          AND (
                (sender_facility_id = {$sender} AND receiver_facility_id = {$receiver})
             OR (sender_facility_id = {$receiver} AND receiver_facility_id = {$sender})
             OR (sender_facility_id = 0 AND receiver_facility_id = 0)
          )
        ORDER BY message_id ASC
    ");

    while ($m = mysqli_fetch_assoc($q)) {

        $mid  = $m['message_id'];
        $text = $m['message_text'];

        $isMine = ($m['sender_facility_id'] == $sender);
        $isBc   = ($m['sender_facility_id'] == 0 && $m['receiver_facility_id'] == 0);

        $class = $isBc ? "broadcast" : ($isMine ? "sent" : "received");

        if (is_file_message($text)) {
            $content = render_file_message($text, $isMine);
        } else {
            $content = nl2br(htmlspecialchars($text));
        }

        echo "
        <div class='message {$class} message-item' data-id='{$mid}'>
            {$content}
        </div>";
    }
    exit;
}

/* ============================================================
   3) SEND MESSAGE (NORMAL + BROADCAST)
============================================================ */
if (isset($_POST['send_message'])) {

    $sender   = (int)$_POST['sender'];
    $receiver = (int)$_POST['receiver'];
    $msg      = trim($_POST['message'] ?? '');

    if ($msg === '') exit("EMPTY");

    $msg = mysqli_real_escape_string($con, $msg);

    mysqli_query($con, "
        INSERT INTO facility_chat_messages
        (sender_facility_id, receiver_facility_id, message_text, is_active, is_read)
        VALUES ({$sender}, {$receiver}, '{$msg}', 1, 0)
    ");

    exit("OK");
}

/* ============================================================
   4) TYPING INDICATOR (NO BROADCAST)
============================================================ */
if (isset($_POST['typing'])) {

    $sender   = (int)$_POST['sender'];
    $receiver = (int)$_POST['receiver'];

    if ($sender === 0 || $receiver === 0) exit;

    mysqli_query($con, "
        REPLACE INTO chat_typing_status (sender_id, receiver_id, typing_time)
        VALUES ({$sender}, {$receiver}, NOW())
    ");
    exit;
}

if (isset($_GET['get_typing'])) {

    $sender   = (int)$_GET['sender'];
    $receiver = (int)$_GET['receiver'];

    if ($sender === 0 || $receiver === 0) exit;

    $q = mysqli_query($con, "
        SELECT TIMESTAMPDIFF(SECOND, typing_time, NOW()) AS sec
        FROM chat_typing_status
        WHERE sender_id = {$receiver}
          AND receiver_id = {$sender}
        LIMIT 1
    ");

    if ($q && ($r = mysqli_fetch_assoc($q)) && (int)$r['sec'] <= 2) {
        echo "typing...";
    }
    exit;
}

/* ============================================================
   HELPER FUNCTIONS
============================================================ */
function is_file_message($msg) {
    $msg = trim(html_entity_decode($msg));
    return preg_match('#^(/assets/chat_files/|https?://.*/assets/chat_files/)#i', $msg);
}

function render_file_message($path, $isMine=false) {

    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $file = basename($path);

    if (in_array($ext, ['jpg','jpeg','png','gif'])) {
        return "<img src='{$path}' style='max-width:220px;border-radius:8px'>";
    }

    $icons = [
        'pdf'=>'📕','xls'=>'📗','xlsx'=>'📗','csv'=>'📗',
        'doc'=>'📘','docx'=>'📘'
    ];

    $icon  = $icons[$ext] ?? '📄';
    $color = $isMine ? '#fff' : '#333';

    return "
        <div style='display:flex;align-items:center;gap:8px'>
            <span style='font-size:20px'>{$icon}</span>
            <a href='{$path}' target='_blank'
               style='color:{$color};text-decoration:underline'>
                {$file}
            </a>
        </div>";
}
?>
